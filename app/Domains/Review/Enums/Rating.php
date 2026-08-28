<?php

declare(strict_types=1);

namespace App\Domains\Review\Enums;

enum Rating: int
{
    case Again = 1;
    case Hard = 2;
    case Good = 3;
    case Easy = 4;

    /**
     * Whether this grade counts the question as recalled.
     */
    public function isRecalled(): bool
    {
        return $this !== self::Again;
    }
}
