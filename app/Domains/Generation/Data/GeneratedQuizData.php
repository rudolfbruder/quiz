<?php

declare(strict_types=1);

namespace App\Domains\Generation\Data;

use App\Domains\Generation\Exceptions\UnusableGeneratedQuizException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One generation run's output, hydrated out of the agent's structured response.
 *
 * The provider answering in the declared schema proves the shape and nothing
 * else. A schema-valid answer can still carry no questions, a question nobody
 * can answer, or one with two right answers, so `assertUsable` is run over the
 * hydrated object before any of it goes near the database. `hydrate` is the
 * single path in: it hydrates and checks together, so neither can be skipped.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class GeneratedQuizData extends Data
{
    /**
     * A question below this many options cannot be answered by choosing.
     */
    public const MINIMUM_OPTIONS_PER_QUESTION = 2;

    /**
     * @param  array<int, GeneratedOutlineSectionData>  $outline
     * @param  array<int, GeneratedQuestionData>  $questions
     */
    public function __construct(
        public readonly string $topicName,
        public readonly string $title,
        public readonly string $language,
        public readonly int $pageCount,
        #[DataCollectionOf(GeneratedOutlineSectionData::class)]
        public readonly array $outline,
        #[DataCollectionOf(GeneratedQuestionData::class)]
        public readonly array $questions,
    ) {}

    /**
     * Hydrate a generation run from the agent's structured response, and refuse
     * it if what came back cannot be turned into a quiz.
     *
     * @param  Arrayable<string, mixed>|array<string, mixed>  $response
     *
     * @throws UnusableGeneratedQuizException
     */
    public static function hydrate(Arrayable|array $response): self
    {
        $quiz = self::from($response instanceof Arrayable ? $response->toArray() : $response);

        $quiz->assertUsable();

        return $quiz;
    }

    /**
     * Check the run against the rules the schema cannot express.
     *
     * @throws UnusableGeneratedQuizException
     */
    public function assertUsable(): void
    {
        if ($this->questions === []) {
            throw UnusableGeneratedQuizException::noQuestions();
        }

        foreach (array_values($this->questions) as $index => $question) {
            $this->assertQuestionUsable($question, $index + 1);
        }
    }

    /**
     * Check one question, named by its position in the run so a failure reason
     * points at something a person can find.
     *
     * @throws UnusableGeneratedQuizException
     */
    private function assertQuestionUsable(GeneratedQuestionData $question, int $questionNumber): void
    {
        if (trim($question->explanation) === '') {
            throw UnusableGeneratedQuizException::missingExplanation($questionNumber);
        }

        $optionCount = count($question->options);

        if ($optionCount < self::MINIMUM_OPTIONS_PER_QUESTION) {
            throw UnusableGeneratedQuizException::tooFewOptions(
                $questionNumber,
                $optionCount,
                self::MINIMUM_OPTIONS_PER_QUESTION,
            );
        }

        $correctCount = count($question->correctOptions());

        if ($correctCount === 0) {
            throw UnusableGeneratedQuizException::noCorrectOption($questionNumber);
        }

        if ($correctCount > 1) {
            throw UnusableGeneratedQuizException::severalCorrectOptions($questionNumber, $correctCount);
        }

        $this->assertOptionsAreDistinct($question, $questionNumber);
    }

    /**
     * Two options that read the same are one option offered twice, whatever the
     * casing and surrounding whitespace say.
     *
     * @throws UnusableGeneratedQuizException
     */
    private function assertOptionsAreDistinct(GeneratedQuestionData $question, int $questionNumber): void
    {
        $seen = [];

        foreach ($question->options as $option) {
            $normalised = Str::lower(trim($option->text));

            if (in_array($normalised, $seen, strict: true)) {
                throw UnusableGeneratedQuizException::duplicateOption($questionNumber, $option->text);
            }

            $seen[] = $normalised;
        }
    }
}
