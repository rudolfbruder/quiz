<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Desired retention
    |--------------------------------------------------------------------------
    |
    | The probability that a question is still recalled at the moment it comes
    | due. Stability is defined against this value: at the default of 0.9 a
    | question's interval equals its stability in days. Raising it shortens
    | granted intervals; lowering it lengthens them.
    |
    */

    'desired_retention' => 0.9,

    /*
    |--------------------------------------------------------------------------
    | Forgetting curve decay
    |--------------------------------------------------------------------------
    |
    | The exponent of the power-law forgetting curve. The companion factor is
    | always derived from this value and the desired retention, never stored,
    | so that recall probability and stability cannot drift apart.
    |
    */

    'decay' => 0.5,

    /*
    |--------------------------------------------------------------------------
    | Daily quiz size
    |--------------------------------------------------------------------------
    */

    'daily_question_count' => 5,

    /*
    |--------------------------------------------------------------------------
    | Interval bounds, in days
    |--------------------------------------------------------------------------
    */

    'max_interval_days' => 365.0,
    'min_stability_days' => 0.5,

    /*
    |--------------------------------------------------------------------------
    | First review, keyed by grade
    |--------------------------------------------------------------------------
    |
    | A better grade yields a longer initial stability and a lower initial
    | difficulty. Keys are the integer values of the Rating enum.
    |
    */

    'initial_stability' => [
        1 => 0.4,
        2 => 1.2,
        3 => 3.0,
        4 => 6.0,
    ],

    'initial_difficulty' => [
        1 => 7.0,
        2 => 6.0,
        3 => 5.0,
        4 => 4.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stability growth on a successful review
    |--------------------------------------------------------------------------
    |
    | grade_factor scales the increase by how the review was graded. growth_rate
    | is the overall size of the increase, and stability_brake is the exponent
    | that makes an already stable question gain proportionally less.
    |
    */

    'grade_factor' => [
        2 => 0.6,
        3 => 1.0,
        4 => 1.4,
    ],

    'growth_rate' => 2.5,
    'stability_brake' => 0.3,

    /*
    |--------------------------------------------------------------------------
    | Difficulty
    |--------------------------------------------------------------------------
    |
    | Difficulty is bounded between min and max and is drawn back toward the
    | midpoint after every adjustment, so a run of unusual grades cannot pin a
    | question at an extreme.
    |
    */

    'difficulty_min' => 1.0,
    'difficulty_max' => 10.0,
    'difficulty_midpoint' => 5.0,
    'difficulty_step' => 0.8,
    'difficulty_reversion' => 0.1,

    /*
    |--------------------------------------------------------------------------
    | Lapse
    |--------------------------------------------------------------------------
    |
    | A forgotten question collapses to lapse_factor times its stability raised
    | to lapse_exponent, floored at min_stability_days.
    |
    */

    'lapse_factor' => 0.5,
    'lapse_exponent' => 0.4,

    /*
    |--------------------------------------------------------------------------
    | Fast answer threshold, in milliseconds
    |--------------------------------------------------------------------------
    |
    | An answer returned faster than this is a candidate for a better grade.
    | The mapping from answer speed to grade belongs to quiz taking.
    |
    */

    'fast_answer_ms' => 5000,

];
