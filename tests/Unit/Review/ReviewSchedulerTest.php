<?php

declare(strict_types=1);

use App\Domains\Review\Enums\Rating;
use App\Domains\Review\Services\ReviewScheduler;
use Carbon\CarbonImmutable;
use Tests\TestCase;

uses(TestCase::class);

/**
 * The scheduler is pure, so every test supplies its own moment rather than
 * leaning on the clock.
 */
function reviewScheduler(): ReviewScheduler
{
    return app(ReviewScheduler::class);
}

function reviewSchedulerMoment(): CarbonImmutable
{
    return CarbonImmutable::parse('2026-01-01 08:00:00');
}

it('returns one on the recall curve immediately after a review', function (): void {
    expect(reviewScheduler()->retrievability(10.0, 0.0))->toBe(1.0);
});

it('returns the desired retention at an elapsed time equal to the stability', function (): void {
    expect(reviewScheduler()->retrievability(12.0, 12.0))->toEqualWithDelta(0.9, 1e-12)
        ->and(reviewScheduler()->retrievability(0.75, 0.75))->toEqualWithDelta(0.9, 1e-12);
});

it('decays below the desired retention without reaching zero well past the due moment', function (): void {
    $retrievability = reviewScheduler()->retrievability(10.0, 5000.0);

    expect($retrievability)->toBeLessThan(0.9)
        ->and($retrievability)->toBeGreaterThan(0.0);
});

it('assigns the configured initial values on a first review', function (): void {
    $decision = reviewScheduler()->schedule(null, null, null, Rating::Good, reviewSchedulerMoment());

    expect($decision->stabilityAfter)->toBe(3.0)
        ->and($decision->difficultyAfter)->toBe(5.0)
        ->and($decision->scheduledDays)->toEqualWithDelta(3.0, 1e-9)
        ->and($decision->dueAt->equalTo(reviewSchedulerMoment()->addDays(3)))->toBeTrue();
});

it('orders the initial values by grade', function (): void {
    $decisions = array_map(
        fn (Rating $rating) => reviewScheduler()->schedule(null, null, null, $rating, reviewSchedulerMoment()),
        [Rating::Again, Rating::Hard, Rating::Good, Rating::Easy],
    );

    $stabilities = array_map(fn ($decision): float => $decision->stabilityAfter, $decisions);
    $difficulties = array_map(fn ($decision): float => $decision->difficultyAfter, $decisions);

    expect($stabilities)->toBe([0.4, 1.2, 3.0, 6.0])
        ->and($difficulties)->toBe([7.0, 6.0, 5.0, 4.0]);
});

it('reports absent prior state for a first review', function (): void {
    $decision = reviewScheduler()->schedule(null, null, null, Rating::Good, reviewSchedulerMoment());

    expect($decision->stabilityBefore)->toBeNull()
        ->and($decision->difficultyBefore)->toBeNull()
        ->and($decision->elapsedDays)->toBeNull()
        ->and($decision->rating)->toBe(Rating::Good)
        ->and($decision->stabilityAfter)->toBeGreaterThan(0.0)
        ->and($decision->scheduledDays)->toBeGreaterThan(0.0);
});

it('reports the whole transition for a subsequent review', function (): void {
    $lastReviewedAt = reviewSchedulerMoment()->subDays(4);

    $decision = reviewScheduler()->schedule(6.0, 5.0, $lastReviewedAt, Rating::Good, reviewSchedulerMoment());

    expect($decision->stabilityBefore)->toBe(6.0)
        ->and($decision->difficultyBefore)->toBe(5.0)
        ->and($decision->elapsedDays)->toEqualWithDelta(4.0, 1e-9)
        ->and($decision->stabilityAfter)->toBeGreaterThan(6.0)
        ->and($decision->difficultyAfter)->toBe(5.0)
        ->and($decision->scheduledDays)->toEqualWithDelta($decision->stabilityAfter, 1e-9);
});

it('makes a question due exactly its stability in days later at the default retention', function (): void {
    $decision = reviewScheduler()->schedule(9.0, 5.0, reviewSchedulerMoment()->subDays(9), Rating::Good, reviewSchedulerMoment());

    expect($decision->scheduledDays)->toEqualWithDelta($decision->stabilityAfter, 1e-9)
        ->and($decision->dueAt->getTimestamp())
        ->toBe(reviewSchedulerMoment()->addSeconds((int) round($decision->stabilityAfter * 86400))->getTimestamp());
});

