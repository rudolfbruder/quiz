<?php

declare(strict_types=1);

use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Topic\Data\TopicSummaryData;
use App\Domains\Topic\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('totals the quizzes and questions a topic holds', function (): void {
    $topic = Topic::factory()->create(['name' => 'Roman Republic', 'slug' => 'roman-republic']);

    $first = Quiz::factory()->create(['topic_id' => $topic->id, 'user_id' => $topic->user_id]);
    $second = Quiz::factory()->create(['topic_id' => $topic->id, 'user_id' => $topic->user_id]);

    Question::factory()->count(4)->create(['quiz_id' => $first->id]);
    Question::factory()->count(6)->create(['quiz_id' => $second->id]);

    $data = TopicSummaryData::fromModel($topic);

    expect($data->id)->toBe($topic->id)
        ->and($data->name)->toBe('Roman Republic')
        ->and($data->slug)->toBe('roman-republic')
        ->and($data->quizCount)->toBe(2)
        ->and($data->questionCount)->toBe(10);
});

it('reports nothing held for a topic without quizzes', function (): void {
    $data = TopicSummaryData::fromModel(Topic::factory()->create());

    expect($data->quizCount)->toBe(0)
        ->and($data->questionCount)->toBe(0);
});
