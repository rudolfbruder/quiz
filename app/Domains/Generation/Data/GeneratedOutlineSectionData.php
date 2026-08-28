<?php

declare(strict_types=1);

namespace App\Domains\Generation\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One section of the outline the model drew up for a source. The outline is
 * kept because it is what gets written as the source's quiz proposal.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class GeneratedOutlineSectionData extends Data
{
    public function __construct(
        public readonly string $title,
        public readonly string $focus,
    ) {}
}
