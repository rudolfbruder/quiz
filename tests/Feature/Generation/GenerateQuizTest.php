<?php

declare(strict_types=1);

use App\Domains\Generation\Actions\PersistGeneratedQuiz;
use App\Domains\Generation\Agents\GenerateQuizFromSource;
use App\Domains\Generation\Data\GeneratedQuizData;
use App\Domains\Generation\Jobs\GenerateQuiz;
use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Question\Models\Option;
use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StructuredTextResponse;

/**
 * No test here reaches a provider: the agent is faked before the job is ever run,
 * and the document it would attach lives on a faked disk.
 */
function generationSource(?User $user = null): Source
{
    Storage::fake('local');

    $source = Source::factory()->for($user ?? User::factory())->create();

    Storage::disk($source->disk)->put($source->path, 'A stored document about the Roman Republic.');

    return $source;
}

function runGenerateQuiz(Source $source, ?PersistGeneratedQuiz $persistGeneratedQuiz = null): void
{
    (new GenerateQuiz($source->id, $source->user_id))
        ->handle($persistGeneratedQuiz ?? app(PersistGeneratedQuiz::class));
}

/**
 * Nothing of a generated quiz reached the database.
 */
function expectNothingGenerated(): void
{
    expect(Quiz::query()->count())->toBe(0)
        ->and(Question::query()->count())->toBe(0)
        ->and(Option::query()->count())->toBe(0)
        ->and(QuizProposal::query()->count())->toBe(0)
        ->and(Topic::query()->count())->toBe(0);
}

it('leaves a source that is already being analysed alone and prompts nothing', function (): void {
    GenerateQuizFromSource::fake()->preventStrayPrompts();

    $source = generationSource();
    $source->update(['status' => SourceStatus::Processing]);

    runGenerateQuiz($source);

    GenerateQuizFromSource::assertNeverPrompted();

    expect($source->refresh()->status)->toBe(SourceStatus::Processing)
        ->and($source->failure_reason)->toBeNull();

    expectNothingGenerated();
});

it('turns a generated run into a ready source and a stored quiz', function (): void {
    GenerateQuizFromSource::fake([generationRunPayload()]);

    $source = generationSource();

    runGenerateQuiz($source);

    GenerateQuizFromSource::assertPromptedTimes(1);

    $quiz = Quiz::query()->sole();

    expect($source->refresh()->status)->toBe(SourceStatus::Ready)
        ->and($source->page_count)->toBe(12)
        ->and($source->failure_reason)->toBeNull()
        ->and($quiz->source_id)->toBe($source->id)
        ->and($quiz->questions()->count())->toBe(3);
});

it('claims a source that failed before, so a retry can run', function (): void {
    GenerateQuizFromSource::fake([generationRunPayload()]);

    $source = generationSource();
    $source->update(['status' => SourceStatus::Failed, 'failure_reason' => 'The provider was unavailable.']);

    runGenerateQuiz($source);

    expect($source->refresh()->status)->toBe(SourceStatus::Ready)
        ->and($source->failure_reason)->toBeNull();
});

it('rejects a document with more pages than the limit, naming both numbers', function (): void {
    config()->set('generation.max_pages', 25);

    GenerateQuizFromSource::fake([generationRunPayload(['page_count' => 40])]);

    $source = generationSource();

    runGenerateQuiz($source);

    $source->refresh();

    expect($source->status)->toBe(SourceStatus::Rejected)
        ->and($source->page_count)->toBe(40)
        ->and($source->failure_reason)->toContain('40')
        ->and($source->failure_reason)->toContain('25');

    expectNothingGenerated();
});

it('accepts a document with exactly as many pages as the limit allows', function (): void {
    config()->set('generation.max_pages', 25);

    GenerateQuizFromSource::fake([generationRunPayload(['page_count' => 25])]);

    $source = generationSource();

    runGenerateQuiz($source);

    expect($source->refresh()->status)->toBe(SourceStatus::Ready)
        ->and($source->page_count)->toBe(25);
});

