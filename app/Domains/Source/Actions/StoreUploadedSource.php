<?php

declare(strict_types=1);

namespace App\Domains\Source\Actions;

use App\Domains\Generation\Jobs\GenerateQuiz;
use App\Domains\Source\Data\StoreSourceData;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use App\Models\User;

/**
 * Keep an accepted upload and record where it was kept.
 *
 * The row is written as `pending` and generation is queued behind it: the request
 * that uploaded the document never waits on a model call.
 */
class StoreUploadedSource
{
    /**
     * Store the uploaded file and create the source row for it.
     */
    public function __invoke(User $user, StoreSourceData $data): Source
    {
        $disk = (string) config('filesystems.default');

        // Both are read from the temporary upload before it is moved off it.
        $originalName = $data->file->getClientOriginalName();
        $mediaType = $data->file->getMimeType();
        $sizeBytes = $data->file->getSize();

        $path = $data->file->store('sources', $disk);

        $source = Source::query()->create([
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $originalName,
            'media_type' => $mediaType,
            'size_bytes' => $sizeBytes,
            'status' => SourceStatus::Pending,
        ]);

        dispatch(GenerateQuiz::forSource($source));

        return $source;
    }
}
