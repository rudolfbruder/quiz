<?php

declare(strict_types=1);

namespace Database\Factories\Review;

use App\Domains\Question\Models\Question;
use App\Domains\Review\Models\ReviewState;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<ReviewState>
 */
class ReviewStateFactory extends Factory
{
    /**
     * Seconds in one day, used to turn a stability in days into a due date.
     */
    private const SECONDS_PER_DAY = 86_400;

    /** @var class-string<ReviewState> */
    protected $model = ReviewState::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'topic_id' => fn (array $attributes) => $this->topicFor(Arr::integer($attributes, 'question_id')),
            'difficulty' => fake()->randomFloat(2, 1, 10),
            'reps' => fake()->numberBetween(1, 12),
            'lapses' => fake()->numberBetween(0, 3),
            ...$this->schedule(fake()->randomFloat(2, 1, 30), fake()->numberBetween(0, 20)),
        ];
    }

    /**
     * A state whose memory stability is the given number of days, and which therefore
     * comes due that many days after its last review.
     */
    public function withStability(float $days): static
    {
        return $this->state(fn (array $attributes) => $this->schedule($days, fake()->numberBetween(0, 20)));
    }

    /**
     * The last review and the due date it grants, kept consistent with the stability.
     *
     * @return array<string, mixed>
     */
    private function schedule(float $stability, int $daysSinceLastReview): array
    {
        $lastReviewedAt = now()->subDays($daysSinceLastReview);

        return [
            'stability' => $stability,
            'last_reviewed_at' => $lastReviewedAt,
            'due_at' => $lastReviewedAt->copy()->addSeconds((int) round($stability * self::SECONDS_PER_DAY)),
        ];
    }

    /**
     * The topic the given question belongs to, taken from its quiz.
     */
    private function topicFor(int $questionId): int
    {
        return Question::query()->findOrFail($questionId)->quiz()->firstOrFail()->topic_id;
    }
}
