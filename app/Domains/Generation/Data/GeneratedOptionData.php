<?php

declare(strict_types=1);

namespace App\Domains\Generation\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One answer option as the model returned it, before anything is stored.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class GeneratedOptionData extends Data
{
    public function __construct(
        public readonly string $text,
        public readonly bool $isCorrect,
    ) {}
}
