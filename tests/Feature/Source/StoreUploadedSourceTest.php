<?php

declare(strict_types=1);

use App\Domains\Source\Actions\StoreUploadedSource;
use App\Domains\Source\Data\StoreSourceData;
use App\Domains\Source\Enums\SourceStatus;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/*
 * Storing an upload queues its generation. The queue is faked throughout so that
 * these tests stay about the file and the row they write.
 */

it('stores the file and records where it was kept', function (): void {
    Storage::fake('local');
    Queue::fake();

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('roman-republic.pdf', 120, 'application/pdf');

    $source = app(StoreUploadedSource::class)($user, new StoreSourceData($file));

    Storage::disk($source->disk)->assertExists($source->path);

    expect($source->user_id)->toBe($user->id)
        ->and($source->disk)->toBe('local')
        ->and($source->original_name)->toBe('roman-republic.pdf')
        ->and($source->media_type)->toBe('application/pdf')
        ->and($source->size_bytes)->toBe(120 * 1024)
        ->and($source->status)->toBe(SourceStatus::Pending)
        ->and($source->page_count)->toBeNull()
        ->and($source->failure_reason)->toBeNull()
        ->and($source->analysed_at)->toBeNull();
});

it('files uploads under the sources folder', function (): void {
    Storage::fake('local');
    Queue::fake();

    $source = app(StoreUploadedSource::class)(
        User::factory()->create(),
        new StoreSourceData(UploadedFile::fake()->create('notes.pdf', 8, 'application/pdf')),
    );

    expect($source->path)->toStartWith('sources/');
});
