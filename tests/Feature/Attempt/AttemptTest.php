<?php

declare(strict_types=1);

use App\Domains\Attempt\Enums\AttemptKind;
use App\Domains\Attempt\Models\Attempt;
use App\Domains\Attempt\Models\Response;
use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Carbon\CarbonInterface;

it('records a served question before it is answered', function (): void {
    $attempt = Attempt::factory()->create();
    $response = Response::factory()->create(['attempt_id' => $attempt->getKey()]);

    $stored = $response->fresh();

    expect($stored->option_id)->toBeNull()
        ->and($stored->is_correct)->toBeNull()
        ->and($stored->answered_at)->toBeNull()
        ->and($stored->answered_in_milliseconds)->toBeNull()
        ->and($stored->served_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($attempt->responses)->toHaveCount(1);
});

it('casts the kind, the served date and both moments', function (): void {
    $attempt = Attempt::factory()->create()->fresh();

    expect($attempt->kind)->toBe(AttemptKind::Daily)
        ->and($attempt->served_on)->toBeInstanceOf(CarbonInterface::class)
        ->and($attempt->served_on->toDateString())->toBe(today()->toDateString())
        ->and($attempt->started_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($attempt->completed_at)->toBeNull();
});

it('resolves its user, topic, quiz and response relations', function (): void {
    $attempt = Attempt::factory()->practice()->create();
    Response::factory()->count(3)->create(['attempt_id' => $attempt->getKey()]);

    expect($attempt->user)->toBeInstanceOf(User::class)
        ->and($attempt->topic)->toBeInstanceOf(Topic::class)
        ->and($attempt->quiz)->toBeInstanceOf(Quiz::class)
        ->and($attempt->responses)->toHaveCount(3);
});

it('builds a practice attempt against a quiz in the same topic', function (): void {
    $attempt = Attempt::factory()->practice()->create();

    expect($attempt->kind)->toBe(AttemptKind::Practice)
        ->and($attempt->served_on)->toBeNull()
        ->and($attempt->quiz->topic_id)->toBe($attempt->topic_id)
        ->and($attempt->quiz->user_id)->toBe($attempt->user_id);
});

it('dates repeated daily attempts for one pairing so they do not collide', function (): void {
    $topic = Topic::factory()->create();

    $dates = Attempt::factory()
        ->count(3)
        ->daily()
        ->create(['user_id' => $topic->user_id, 'topic_id' => $topic->getKey()])
        ->map(fn (Attempt $attempt): string => $attempt->served_on->toDateString())
        ->all();

    expect(array_unique($dates))->toHaveCount(3);
});

it('scores an attempt once it is completed', function (): void {
    $attempt = Attempt::factory()->completed()->create()->fresh();

    expect($attempt->completed_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($attempt->correct_count)->toBeLessThanOrEqual($attempt->question_count);
});

it('fills a response in once the question is answered', function (): void {
    $question = Question::factory()->create();
    $response = Response::factory()->answered()->create(['question_id' => $question->getKey()])->fresh();

    expect($response->option_id)->not->toBeNull()
        ->and($response->is_correct)->toBe($response->option->is_correct)
        ->and($response->answered_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($response->answered_in_milliseconds)->toBeGreaterThan(0);
});
