<?php

declare(strict_types=1);

use App\Domains\Source\Models\Source;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

it('lets the uploader view and delete their own source', function (): void {
    $source = Source::factory()->create();
    $owner = $source->user;

    expect(Gate::forUser($owner)->allows('view', $source))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('delete', $source))->toBeTrue();
});

it('denies both abilities to anybody else', function (): void {
    $source = Source::factory()->create();
    $stranger = User::factory()->create();

    expect(Gate::forUser($stranger)->denies('view', $source))->toBeTrue()
        ->and(Gate::forUser($stranger)->denies('delete', $source))->toBeTrue();
});

it('refuses a delete request for somebody else\'s source with a 403', function (): void {
    Storage::fake('local');

    $source = Source::factory()->create();
    Storage::disk('local')->put($source->path, 'the document');

    $this->actingAs(User::factory()->create())
        ->delete(route('sources.destroy', $source))
        ->assertForbidden();

    expect(Source::query()->whereKey($source->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($source->path);
});
