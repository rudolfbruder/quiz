<?php

declare(strict_types=1);

use App\Domains\Question\Models\Question;
use App\Domains\Review\Models\ReviewState;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Carbon\CarbonInterface;

it('returns only the states due at or before the given moment', function (): void {
    $moment = now();

    $due = ReviewState::factory()->create(['due_at' => $moment->copy()->subDay()]);
    $exactly = ReviewState::factory()->create(['due_at' => $moment]);
    ReviewState::factory()->create(['due_at' => $moment->copy()->addDay()]);

    $keys = ReviewState::query()->dueAt($moment)->pluck('id')->all();

    expect($keys)->toHaveCount(2)
        ->and($keys)->toContain($due->getKey(), $exactly->getKey());
});

it('is due the stability in days after the last review', function (): void {
    $state = ReviewState::factory()->withStability(12.0)->create()->fresh();

    expect($state->stability)->toBe(12.0)
        ->and($state->due_at->equalTo($state->last_reviewed_at->copy()->addDays(12)))->toBeTrue();
});

it('casts stability and difficulty to floats and both moments to datetimes', function (): void {
    $state = ReviewState::factory()->create()->fresh();

    expect($state->stability)->toBeFloat()
        ->and($state->difficulty)->toBeFloat()
        ->and($state->due_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($state->last_reviewed_at)->toBeInstanceOf(CarbonInterface::class);
});

it('resolves its user, question and topic relations', function (): void {
    $state = ReviewState::factory()->create();

    expect($state->user)->toBeInstanceOf(User::class)
        ->and($state->question)->toBeInstanceOf(Question::class)
        ->and($state->topic)->toBeInstanceOf(Topic::class)
        ->and($state->topic_id)->toBe($state->question->quiz->topic_id);
});
