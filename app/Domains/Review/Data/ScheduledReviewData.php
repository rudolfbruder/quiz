<?php

declare(strict_types=1);

namespace App\Domains\Review\Data;

use App\Domains\Review\Enums\Rating;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * One scheduling decision, carrying everything the caller has to persist.
 *
 * The properties fall into two groups. `rating`, `stabilityBefore`,
 * `difficultyBefore`, `stabilityAfter`, `difficultyAfter`, `elapsedDays` and
 * `scheduledDays` map one to one onto the `review_logs` columns and record what
 * happened. `repsAfter` and `lapsesAfter` are not logged: they are the running
 * counters the caller writes back to the question's `review_states` row,
 * alongside the resulting stability, difficulty and due moment.
 *
 * The before-values are null for a first review, where no prior state existed,
 * rather than being fabricated from the initial values the grade establishes.
 */
class ScheduledReviewData extends Data
{
    public function __construct(
        public readonly Rating $rating,
        public readonly ?float $stabilityBefore,
        public readonly ?float $difficultyBefore,
        public readonly float $stabilityAfter,
        public readonly float $difficultyAfter,
        public readonly ?float $elapsedDays,
        public readonly float $scheduledDays,
        public readonly CarbonImmutable $dueAt,
        public readonly int $repsAfter,
        public readonly int $lapsesAfter,
    ) {}
}
