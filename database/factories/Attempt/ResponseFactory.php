<?php

declare(strict_types=1);

namespace Database\Factories\Attempt;

use App\Domains\Attempt\Models\Attempt;
use App\Domains\Attempt\Models\Response;
use App\Domains\Question\Models\Option;
use App\Domains\Question\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Response>
 */
class ResponseFactory extends Factory
{
    /** @var class-string<Response> */
    protected $model = Response::class;

    /**
     * Define the model's default state.
     *
     * A response starts out served but unanswered: the question was shown, and no
     * option, verdict or answering moment exists yet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attempt_id' => Attempt::factory(),
            'question_id' => Question::factory(),
            'option_id' => null,
            'position' => 0,
            'is_correct' => null,
            'answered_in_milliseconds' => null,
            'served_at' => now(),
            'answered_at' => null,
        ];
    }

    /**
     * Number the served questions of an attempt consecutively.
     *
     * A whole batch is built before any of it is stored, so the position has to
     * combine the rows already in the table with this model's place in the batch.
     */
    public function configure(): static
    {
        return $this->sequence(function (Sequence $sequence): array {
            $offset = $sequence->index;

            return [
                'position' => fn (array $attributes) => Response::query()
                    ->where('attempt_id', $attributes['attempt_id'])
                    ->count() + $offset,
            ];
        });
    }

    /**
     * A response the user has picked an option for.
     */
    public function answered(): static
    {
        return $this->state(fn (array $attributes) => [
            'option_id' => fn (array $attributes) => $this->optionFor($attributes['question_id']),
            'is_correct' => fn (array $attributes) => (bool) Option::query()
                ->whereKey($attributes['option_id'])
                ->value('is_correct'),
            'answered_in_milliseconds' => fake()->numberBetween(1_200, 45_000),
            'answered_at' => now(),
        ]);
    }

    /**
     * Pick one of the question's options, creating one if it has none.
     */
    private function optionFor(mixed $questionId): int
    {
        $option = Option::query()
            ->where('question_id', $questionId)
            ->inRandomOrder()
            ->first();

        $option ??= Option::factory()->create(['question_id' => $questionId]);

        return (int) $option->getKey();
    }
}