it('grows stability along the expected progression under repeated good grades', function (): void {
    $expected = [3.0, 6.6, 12.7, 22.5, 37.1, 57.8];

    $stability = null;
    $difficulty = null;
    $lastReviewedAt = null;
    $reviewedAt = reviewSchedulerMoment();
    $actual = [];

    foreach ($expected as $target) {
        $decision = reviewScheduler()->schedule($stability, $difficulty, $lastReviewedAt, Rating::Good, $reviewedAt);

        $actual[] = $decision->stabilityAfter;

        $stability = $decision->stabilityAfter;
        $difficulty = $decision->difficultyAfter;
        $lastReviewedAt = $reviewedAt;
        $reviewedAt = $decision->dueAt;
    }

    foreach ($expected as $index => $target) {
        expect($actual[$index])->toEqualWithDelta($target, $target * 0.05);
    }
});

it('grants a harder question less stability than an easier one', function (): void {
    $harder = reviewScheduler()->schedule(10.0, 8.0, reviewSchedulerMoment()->subDays(10), Rating::Good, reviewSchedulerMoment());
    $easier = reviewScheduler()->schedule(10.0, 3.0, reviewSchedulerMoment()->subDays(10), Rating::Good, reviewSchedulerMoment());

    expect($harder->stabilityAfter)->toBeLessThan($easier->stabilityAfter);
});

it('grants more stability to a question recalled long after it was due', function (): void {
    $onTime = reviewScheduler()->schedule(10.0, 5.0, reviewSchedulerMoment()->subDays(10), Rating::Good, reviewSchedulerMoment());
    $late = reviewScheduler()->schedule(10.0, 5.0, reviewSchedulerMoment()->subDays(60), Rating::Good, reviewSchedulerMoment());

    expect($late->stabilityAfter)->toBeGreaterThan($onTime->stabilityAfter);
});

it('orders resulting stability from hard through good to easy', function (): void {
    $stabilities = array_map(
        fn (Rating $rating): float => reviewScheduler()
            ->schedule(10.0, 5.0, reviewSchedulerMoment()->subDays(10), $rating, reviewSchedulerMoment())
            ->stabilityAfter,
        [Rating::Hard, Rating::Good, Rating::Easy],
    );

    expect($stabilities[0])->toBeLessThan($stabilities[1])
        ->and($stabilities[1])->toBeLessThan($stabilities[2]);
});

it('collapses a long stable question that is forgotten', function (): void {
    $decision = reviewScheduler()->schedule(22.5, 5.0, reviewSchedulerMoment()->subDays(22), Rating::Again, reviewSchedulerMoment());

    expect($decision->stabilityAfter)->toBeLessThan(2.0)
        ->and($decision->stabilityAfter)->toBeGreaterThanOrEqual(config('review.min_stability_days'))
        ->and($decision->scheduledDays)->toEqualWithDelta($decision->stabilityAfter, 1e-9)
        ->and($decision->dueAt->lessThan(reviewSchedulerMoment()->addDays(2)))->toBeTrue();
});

it('clamps a newly learned question at the minimum stability when it is forgotten', function (): void {
    $minimum = (float) config('review.min_stability_days');

    $decision = reviewScheduler()->schedule($minimum, 5.0, reviewSchedulerMoment()->subDay(), Rating::Again, reviewSchedulerMoment());

    expect($decision->stabilityAfter)->toBe($minimum)
        ->and($decision->scheduledDays)->toBe($minimum);
});

it('never increases stability on a lapse', function (): void {
    foreach ([0.5, 1.0, 3.0, 12.0, 60.0, 400.0] as $stability) {
        $decision = reviewScheduler()->schedule($stability, 5.0, reviewSchedulerMoment()->subDays(1), Rating::Again, reviewSchedulerMoment());

        expect($decision->stabilityAfter)->toBeLessThanOrEqual($stability)
            ->and($decision->stabilityAfter)->toBeGreaterThanOrEqual(min($stability, (float) config('review.min_stability_days')));
    }
});

