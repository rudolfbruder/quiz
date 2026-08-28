<?php

declare(strict_types=1);

use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('redirects an anonymous upload to the login page and stores nothing', function (): void {
    Storage::fake('local');

    $this->post(route('sources.store'), [
        'file' => UploadedFile::fake()->create('lecture.pdf', 40, 'application/pdf'),
    ])->assertRedirect(route('login'));

    expect(Source::query()->count())->toBe(0);
});

it('accepts a supported document from a signed-in person', function (): void {
    Storage::fake('local');
    Queue::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('sources.store'), [
            'file' => UploadedFile::fake()->create('lecture.pdf', 40, 'application/pdf'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $source = Source::query()->sole();

    expect($source->user_id)->toBe($user->id)
        ->and($source->status)->toBe(SourceStatus::Pending);

    Storage::disk($source->disk)->assertExists($source->path);
});

it('refuses an unsupported document type and stores nothing', function (): void {
    Storage::fake('local');

    $this->actingAs(User::factory()->create())
        ->post(route('sources.store'), [
            'file' => UploadedFile::fake()->create(
                'lecture.docx',
                40,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ),
        ])
        ->assertSessionHasErrors('file');

    expect(Source::query()->count())->toBe(0);
});

it('lets the uploader delete their own source', function (): void {
    Storage::fake('local');

    $source = Source::factory()->ready()->create();
    Storage::disk('local')->put($source->path, 'the document');

    $this->actingAs($source->user)
        ->delete(route('sources.destroy', $source))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    Storage::disk('local')->assertMissing($source->path);

    expect(Source::query()->count())->toBe(0);
});

it('refuses to delete a source that is being analysed, naming the state', function (): void {
    Storage::fake('local');

    $source = Source::factory()->create(['status' => SourceStatus::Processing]);
    Storage::disk('local')->put($source->path, 'the document');

    $this->actingAs($source->user)
        ->delete(route('sources.destroy', $source))
        ->assertSessionHasErrors(['source' => 'This document cannot be deleted while it is processing.']);

    Storage::disk('local')->assertExists($source->path);

    expect(Source::query()->whereKey($source->id)->exists())->toBeTrue();
});
