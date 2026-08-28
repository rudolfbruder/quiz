<?php

declare(strict_types=1);

namespace App\Domains\Generation\Jobs;

use App\Domains\Generation\Actions\PersistGeneratedQuiz;
use App\Domains\Generation\Agents\GenerateQuizFromSource;
use App\Domains\Generation\Data\GeneratedQuizData;
use App\Domains\Generation\Exceptions\UnusableGeneratedQuizException;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;
use Throwable;

/**
 * Turn one uploaded document into a quiz, away from the request that uploaded it.
 *
 * This job owns the lifecycle and nothing else. It claims the source, calls the
 * agent, hands what comes back to `PersistGeneratedQuiz`, and records where the
 * run ended up:
 *
 *     pending ──▶ processing ──▶ ready
 *                     │
 *                     ├──▶ rejected   the document is longer than the limit
 *                     └──▶ failed     provider error, unusable answer, storage error
 *
 * The domain logic lives in the action; the statuses, the retries and the
 * failure reasons live here.
 */
class GenerateQuiz implements ShouldQueue
{
    use Queueable;

    /**
     * How long a recorded failure reason may be before it is cut short.
     */
    private const MAXIMUM_REASON_LENGTH = 1000;

    /**
     * How many times a run is attempted before it is left failed.
     */
    public int $tries = 3;

    /**
     * How long to wait between attempts, in seconds.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 120];

    /**
     * How long a single attempt may take, in seconds.
     *
     * This sits above the agent's own timeout on purpose: the agent giving up
     * should read as a provider failure with a reason, not as a killed worker.
     */
    public int $timeout = 300;

    /**
     * The source is carried by its identifiers rather than as a model, so that a
     * document deleted between dispatch and execution is something this job can
     * check for rather than something that breaks it on the way in.
     */
    public function __construct(
        public readonly int $sourceId,
        public readonly int $userId,
    ) {}

    /**
     * Queue the generation of a quiz from a stored source.
     */
    public static function forSource(Source $source): self
    {
        return new self($source->id, $source->user_id);
    }

    /**
     * Run the generation.
     */
    public function handle(PersistGeneratedQuiz $persistGeneratedQuiz): void
    {
        $source = $this->source();

        if ($source === null || ! $this->claim($source)) {
            return;
        }

        try {
            $response = GenerateQuizFromSource::make(disk: $source->disk, path: $source->path)->generate();

            $generated = GeneratedQuizData::hydrate($this->structuredAnswerOf($response));

            if ($this->rejectOversizedDocument($source, $generated->pageCount)) {
                return;
            }

            $this->recordRun($source, $persistGeneratedQuiz($source, $generated), $response);
        } catch (UnusableGeneratedQuizException $exception) {
            // The provider answered, and its answer cannot be turned into a quiz.
            // Another attempt would cost another run for the same document, so
            // this one is the last: the person retries deliberately or not at all.
            $this->recordFailure($source, $exception->getMessage());

            $this->fail($exception);
        } catch (Throwable $exception) {
            // A provider error or a failed write, either of which a later attempt
            // may well get past. The source is left `failed` rather than
            // `processing` so that the retry can claim it again.
            $this->recordFailure($source, $this->reasonFor($exception));

            throw $exception;
        }
    }

    /**
     * Record a run that broke somewhere this job could not see it happen — a
     * killed worker, a timeout — and so never wrote a reason of its own.
     */
    public function failed(Throwable $exception): void
    {
        $source = $this->source();

        if ($source === null || $source->status !== SourceStatus::Processing) {
            return;
        }

        $this->recordFailure($source, $this->reasonFor($exception));
    }

    /**
     * Take the structured answer out of the response.
     *
     * The agent declares a schema, so a provider that answered anything else
     * answered wrongly, and there is nothing here to hydrate.
     *
     * @return array<string, mixed>
     */
    private function structuredAnswerOf(AgentResponse $response): array
    {
        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('The provider answered outside the structure it was asked for.');
        }

        $answer = [];

        foreach ($response->structured as $field => $value) {
            $answer[(string) $field] = $value;
        }

        return $answer;
    }

    /**
     * Read the source back, confirming it still exists and still belongs to the
     * person it was dispatched for. A payload is not evidence of either.
     */
    private function source(): ?Source
    {
        return Source::query()
            ->whereKey($this->sourceId)
            ->where('user_id', $this->userId)
            ->first();
    }

    /**
     * Take ownership of the source for this run.
     *
     * The move into `processing` is a conditional update rather than a read
     * followed by a write, so two dispatches for the same document cannot both
     * believe they won: only the update that changed a row proceeds. `failed` is
     * a state a run may be claimed out of, which is what makes a retry work;
     * `processing` is not, which is what stops a second run starting.
     */
    private function claim(Source $source): bool
    {
        return Source::query()
            ->whereKey($source->id)
            ->whereIn('status', [SourceStatus::Pending, SourceStatus::Failed])
            ->update([
                'status' => SourceStatus::Processing,
                'failure_reason' => null,
            ]) === 1;
    }

    /**
     * Refuse a document that turned out to be longer than the limit allows.
     *
     * The page count is only known once the document has been read, so the
     * refusal happens here rather than at upload. It names the real count and
     * the limit, because a person told only that their document is "too long"
     * has no way to argue with a wrong count.
     */
    private function rejectOversizedDocument(Source $source, int $pageCount): bool
    {
        $limit = Config::integer('generation.max_pages');

        if ($pageCount <= $limit) {
            return false;
        }

        Source::query()
            ->whereKey($source->id)
            ->where('status', SourceStatus::Processing)
            ->update([
                'status' => SourceStatus::Rejected,
                'page_count' => $pageCount,
                'failure_reason' => (string) __(
                    'This document has :pages pages, which is above the limit of :limit pages.',
                    ['pages' => $pageCount, 'limit' => $limit],
                ),
                'analysed_at' => now(),
            ]);

        return true;
    }

    /**
     * Leave the source failed, with a reason written for the person reading it.
     *
     * The update is conditional on the source still being the one this run
     * claimed, so a late failure cannot overwrite a state something else has
     * since put it in.
     */
    private function recordFailure(Source $source, string $reason): void
    {
        Source::query()
            ->whereKey($source->id)
            ->where('status', SourceStatus::Processing)
            ->update([
                'status' => SourceStatus::Failed,
                'failure_reason' => Str::limit($reason, self::MAXIMUM_REASON_LENGTH),
                'analysed_at' => now(),
            ]);
    }

    /**
     * Put a failure into words a person can act on.
     */
    private function reasonFor(Throwable $exception): string
    {
        if ($exception instanceof UnusableGeneratedQuizException) {
            return $exception->getMessage();
        }

        return (string) __('This document could not be analysed: :message', [
            'message' => $exception->getMessage(),
        ]);
    }

    /**
     * Record what the run cost.
     *
     * The agent, the source and the token counts, and deliberately nothing else:
     * the prompt and the document belong to the person who uploaded them and have
     * no business in a log file.
     */
    private function recordRun(Source $source, Quiz $quiz, AgentResponse $response): void
    {
        Log::info('Generated a quiz from an uploaded source.', [
            'agent' => GenerateQuizFromSource::class,
            'source_id' => $source->id,
            'quiz_id' => $quiz->id,
            'prompt_tokens' => $response->usage->promptTokens,
            'completion_tokens' => $response->usage->completionTokens,
            'total_tokens' => $response->usage->promptTokens + $response->usage->completionTokens,
        ]);
    }
}
