<?php

declare(strict_types=1);

namespace Database\Factories\Question;

use App\Domains\Question\Models\Option;
use App\Domains\Question\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Option>
 */
class OptionFactory extends Factory
{
    /** @var class-string<Option> */
    protected $model = Option::class;

    /**
     * Define the model's default state.
     *
     * The text is drawn from a unique pool so that no question ends up with two
     * options reading the same.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'text' => rtrim(fake()->unique()->sentence(5), '.'),
            'is_correct' => false,
        ];
    }

    /**
     * Number the options of a question consecutively.
     *
     * A whole batch is built before any of it is stored, so the position has to
     * combine the rows already in the table with this model's place in the batch.
     */
    public function configure(): static
    {
        return $this->sequence(function (Sequence $sequence): array {
            $offset = $sequence->index;

            return [
                'position' => fn (array $attributes) => Option::query()
                    ->where('question_id', $attributes['question_id'])
                    ->count() + $offset,
            ];
        });
    }

    /**
     * The option that answers the question.
     */
    public function correct(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_correct' => true,
        ]);
    }
}
