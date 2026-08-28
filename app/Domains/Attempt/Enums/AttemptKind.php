<?php

declare(strict_types=1);

namespace App\Domains\Attempt\Enums;

enum AttemptKind: string
{
    case Daily = 'daily';
    case Practice = 'practice';
}
