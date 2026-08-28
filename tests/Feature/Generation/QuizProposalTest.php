<?php

declare(strict_types=1);

use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Source\Models\Source;
use Carbon\CarbonInterface;

it('round-trips an outline of sections through the array cast', function (): void {
    $outline = [
        ['title' => 'Foundations', 'focus' => 'The core terms the rest of the material builds on'],
        ['title' => 'Turning points', 'focus' => 'The events that changed the direction of the story'],
    ];

    $proposal = QuizProposal::factory()->create(['outline' => $outline]);

    expect($proposal->fresh()->outline)->toBe($outline);
});

it('generates a multi-section outline with a title and a focus per section', function (): void {
    $proposal = QuizProposal::factory()->create()->fresh();

    expect($proposal->outline)->toBeArray()
        ->and(count($proposal->outline))->toBeGreaterThanOrEqual(3)
        ->and($proposal->suggested_topic_name)->not->toBeNull();

    foreach ($proposal->outline as $section) {
        expect($section)->toHaveKeys(['title', 'focus'])
            ->and($section['title'])->not->toBe('')
            ->and($section['focus'])->not->toBe('');
    }
});

it('is unaccepted until the accepted state is applied', function (): void {
    expect(QuizProposal::factory()->create()->accepted_at)->toBeNull()
        ->and(QuizProposal::factory()->accepted()->create()->accepted_at)
        ->toBeInstanceOf(CarbonInterface::class);
});

it('gives each proposal of a source a distinct position', function (): void {
    $source = Source::factory()->create();

    $positions = QuizProposal::factory()
        ->count(3)
        ->create(['source_id' => $source->getKey()])
        ->pluck('position')
        ->all();

    expect($positions)->toBe([0, 1, 2]);
});

it('resolves its source and quiz relations', function (): void {
    $quiz = Quiz::factory()->create();
    $proposal = $quiz->proposal;

    expect($proposal->source)->toBeInstanceOf(Source::class)
        ->and($proposal->quiz->getKey())->toBe($quiz->getKey());
});
