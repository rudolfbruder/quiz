<?php

declare(strict_types=1);

namespace Database\Factories\Review;

use App\Domains\Question\Models\Question;
use App\Domains\Review\Enums\Rating;
use App\Domains\Review\Models\ReviewLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewLog>
 */
class ReviewLogFactory extends Factory
{
    /** @var class-string<ReviewLog> */
    protected $model = ReviewLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stabilityBefore = fake()->randomFloat(2, 1, 20);
        $scheduledDays = fake()->randomFloat(2, 1, 40);

        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'attempt_id' => null,
            'response_id' => null,
            'rating' => Rating::Good,
            'stability_before' => $stabilityBefore,
            'difficulty_before' => fake()->randomFloat(2, 1, 10),
            'stability_after' => $scheduledDays,
            'difficulty_after' => fake()->randomFloat(2, 1, 10),
            'elapsed_days' => fake()->randomFloat(2, 0, 30),
            'scheduled_days' => $scheduledDays,
            'answered_in_milliseconds' => fake()->numberBetween(1_200, 45_000),
            'reviewed_at' => now(),
        ];
    }

    /**
     * The very first review of a question, which has no prior state to record.
     */
    public function first(): static
    {
        return $this->state(fn (array $attributes) => [
            'stability_before' => null,
            'difficulty_before' => null,
            'elapsed_days' => null,
        ]);
    }
}
