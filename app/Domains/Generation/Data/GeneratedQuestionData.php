<?php

declare(strict_types=1);

namespace App\Domains\Generation\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One question as the model returned it, with its options and explanation.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class GeneratedQuestionData extends Data
{
    /**
     * @param  array<int, GeneratedOptionData>  $options
     */
    public function __construct(
        public readonly string $text,
        public readonly string $explanation,
        #[DataCollectionOf(GeneratedOptionData::class)]
        public readonly array $options,
    ) {}

    /**
     * The options marked correct by the model.
     *
     * @return array<int, GeneratedOptionData>
     */
    public function correctOptions(): array
    {
        return array_values(array_filter(
            $this->options,
            fn (GeneratedOptionData $option): bool => $option->isCorrect,
        ));
    }
}
