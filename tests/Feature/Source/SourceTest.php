<?php

declare(strict_types=1);

use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Carbon\CarbonInterface;

it('casts the status column to the source status enum', function (): void {
    $source = Source::factory()->create();

    expect($source->fresh()->status)->toBe(SourceStatus::Pending);
});

it('casts the analysed moment to a datetime', function (): void {
    $source = Source::factory()->ready()->create();

    expect($source->fresh()->analysed_at)->toBeInstanceOf(CarbonInterface::class);
});

it('resolves its user, topic and proposal relations', function (): void {
    $topic = Topic::factory()->create();
    $source = Source::factory()->create(['user_id' => $topic->user_id, 'topic_id' => $topic->getKey()]);
    QuizProposal::factory()->count(3)->create(['source_id' => $source->getKey()]);

    expect($source->user)->toBeInstanceOf(User::class)
        ->and($source->topic->getKey())->toBe($topic->getKey())
        ->and($source->proposals)->toHaveCount(3);
});

it('starts out pending with no page count and no failure reason', function (): void {
    $source = Source::factory()->create();

    expect($source->status)->toBe(SourceStatus::Pending)
        ->and($source->page_count)->toBeNull()
        ->and($source->failure_reason)->toBeNull();
});

it('records a plausible page count once it is ready', function (): void {
    $source = Source::factory()->ready()->create();

    expect($source->status)->toBe(SourceStatus::Ready)
        ->and($source->page_count)->toBeGreaterThan(0)
        ->and($source->page_count)->toBeLessThanOrEqual(config('generation.max_pages'))
        ->and($source->failure_reason)->toBeNull()
        ->and($source->analysed_at)->not->toBeNull();
});

it('records a reason when analysis failed', function (): void {
    $source = Source::factory()->failed()->create();

    expect($source->status)->toBe(SourceStatus::Failed)
        ->and($source->failure_reason)->not->toBeNull();
});

it('names the real page count when the document is rejected for being too long', function (): void {
    $limit = (int) config('generation.max_pages');
    $source = Source::factory()->rejected()->create();

    expect($source->status)->toBe(SourceStatus::Rejected)
        ->and($source->page_count)->toBeGreaterThan($limit)
        ->and($source->failure_reason)->toContain((string) $source->page_count);
});
