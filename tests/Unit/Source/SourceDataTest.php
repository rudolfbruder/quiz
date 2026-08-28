<?php

declare(strict_types=1);

use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Source\Data\SourceData;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('carries the quiz title and question count of a ready source', function (): void {
    $source = Source::factory()->ready()->create(['original_name' => 'roman-republic.pdf']);
    $quiz = Quiz::factory()->create([
        'source_id' => $source->id,
        'title' => 'The Roman Republic',
    ]);
    Question::factory()->count(7)->create(['quiz_id' => $quiz->id]);

    $data = SourceData::fromModel($source->fresh());

    expect($data->originalName)->toBe('roman-republic.pdf')
        ->and($data->status)->toBe(SourceStatus::Ready)
        ->and($data->quizTitle)->toBe('The Roman Republic')
        ->and($data->questionCount)->toBe(7)
        ->and($data->pageCount)->toBeGreaterThan(0)
        ->and($data->failureReason)->toBeNull()
        ->and($data->uploadedAt)->not->toBeNull();
});

it('leaves the quiz fields empty while a source has produced nothing', function (): void {
    $data = SourceData::fromModel(Source::factory()->create());

    expect($data->status)->toBe(SourceStatus::Pending)
        ->and($data->quizTitle)->toBeNull()
        ->and($data->questionCount)->toBeNull()
        ->and($data->pageCount)->toBeNull();
});

it('prefers a question count already loaded onto the quiz', function (): void {
    $source = Source::factory()->ready()->create();
    $quiz = Quiz::factory()->create(['source_id' => $source->id]);
    Question::factory()->count(3)->create(['quiz_id' => $quiz->id]);

    $loaded = Source::query()
        ->with(['quiz' => fn ($query) => $query->withCount('questions')])
        ->findOrFail($source->id);

    $data = SourceData::fromModel($loaded);

    expect($data->questionCount)->toBe(3);
});
