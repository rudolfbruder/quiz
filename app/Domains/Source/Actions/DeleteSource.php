<?php

declare(strict_types=1);

namespace App\Domains\Source\Actions;

use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Remove a source, its stored file and everything generated from it.
 *
 * The quiz, its questions and their options go with the row: the schema cascades
 * from `sources`, so deleting the source is the whole removal.
 */
class DeleteSource
{
    /**
     * Delete the source, refusing while its analysis is still running.
     *
     * @throws ValidationException
     */
    public function __invoke(Source $source): void
    {
        if ($source->status === SourceStatus::Processing) {
            throw ValidationException::withMessages([
                'source' => __('This document cannot be deleted while it is :status.', [
                    'status' => $source->status->value,
                ]),
            ]);
        }

        $disk = $source->disk;
        $path = $source->path;

        $source->delete();

        Storage::disk($disk)->delete($path);
    }
}
