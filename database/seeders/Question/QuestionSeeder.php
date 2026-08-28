<?php

declare(strict_types=1);

namespace Database\Seeders\Question;

use App\Domains\Question\Enums\QuestionType;
use App\Domains\Question\Models\Option;
use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Models\Quiz;
use Database\Seeders\Quiz\QuizSeeder;
use Database\Seeders\Source\SourceSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The questions each demo quiz was generated into, taken from hand-written fixtures.
 *
 * The fixtures are a deliverable rather than a convenience: quiz taking is built and
 * reviewed against them, so every question carries a real stem, three plausible
 * distractors alongside the one correct option, and an explanation that says why the
 * answer is the answer.
 */
class QuestionSeeder extends Seeder
{
    /**
     * Seed the questions of every demo quiz.
     */
    public function run(): void
    {
        self::demoQuestions();
    }

    /**
     * Every demo question, in the order its quiz holds it, seeding first if needed.
     *
     * @return Collection<int, Question>
     */
    public static function demoQuestions(): Collection
    {
        $quizzes = QuizSeeder::demoQuizzes();

        foreach ($quizzes as $quiz) {
            DB::transaction(fn () => (new self)->seedQuiz($quiz));
        }

        return Question::query()
            ->whereIn('quiz_id', $quizzes->modelKeys())
            ->orderBy('quiz_id')
            ->orderBy('position')
            ->get();
    }

    /**
     * Give one quiz its full question set, unless it already has questions.
     */
    private function seedQuiz(Quiz $quiz): void
    {
        if ($quiz->questions()->exists()) {
            return;
        }

        foreach ($this->fixture($quiz) as $position => $fixture) {
            $question = Question::query()->create([
                'quiz_id' => $quiz->getKey(),
                'type' => QuestionType::SingleChoice,
                'text' => $fixture['text'],
                'explanation' => $fixture['explanation'],
                'position' => $position,
            ]);

            foreach ($fixture['options'] as $optionPosition => $text) {
                Option::query()->create([
                    'question_id' => $question->getKey(),
                    'text' => $text,
                    'is_correct' => $optionPosition === $fixture['correct'],
                    'position' => $optionPosition,
                ]);
            }
        }
    }

    /**
     * The question fixtures written for the document the given quiz was generated from.
     *
     * @return array<int, array{text: string, correct: int, options: array<int, string>, explanation: string}>
     */
    private function fixture(Quiz $quiz): array
    {
        $key = SourceSeeder::keyFor($quiz->source()->firstOrFail());
        $path = __DIR__."/fixtures/{$key}.php";

        if (! is_file($path)) {
            throw new RuntimeException("No question fixture is defined for source \"{$key}\".");
        }

        /** @var array<int, array{text: string, correct: int, options: array<int, string>, explanation: string}> $questions */
        $questions = require $path;

        return $questions;
    }
}
