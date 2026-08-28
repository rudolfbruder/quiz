<?php

declare(strict_types=1);

use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Enums\QuizStatus;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Source\Models\Source;
use App\Domains\Topic\Models\Topic;
use App\Models\User;

it('casts the status column to the quiz status enum', function (): void {
    $quiz = Quiz::factory()->create()->fresh();

    expect($quiz->status)->toBe(QuizStatus::Ready)
        ->and(Quiz::factory()->failed()->create()->fresh()->status)->toBe(QuizStatus::Failed)
        ->and(Quiz::factory()->pending()->create()->fresh()->status)->toBe(QuizStatus::Pending);
});

it('resolves its user, topic, source, proposal and question relations', function (): void {
    $quiz = Quiz::factory()->create();
    Question::factory()->count(2)->create(['quiz_id' => $quiz->getKey()]);

    expect($quiz->user)->toBeInstanceOf(User::class)
        ->and($quiz->topic)->toBeInstanceOf(Topic::class)
        ->and($quiz->source)->toBeInstanceOf(Source::class)
        ->and($quiz->proposal)->toBeInstanceOf(QuizProposal::class)
        ->and($quiz->questions)->toHaveCount(2);
});

it('shares one owner across the quiz, its source and its topic', function (): void {
    $quiz = Quiz::factory()->create();

    expect($quiz->source->user_id)->toBe($quiz->user_id)
        ->and($quiz->topic->user_id)->toBe($quiz->user_id)
        ->and($quiz->proposal->source_id)->toBe($quiz->source_id);
});
