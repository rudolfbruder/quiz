<?php

declare(strict_types=1);

use App\Domains\Source\Data\StoreSourceData;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

it('accepts a small pdf', function (): void {
    $data = StoreSourceData::validateAndCreate([
        'file' => UploadedFile::fake()->create('lecture.pdf', 120, 'application/pdf'),
    ]);

    expect($data->file->getClientOriginalName())->toBe('lecture.pdf');
});

it('refuses a document type it cannot read', function (): void {
    StoreSourceData::validate([
        'file' => UploadedFile::fake()->create(
            'lecture.docx',
            120,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ),
    ]);
})->throws(ValidationException::class);

it('refuses a document above the size limit', function (): void {
    $limit = (int) config('generation.max_upload_kilobytes');

    StoreSourceData::validate([
        'file' => UploadedFile::fake()->create('lecture.pdf', $limit + 1, 'application/pdf'),
    ]);
})->throws(ValidationException::class);

it('draws its rules from the generation configuration', function (): void {
    config([
        'generation.accepted_extensions' => ['txt'],
        'generation.accepted_media_types' => ['text/plain'],
        'generation.max_upload_kilobytes' => 64,
    ]);

    expect(StoreSourceData::rules()['file'])
        ->toContain('mimes:txt')
        ->toContain('mimetypes:text/plain')
        ->toContain('max:64');
});
