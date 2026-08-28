<?php

declare(strict_types=1);

namespace App\Domains\Source\Data;

use Spatie\LaravelData\Data;

/**
 * The limits the upload form has to state out loud.
 *
 * They are read from `config/generation.php` here so the interface repeats the
 * same numbers the validation rules enforce, rather than a second copy of them
 * that can drift.
 */
class UploadLimitsData extends Data
{
    /**
     * @param  list<string>  $acceptedExtensions
     * @param  list<string>  $acceptedMediaTypes
     */
    public function __construct(
        public readonly array $acceptedExtensions,
        public readonly array $acceptedMediaTypes,
        public readonly int $maxUploadKilobytes,
        public readonly int $maxPages,
    ) {}

    /**
     * Read the configured limits.
     */
    public static function fromConfig(): self
    {
        /** @var list<string> $extensions */
        $extensions = config('generation.accepted_extensions', []);

        /** @var list<string> $mediaTypes */
        $mediaTypes = config('generation.accepted_media_types', []);

        return new self(
            acceptedExtensions: $extensions,
            acceptedMediaTypes: $mediaTypes,
            maxUploadKilobytes: (int) config('generation.max_upload_kilobytes'),
            maxPages: (int) config('generation.max_pages'),
        );
    }
}