it('raises difficulty toward but never beyond the maximum under repeated failures', function (): void {
    $difficulty = 5.0;

    for ($i = 0; $i < 100; $i++) {
        $decision = reviewScheduler()->schedule(3.0, $difficulty, reviewSchedulerMoment()->subDays(3), Rating::Again, reviewSchedulerMoment());

        expect($decision->difficultyAfter)->toBeGreaterThanOrEqual($difficulty)
            ->and($decision->difficultyAfter)->toBeLessThanOrEqual((float) config('review.difficulty_max'));

        $difficulty = $decision->difficultyAfter;
    }

    expect($difficulty)->toBeGreaterThan(5.0);
});

it('lowers difficulty toward but never below the minimum under repeated easy grades', function (): void {
    $difficulty = 5.0;

    for ($i = 0; $i < 100; $i++) {
        $decision = reviewScheduler()->schedule(3.0, $difficulty, reviewSchedulerMoment()->subDays(3), Rating::Easy, reviewSchedulerMoment());

        expect($decision->difficultyAfter)->toBeLessThanOrEqual($difficulty)
            ->and($decision->difficultyAfter)->toBeGreaterThanOrEqual((float) config('review.difficulty_min'));

        $difficulty = $decision->difficultyAfter;
    }

    expect($difficulty)->toBeLessThan(5.0);
});

it('holds difficulty at the midpoint under a good grade', function (): void {
    $midpoint = (float) config('review.difficulty_midpoint');

    $decision = reviewScheduler()->schedule(10.0, $midpoint, reviewSchedulerMoment()->subDays(10), Rating::Good, reviewSchedulerMoment());

    expect($decision->difficultyAfter)->toEqualWithDelta($midpoint, 1e-12);
});

it('pulls an extreme difficulty measurably back toward the midpoint', function (): void {
    $midpoint = (float) config('review.difficulty_midpoint');

    $high = reviewScheduler()->schedule(10.0, 10.0, reviewSchedulerMoment()->subDays(10), Rating::Good, reviewSchedulerMoment());
    $low = reviewScheduler()->schedule(10.0, 1.0, reviewSchedulerMoment()->subDays(10), Rating::Good, reviewSchedulerMoment());

    expect($high->difficultyAfter)->toBeLessThan(10.0)
        ->and($high->difficultyAfter)->toBeGreaterThan($midpoint)
        ->and($low->difficultyAfter)->toBeGreaterThan(1.0)
        ->and($low->difficultyAfter)->toBeLessThan($midpoint);
});

it('caps the granted interval while keeping the true stability', function (): void {
    $maximum = (float) config('review.max_interval_days');

    $decision = reviewScheduler()->schedule(2000.0, 5.0, reviewSchedulerMoment()->subDays(2000), Rating::Good, reviewSchedulerMoment());

    expect($decision->stabilityAfter)->toBeGreaterThan($maximum)
        ->and($decision->scheduledDays)->toBe($maximum)
        ->and($decision->dueAt->equalTo(reviewSchedulerMoment()->addDays(365)))->toBeTrue();
});

it('shortens the granted interval when the desired retention is raised', function (): void {
    $default = reviewScheduler()->schedule(20.0, 5.0, reviewSchedulerMoment()->subDays(20), Rating::Good, reviewSchedulerMoment());

    config()->set('review.desired_retention', 0.95);

    $stricter = reviewScheduler()->schedule(20.0, 5.0, reviewSchedulerMoment()->subDays(20), Rating::Good, reviewSchedulerMoment());

    expect($stricter->stabilityAfter)->toEqualWithDelta($default->stabilityAfter, 1e-9)
        ->and($stricter->scheduledDays)->toBeLessThan($stricter->stabilityAfter)
        ->and($stricter->scheduledDays)->toBeLessThan($default->scheduledDays);
});

it('lengthens the granted interval when the desired retention is lowered', function (): void {
    $default = reviewScheduler()->schedule(20.0, 5.0, reviewSchedulerMoment()->subDays(20), Rating::Good, reviewSchedulerMoment());

    config()->set('review.desired_retention', 0.8);

    $looser = reviewScheduler()->schedule(20.0, 5.0, reviewSchedulerMoment()->subDays(20), Rating::Good, reviewSchedulerMoment());

    expect($looser->stabilityAfter)->toEqualWithDelta($default->stabilityAfter, 1e-9)
        ->and($looser->scheduledDays)->toBeGreaterThan($looser->stabilityAfter)
        ->and($looser->scheduledDays)->toBeGreaterThan($default->scheduledDays);
});

