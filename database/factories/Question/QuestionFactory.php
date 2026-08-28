<?php

declare(strict_types=1);

namespace Database\Factories\Question;

use App\Domains\Question\Enums\QuestionType;
use App\Domains\Question\Models\Option;
use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * How many options a generated question is offered.
     */
    private const OPTION_COUNT = 4;

    /** @var class-string<Question> */
    protected $model = Question::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'type' => QuestionType::SingleChoice,
            'text' => rtrim(fake()->unique()->sentence(8), '.').'?',
            'explanation' => fake()->paragraph(2),
        ];
    }

    /**
     * Number the questions of a quiz consecutively, and give every generated question
     * a full set of options with exactly one of them correct.
     *
     * A whole batch is built before any of it is stored, so the position has to
     * combine the rows already in the table with this model's place in the batch.
     */
    public function configure(): static
    {
        return $this->sequence(function (Sequence $sequence): array {
            $offset = $sequence->index;

            return [
                'position' => fn (array $attributes) => Question::query()
                    ->where('quiz_id', $attributes['quiz_id'])
                    ->count() + $offset,
            ];
        })->afterCreating(function (Question $question): void {
            if ($question->options()->exists()) {
                return;
            }

            $correctPosition = fake()->numberBetween(0, self::OPTION_COUNT - 1);

            foreach (range(0, self::OPTION_COUNT - 1) as $position) {
                Option::factory()->create([
                    'question_id' => $question->getKey(),
                    'is_correct' => $position === $correctPosition,
                    'position' => $position,
                ]);
            }
        });
    }
}
