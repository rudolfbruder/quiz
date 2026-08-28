<?php

declare(strict_types=1);

use App\Domains\Question\Enums\QuestionType;
use App\Domains\Question\Models\Option;
use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Models\Quiz;

it('casts the type column to the question type enum', function (): void {
    expect(Question::factory()->create()->fresh()->type)->toBe(QuestionType::SingleChoice);
});

it('gives every generated question exactly one correct option and no repeated text', function (): void {
    $questions = Question::factory()->count(5)->create();

    foreach ($questions as $question) {
        $options = $question->options;
        $texts = $options->pluck('text')->all();

        expect($options)->toHaveCount(4)
            ->and($options->where('is_correct', true))->toHaveCount(1)
            ->and(array_unique($texts))->toHaveCount(count($texts));
    }
});

it('casts the correctness flag on an option to a boolean', function (): void {
    $option = Option::factory()->correct()->create()->fresh();

    expect($option->is_correct)->toBeTrue()
        ->and($option->question)->toBeInstanceOf(Question::class);
});

it('returns the questions of a quiz in a stable order', function (): void {
    $quiz = Quiz::factory()->create();
    Question::factory()->count(4)->create(['quiz_id' => $quiz->getKey()]);

    $first = $quiz->questions()->pluck('id')->all();
    $second = $quiz->questions()->pluck('id')->all();

    expect($second)->toBe($first)
        ->and($quiz->questions()->pluck('position')->all())->toBe([0, 1, 2, 3]);
});
