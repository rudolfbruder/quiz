<?php

declare(strict_types=1);

namespace App\Domains\Review\Services;

use App\Domains\Review\Data\ScheduledReviewData;
use App\Domains\Review\Enums\Rating;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Config\Repository;
use RuntimeException;

/**
 * Decides when a question should next be shown, from the grade a user gave it
 * and the review state it already carried.
 *
 * The service is pure. It never reads the clock, the database or a model: every
 * moment it works with is supplied by the caller, so the same inputs always
 * yield the same stability, difficulty and due moment.
 */
class ReviewScheduler
{
    /**
     * The retention the stability scale is defined against.
     *
     * This is structural rather than tunable. A stability of twelve days means
     * "recall has fallen to 90 per cent twelve days after the review", and that
     * definition is what anchors the forgetting curve. The desired retention is
     * the separate knob applied on top of it: raising it above this anchor
     * grants an interval shorter than the stability, lowering it grants a
     * longer one, and stability keeps its meaning either way.
     */
    private const STABILITY_RETENTION = 0.9;

    /**
     * The grade the difficulty adjustment is centred on. `Good` is neutral: it
     * leaves difficulty untouched, worse grades raise it, better grades lower
     * it. Part of the formula, not a setting.
     */
    private const NEUTRAL_GRADE = 3;

    /**
     * Difficulty is turned into a growth multiplier as `(11 - D) / 10`, which
     * maps the 1..10 difficulty band onto 1.0..0.1 without ever reaching zero.
     * Part of the formula, not a setting.
     */
    private const DIFFICULTY_INVERSION_BASE = 11.0;

    private const DIFFICULTY_INVERSION_SCALE = 10.0;

    public function __construct(private readonly Repository $config) {}

    /**
     * Schedule the next review of a question.
     *
     * A null stability, difficulty or last review moment means the question has
     * no prior review state for this user and is therefore new, in which case
     * the prior counters are zero. The counters trail the other prior-state
     * arguments only because they carry defaults and PHP requires optional
     * parameters last.
     */
    public function schedule(
        ?float $stability,
        ?float $difficulty,
        ?CarbonInterface $lastReviewedAt,
        Rating $rating,
        CarbonInterface $reviewedAt,
        int $reps = 0,
        int $lapses = 0,
    ): ScheduledReviewData {
        $isFirstReview = $stability === null || $difficulty === null || $lastReviewedAt === null;

        if ($isFirstReview) {
            $elapsedDays = null;
            $stabilityAfter = $this->initialStability($rating);
            $difficultyAfter = $this->initialDifficulty($rating);
        } else {
            $elapsedDays = $this->elapsedDays($lastReviewedAt, $reviewedAt);
            $difficultyAfter = $this->nextDifficulty($difficulty, $rating);
            $stabilityAfter = $rating->isRecalled()
                ? $this->stabilityOnRecall($stability, $difficultyAfter, $elapsedDays, $rating)
                : $this->stabilityOnLapse($stability);
        }

        $scheduledDays = $this->interval($stabilityAfter);

        return new ScheduledReviewData(
            rating: $rating,
            stabilityBefore: $isFirstReview ? null : $stability,
            difficultyBefore: $isFirstReview ? null : $difficulty,
            stabilityAfter: $stabilityAfter,
            difficultyAfter: $difficultyAfter,
            elapsedDays: $elapsedDays,
            scheduledDays: $scheduledDays,
            dueAt: CarbonImmutable::instance($reviewedAt)->addSeconds((int) round($scheduledDays * 86400)),
            repsAfter: $reps + 1,
            lapsesAfter: $rating->isRecalled() ? $lapses : $lapses + 1,
        );
    }

    /**
     * The probability that a question with the given stability is still
     * recalled after the given number of days.
     *
     * The curve starts at certainty, passes exactly through the stability
     * retention at an elapsed time equal to the stability, and never reaches
     * zero.
     */
    public function retrievability(float $stability, float $elapsedDays): float
    {
        $decay = $this->float('decay');
        $stability = max($stability, $this->float('min_stability_days'));
        $elapsedDays = max(0.0, $elapsedDays);

        return (1 + $this->factor() * $elapsedDays / $stability) ** -$decay;
    }

    /**
     * The interval, in days, granted for a question at the given stability.
     *
     * At the default desired retention this collapses to the stability itself.
     * The result is bounded, while the stability it was derived from keeps its
     * true value.
     */
    private function interval(float $stability): float
    {
        $decay = $this->float('decay');
        $desiredRetention = $this->float('desired_retention');

        $days = $stability / $this->factor() * ($desiredRetention ** (-1 / $decay) - 1);

        return max(
            $this->float('min_stability_days'),
            min($this->float('max_interval_days'), $days),
        );
    }

    /**
     * The companion factor of the forgetting curve, derived from the decay and
     * the stability retention so the two can never drift apart.
     */
    private function factor(): float
    {
        return self::STABILITY_RETENTION ** (-1 / $this->float('decay')) - 1;
    }

    /**
     * Move difficulty by the grade given, then draw it back toward the midpoint
     * so a run of unusual grades cannot pin a question at an extreme.
     */
    private function nextDifficulty(float $difficulty, Rating $rating): float
    {
        $difficulty -= $this->float('difficulty_step') * ($rating->value - self::NEUTRAL_GRADE);
        $difficulty += $this->float('difficulty_reversion') * ($this->float('difficulty_midpoint') - $difficulty);

        return max(
            $this->float('difficulty_min'),
            min($this->float('difficulty_max'), $difficulty),
        );
    }

    /**
     * Grow stability after a correct answer, moderated by how difficult the
     * question is, how stable it already was, and how far recall had decayed by
     * the time it was reviewed.
     */
    private function stabilityOnRecall(float $stability, float $difficulty, float $elapsedDays, Rating $rating): float
    {
        $retrievability = $this->retrievability($stability, $elapsedDays);

        $growth = $this->float('growth_rate')
            * $this->gradeFactor($rating)
            * ((self::DIFFICULTY_INVERSION_BASE - $difficulty) / self::DIFFICULTY_INVERSION_SCALE)
            * (2 - $retrievability)
            * $stability ** -$this->float('stability_brake');

        return $stability * (1 + $growth);
    }

    /**
     * Collapse stability after a wrong answer. The result is floored at the
     * configured minimum and can never come out above the stability the
     * question had before the lapse.
     */
    private function stabilityOnLapse(float $stability): float
    {
        $collapsed = max(
            $this->float('min_stability_days'),
            $this->float('lapse_factor') * $stability ** $this->float('lapse_exponent'),
        );

        return min($stability, $collapsed);
    }

    /**
     * Real time between the two moments, in days, floored at zero.
     */
    private function elapsedDays(CarbonInterface $lastReviewedAt, CarbonInterface $reviewedAt): float
    {
        return max(0.0, (float) $lastReviewedAt->diffInSeconds($reviewedAt, false)) / 86400;
    }

    private function initialStability(Rating $rating): float
    {
        return $this->float("initial_stability.{$rating->value}");
    }

    private function initialDifficulty(Rating $rating): float
    {
        return $this->float("initial_difficulty.{$rating->value}");
    }

    private function gradeFactor(Rating $rating): float
    {
        return $this->float("grade_factor.{$rating->value}");
    }

    private function float(string $key): float
    {
        $value = $this->config->get("review.{$key}");

        if (! is_numeric($value)) {
            throw new RuntimeException("Review configuration [review.{$key}] is missing or is not numeric.");
        }

        return (float) $value;
    }
}