it('records a readable reason when the provider cannot be reached', function (): void {
    GenerateQuizFromSource::fake(function (): never {
        throw new RuntimeException('The provider is unavailable.');
    });

    $source = generationSource();

    expect(fn () => runGenerateQuiz($source))
        ->toThrow(RuntimeException::class, 'The provider is unavailable.');

    $source->refresh();

    expect($source->status)->toBe(SourceStatus::Failed)
        ->and($source->failure_reason)->toContain('The provider is unavailable.');

    expectNothingGenerated();
});

it('records a readable reason when the provider answers outside the schema', function (): void {
    GenerateQuizFromSource::fake(['Here are some questions about the Roman Republic.']);

    $source = generationSource();

    expect(fn () => runGenerateQuiz($source))->toThrow(RuntimeException::class);

    $source->refresh();

    expect($source->status)->toBe(SourceStatus::Failed)
        ->and($source->failure_reason)->toContain('outside the structure it was asked for');

    expectNothingGenerated();
});

it('records the faulty question when the answer cannot be turned into a quiz', function (): void {
    $payload = generationRunPayload();

    $payload['questions'][1]['options'] = array_map(
        fn (array $option): array => ['text' => $option['text'], 'is_correct' => false],
        $payload['questions'][1]['options'],
    );

    GenerateQuizFromSource::fake([$payload]);

    $source = generationSource();

    runGenerateQuiz($source);

    $source->refresh();

    expect($source->status)->toBe(SourceStatus::Failed)
        ->and($source->failure_reason)->toBe('Question 2 has no option marked correct.');

    expectNothingGenerated();
});

it('records a readable reason when the generated quiz cannot be stored', function (): void {
    GenerateQuizFromSource::fake([generationRunPayload()]);

    $source = generationSource();

    $failingWrite = new class extends PersistGeneratedQuiz
    {
        public function __invoke(Source $source, GeneratedQuizData $generated): Quiz
        {
            throw new RuntimeException('The database went away.');
        }
    };

    expect(fn () => runGenerateQuiz($source, $failingWrite))
        ->toThrow(RuntimeException::class, 'The database went away.');

    $source->refresh();

    expect($source->status)->toBe(SourceStatus::Failed)
        ->and($source->failure_reason)->toContain('The database went away.');

    expectNothingGenerated();
});

it('does nothing when the source was deleted between dispatch and execution', function (): void {
    GenerateQuizFromSource::fake()->preventStrayPrompts();

    $source = generationSource();
    $job = GenerateQuiz::forSource($source);

    $source->delete();

    $job->handle(app(PersistGeneratedQuiz::class));

    GenerateQuizFromSource::assertNeverPrompted();

    expect(Source::query()->count())->toBe(0);

    expectNothingGenerated();
});

it('does nothing when the source no longer belongs to the person it was queued for', function (): void {
    GenerateQuizFromSource::fake()->preventStrayPrompts();

    $source = generationSource();

    (new GenerateQuiz($source->id, User::factory()->create()->id))
        ->handle(app(PersistGeneratedQuiz::class));

    GenerateQuizFromSource::assertNeverPrompted();

    expect($source->refresh()->status)->toBe(SourceStatus::Pending);

    expectNothingGenerated();
});

it('logs the agent, the source and the tokens spent, and nothing of the document', function (): void {
    $payload = generationRunPayload();

    GenerateQuizFromSource::fake([
        new StructuredTextResponse($payload, (string) json_encode($payload), new Usage(1200, 800), new Meta),
    ]);

    Log::spy();

    $source = generationSource();

    runGenerateQuiz($source);

    Log::shouldHaveReceived('info')->withArgs(
        function (string $message, array $context) use ($source): bool {
            $logged = $message.' '.json_encode($context);

            return $context['agent'] === GenerateQuizFromSource::class
                && $context['source_id'] === $source->id
                && $context['prompt_tokens'] === 1200
                && $context['completion_tokens'] === 800
                && $context['total_tokens'] === 2000
                && ! str_contains($logged, 'A stored document')
                && ! str_contains($logged, 'Roman Republic')
                && ! str_contains($logged, 'Tiberius Gracchus')
                && ! str_contains($logged, 'attached document');
        }
    );
});
