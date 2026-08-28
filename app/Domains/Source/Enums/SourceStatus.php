<?php

declare(strict_types=1);

namespace App\Domains\Source\Enums;

enum SourceStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
    case Rejected = 'rejected';
}
