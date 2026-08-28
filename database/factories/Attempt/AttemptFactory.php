<?php

declare(strict_types=1);

namespace Database\Factories\Attempt;

use App\Domains\Attempt\Enums\AttemptKind;
use App\Domains\Attempt\Models\Attempt;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    /** @var class-string<Attempt> */
    protected $model = Attempt::class;

    /**
     * Define the model's default state.
     *
     * Daily attempts are unique per user, topic and served date, so each further
     * attempt for the same pairing is dated a day earlier than the last.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'topic_id' => fn (array $attributes) => Topic::factory()
                ->create(['user_id' => $attributes['user_id']])
                ->getKey(),
            'quiz_id' => null,
            'kind' => AttemptKind::Daily,
            'served_on' => today(),
            'question_count' => (int) config('review.daily_question_count'),
            'correct_count' => null,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    /**
     * Date each daily attempt a day earlier than the last one for the same pairing,
     * since a user holds at most one daily attempt per topic and date.
     *
     * A whole batch is built before any of it is stored, so the date has to combine
     * the rows already in the table with this model's place in the batch.
     */
    public function configure(): static
    {
        return $this->sequence(function (Sequence $sequence): array {
            $offset = $sequence->index;

            return [
                'served_on' => fn (array $attributes) => today()->subDays(
                    Attempt::query()
                        ->where('user_id', $attributes['user_id'])
                        ->where('topic_id', $attributes['topic_id'])
                        ->count() + $offset
                ),
            ];
        });
    }

    /**
     * The daily quiz served for one date.
     */
    public function daily(?CarbonInterface $servedOn = null): static
    {
        return $this->state(fn (array $attributes) => $servedOn === null
            ? ['kind' => AttemptKind::Daily]
            : ['kind' => AttemptKind::Daily, 'served_on' => $servedOn->toDateString()]);
    }

    /**
     * A self-directed run through one quiz, outside the daily schedule.
     */
    public function practice(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => AttemptKind::Practice,
            'quiz_id' => fn (array $attributes) => Quiz::factory()
                ->create([
                    'user_id' => $attributes['user_id'],
                    'topic_id' => $attributes['topic_id'],
                ])
                ->getKey(),
            'served_on' => null,
        ]);
    }

    /**
     * An attempt the user has worked all the way through.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'correct_count' => fake()->numberBetween(0, (int) $attributes['question_count']),
            'completed_at' => now(),
        ]);
    }
}
