<?php

declare(strict_types=1);

namespace App\Domains\Source\Data;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

/**
 * The upload boundary: the one document a person is handing the application.
 *
 * Every limit is read from `config/generation.php` so the accepted types and the
 * size ceiling are stated once, here, and nowhere else.
 */
class StoreSourceData extends Data
{
    public function __construct(
        public readonly UploadedFile $file,
    ) {}

    /**
     * The validation rules for an upload.
     *
     * The extension and the reported media type are both checked: a document is
     * refused before it is stored rather than accepted and found unreadable later.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        /** @var list<string> $extensions */
        $extensions = config('generation.accepted_extensions', []);

        /** @var list<string> $mediaTypes */
        $mediaTypes = config('generation.accepted_media_types', []);

        return [
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', $extensions),
                'mimetypes:'.implode(',', $mediaTypes),
                'max:'.(int) config('generation.max_upload_kilobytes'),
            ],
        ];
    }
}