it('produces identical results for identical inputs', function (): void {
    $arguments = [7.5, 6.25, reviewSchedulerMoment()->subDays(9), Rating::Hard, reviewSchedulerMoment()];

    $first = reviewScheduler()->schedule(...$arguments);
    $second = reviewScheduler()->schedule(...$arguments);

    expect($second->stabilityAfter)->toBe($first->stabilityAfter)
        ->and($second->difficultyAfter)->toBe($first->difficultyAfter)
        ->and($second->scheduledDays)->toBe($first->scheduledDays)
        ->and($second->dueAt->equalTo($first->dueAt))->toBeTrue();
});

it('computes the due moment from the supplied moment rather than the present', function (): void {
    $reviewedAt = CarbonImmutable::parse('2020-03-15 12:00:00');

    $decision = reviewScheduler()->schedule(null, null, null, Rating::Good, $reviewedAt);

    expect($decision->dueAt->equalTo($reviewedAt->addDays(3)))->toBeTrue()
        ->and($decision->dueAt->isPast())->toBeTrue();
});

it('keeps fractional days when converting an interval into a due moment', function (): void {
    $decision = reviewScheduler()->schedule(null, null, null, Rating::Hard, reviewSchedulerMoment());

    expect($decision->scheduledDays)->toEqualWithDelta(1.2, 1e-9)
        ->and($decision->dueAt->getTimestamp())
        ->toBe(reviewSchedulerMoment()->addSeconds((int) round(1.2 * 86400))->getTimestamp());
});

it('treats a review taken before the previous one as zero elapsed days', function (): void {
    $decision = reviewScheduler()->schedule(10.0, 5.0, reviewSchedulerMoment()->addDays(3), Rating::Good, reviewSchedulerMoment());

    expect($decision->elapsedDays)->toBe(0.0)
        ->and($decision->stabilityAfter)->toBeGreaterThan(10.0);
});

it('raises the lapse count by exactly one when a long stable question is forgotten', function (): void {
    $decision = reviewScheduler()->schedule(
        22.5,
        5.0,
        reviewSchedulerMoment()->subDays(22),
        Rating::Again,
        reviewSchedulerMoment(),
        reps: 7,
        lapses: 2,
    );

    expect($decision->lapsesAfter)->toBe(3)
        ->and($decision->repsAfter)->toBe(8)
        ->and($decision->stabilityAfter)->toBeLessThan(2.0);
});

it('counts a successful review without touching the lapse count', function (): void {
    foreach ([Rating::Hard, Rating::Good, Rating::Easy] as $rating) {
        $decision = reviewScheduler()->schedule(
            10.0,
            5.0,
            reviewSchedulerMoment()->subDays(10),
            $rating,
            reviewSchedulerMoment(),
            reps: 4,
            lapses: 1,
        );

        expect($decision->repsAfter)->toBe(5)
            ->and($decision->lapsesAfter)->toBe(1);
    }
});

it('starts the counters from zero on a first review', function (): void {
    $recalled = reviewScheduler()->schedule(null, null, null, Rating::Good, reviewSchedulerMoment());
    $forgotten = reviewScheduler()->schedule(null, null, null, Rating::Again, reviewSchedulerMoment());

    expect($recalled->repsAfter)->toBe(1)
        ->and($recalled->lapsesAfter)->toBe(0)
        ->and($forgotten->repsAfter)->toBe(1)
        ->and($forgotten->lapsesAfter)->toBe(1);
});

it('accumulates the counters across a run of reviews', function (): void {
    $reps = 0;
    $lapses = 0;
    $stability = null;
    $difficulty = null;
    $lastReviewedAt = null;
    $reviewedAt = reviewSchedulerMoment();

    foreach ([Rating::Good, Rating::Again, Rating::Good, Rating::Again, Rating::Easy] as $rating) {
        $decision = reviewScheduler()->schedule(
            $stability,
            $difficulty,
            $lastReviewedAt,
            $rating,
            $reviewedAt,
            reps: $reps,
            lapses: $lapses,
        );

        $reps = $decision->repsAfter;
        $lapses = $decision->lapsesAfter;
        $stability = $decision->stabilityAfter;
        $difficulty = $decision->difficultyAfter;
        $lastReviewedAt = $reviewedAt;
        $reviewedAt = $decision->dueAt;
    }

    expect($reps)->toBe(5)
        ->and($lapses)->toBe(2);
});
