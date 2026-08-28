<?php

declare(strict_types=1);

use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Question\Models\Option;
use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Source\Actions\DeleteSource;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

it('removes the stored file, the row and everything generated from it', function (): void {
    Storage::fake('local');

    $source = Source::factory()->ready()->create();
    Storage::disk('local')->put($source->path, 'the document');

    $proposal = QuizProposal::factory()->create(['source_id' => $source->id]);
    $quiz = Quiz::factory()->create(['quiz_proposal_id' => $proposal->id]);
    Question::factory()->count(3)->create(['quiz_id' => $quiz->id]);

    expect(Option::query()->count())->toBeGreaterThan(0);

    app(DeleteSource::class)($source);

    Storage::disk('local')->assertMissing($source->path);

    expect(Source::query()->whereKey($source->id)->exists())->toBeFalse()
        ->and(QuizProposal::query()->count())->toBe(0)
        ->and(Quiz::query()->count())->toBe(0)
        ->and(Question::query()->count())->toBe(0)
        ->and(Option::query()->count())->toBe(0);
});

it('removes a failed source and leaves nothing orphaned', function (): void {
    Storage::fake('local');

    $source = Source::factory()->failed()->create();
    Storage::disk('local')->put($source->path, 'the document');

    app(DeleteSource::class)($source);

    Storage::disk('local')->assertMissing($source->path);

    expect(Source::query()->count())->toBe(0);
});

it('refuses to delete a source while it is being analysed', function (): void {
    Storage::fake('local');

    $source = Source::factory()->create(['status' => SourceStatus::Processing]);
    Storage::disk('local')->put($source->path, 'the document');

    expect(fn () => app(DeleteSource::class)($source))
        ->toThrow(ValidationException::class, 'This document cannot be deleted while it is processing.');

    Storage::disk('local')->assertExists($source->path);

    expect(Source::query()->whereKey($source->id)->exists())->toBeTrue();
});
