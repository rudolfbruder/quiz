<?php

declare(strict_types=1);

namespace Database\Factories\Quiz;

use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Quiz\Enums\QuizStatus;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Source\Models\Source;
use App\Domains\Topic\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /** @var class-string<Quiz> */
    protected $model = Quiz::class;

    /**
     * Define the model's default state.
     *
     * The proposal is created first and everything else is derived from it, so a quiz
     * always shares its source's owner and its topic belongs to that same user.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_proposal_id' => QuizProposal::factory(),
            'source_id' => fn (array $attributes) => $this->sourceIdFor(Arr::integer($attributes, 'quiz_proposal_id')),
            'user_id' => fn (array $attributes) => $this->ownerOf(Arr::integer($attributes, 'source_id')),
            'topic_id' => fn (array $attributes) => Topic::factory()
                ->create(['user_id' => Arr::integer($attributes, 'user_id')])
                ->getKey(),
            'title' => Str::title(rtrim(fake()->sentence(4), '.')),
            'status' => QuizStatus::Ready,
            'failure_reason' => null,
            'generated_at' => now(),
        ];
    }

    /**
     * The source the given proposal was derived from.
     */
    private function sourceIdFor(int $proposalId): int
    {
        return QuizProposal::query()->findOrFail($proposalId)->source_id;
    }

    /**
     * The user who uploaded the given source.
     */
    private function ownerOf(int $sourceId): int
    {
        return Source::query()->findOrFail($sourceId)->user_id;
    }

    /**
     * A quiz whose questions have not been generated yet.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuizStatus::Pending,
            'generated_at' => null,
        ]);
    }

    /**
     * A quiz whose generation broke.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuizStatus::Failed,
            'failure_reason' => 'Question generation could not be completed.',
            'generated_at' => null,
        ]);
    }
}
