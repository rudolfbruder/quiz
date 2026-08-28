<?php

declare(strict_types=1);

namespace App\Domains\Source\Data;

use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * One uploaded document as the sources panel lists it.
 *
 * The quiz title and the question count are derived rather than stored: they are
 * present once generation has produced a quiz, and null until then.
 */
class SourceData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $originalName,
        public readonly string $mediaType,
        public readonly int $sizeBytes,
        public readonly SourceStatus $status,
        public readonly ?int $pageCount,
        public readonly ?string $failureReason,
        public readonly ?CarbonImmutable $uploadedAt,
        public readonly ?string $quizTitle,
        public readonly ?int $questionCount,
    ) {}

    /**
     * Build the panel's view of one source.
     *
     * A `questions_count` loaded onto the quiz by the caller is preferred over
     * counting again, so a listing can eager load its counts rather than paying for
     * one query per row.
     */
    public static function fromModel(Source $source): self
    {
        $quiz = $source->quiz;

        return new self(
            id: $source->id,
            originalName: $source->original_name,
            mediaType: $source->media_type,
            sizeBytes: $source->size_bytes,
            status: $source->status,
            pageCount: $source->page_count,
            failureReason: $source->failure_reason,
            uploadedAt: $source->created_at,
            quizTitle: $quiz?->title,
            questionCount: $quiz === null
                ? null
                : (int) ($quiz->getAttribute('questions_count') ?? $quiz->questions()->count()),
        );
    }
}
