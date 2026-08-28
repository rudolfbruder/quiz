<?php

declare(strict_types=1);

use App\Domains\Generation\Data\GeneratedOptionData;
use App\Domains\Generation\Data\GeneratedOutlineSectionData;
use App\Domains\Generation\Data\GeneratedQuestionData;
use App\Domains\Generation\Data\GeneratedQuizData;
use App\Domains\Generation\Exceptions\UnusableGeneratedQuizException;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Tests\TestCase;

uses(TestCase::class);

/**
 * A payload in the shape the agent's schema declares, written the way a real
 * answer about the Punic Wars would come back. Each test bends one part of it.
 *
 * @param  array<int, array<string, mixed>>  $questions
 * @return array<string, mixed>
 */
function generatedQuizPayload(array $questions = []): array
{
    return [
        'topic_name' => 'Roman Republic',
        'title' => 'War, Reform and Collapse',
        'language' => 'English',
        'page_count' => 18,
        'outline' => [
            ['title' => 'The wars with Carthage', 'focus' => 'The three Punic Wars and their consequences.'],
            ['title' => 'The Gracchi', 'focus' => 'Land reform and the first political violence.'],
        ],
        'questions' => $questions === [] ? [generatedQuestionPayload()] : $questions,
    ];
}

/**
 * @param  array<int, array{text: string, is_correct: bool}>|null  $options
 * @return array<string, mixed>
 */
function generatedQuestionPayload(?array $options = null, ?string $explanation = null): array
{
    return [
        'text' => 'Control of which island was at stake when the First Punic War broke out?',
        'explanation' => $explanation ?? 'The war began over Messana in north-eastern Sicily and was fought largely for the island, which became Rome\'s first province in 241 BC.',
        'options' => $options ?? [
            ['text' => 'Sardinia', 'is_correct' => false],
            ['text' => 'Corsica', 'is_correct' => false],
            ['text' => 'Sicily', 'is_correct' => true],
            ['text' => 'Malta', 'is_correct' => false],
        ],
    ];
}

/**
 * @param  array<string, mixed>  $payload
 */
function structuredAgentResponse(array $payload): StructuredAgentResponse
{
    return new StructuredAgentResponse(
        invocationId: 'test-invocation',
        structured: $payload,
        text: json_encode($payload, JSON_THROW_ON_ERROR),
        usage: new Usage(0, 0, 0),
        meta: new Meta('openai', 'gpt-5.4'),
    );
}

it('hydrates a realistic payload into typed objects', function (): void {
    $quiz = GeneratedQuizData::hydrate(generatedQuizPayload());

    expect($quiz->topicName)->toBe('Roman Republic')
        ->and($quiz->title)->toBe('War, Reform and Collapse')
        ->and($quiz->language)->toBe('English')
        ->and($quiz->pageCount)->toBe(18)
        ->and($quiz->outline)->toHaveCount(2)
        ->and($quiz->outline[0])->toBeInstanceOf(GeneratedOutlineSectionData::class)
        ->and($quiz->outline[0]->title)->toBe('The wars with Carthage')
        ->and($quiz->outline[1]->focus)->toBe('Land reform and the first political violence.')
        ->and($quiz->questions)->toHaveCount(1)
        ->and($quiz->questions[0])->toBeInstanceOf(GeneratedQuestionData::class)
        ->and($quiz->questions[0]->options)->toHaveCount(4)
        ->and($quiz->questions[0]->options[2])->toBeInstanceOf(GeneratedOptionData::class)
        ->and($quiz->questions[0]->options[2]->text)->toBe('Sicily')
        ->and($quiz->questions[0]->options[2]->isCorrect)->toBeTrue()
        ->and($quiz->questions[0]->options[0]->isCorrect)->toBeFalse()
        ->and($quiz->questions[0]->correctOptions())->toHaveCount(1);
});

it('hydrates the agent response object itself', function (): void {
    $quiz = GeneratedQuizData::hydrate(
        structuredAgentResponse(generatedQuizPayload())
    );

    expect($quiz->pageCount)->toBe(18)
        ->and($quiz->questions)->toHaveCount(1);
});

it('rejects a run that came back with no questions at all', function (): void {
    expect(fn () => GeneratedQuizData::hydrate(
        [...generatedQuizPayload(), 'questions' => []]
    ))->toThrow(UnusableGeneratedQuizException::class, 'no questions at all');
});

it('rejects a question offering fewer than two options', function (): void {
    expect(fn () => GeneratedQuizData::hydrate(generatedQuizPayload([
        generatedQuestionPayload([
            ['text' => 'Sicily', 'is_correct' => true],
        ]),
    ])))->toThrow(UnusableGeneratedQuizException::class, 'Question 1 carries 1 option, and every question needs at least 2.');
});

it('rejects a question with no correct option', function (): void {
    expect(fn () => GeneratedQuizData::hydrate(generatedQuizPayload([
        generatedQuestionPayload([
            ['text' => 'Sardinia', 'is_correct' => false],
            ['text' => 'Sicily', 'is_correct' => false],
        ]),
    ])))->toThrow(UnusableGeneratedQuizException::class, 'Question 1 has no option marked correct.');
});

it('rejects a question with more than one correct option', function (): void {
    expect(fn () => GeneratedQuizData::hydrate(generatedQuizPayload([
        generatedQuestionPayload(),
        generatedQuestionPayload([
            ['text' => 'Sardinia', 'is_correct' => true],
            ['text' => 'Sicily', 'is_correct' => true],
            ['text' => 'Malta', 'is_correct' => false],
        ]),
    ])))->toThrow(UnusableGeneratedQuizException::class, 'Question 2 has 2 options marked correct, and exactly one may be.');
});

it('rejects a question repeating the same option text', function (): void {
    expect(fn () => GeneratedQuizData::hydrate(generatedQuizPayload([
        generatedQuestionPayload([
            ['text' => 'Sicily', 'is_correct' => true],
            ['text' => 'Sardinia', 'is_correct' => false],
            ['text' => '  sicily ', 'is_correct' => false],
        ]),
    ])))->toThrow(UnusableGeneratedQuizException::class, 'Question 1 repeats the same option text twice: "  sicily ".');
});

it('rejects a question carrying no explanation', function (): void {
    expect(fn () => GeneratedQuizData::hydrate(generatedQuizPayload([
        generatedQuestionPayload(explanation: '   '),
    ])))->toThrow(UnusableGeneratedQuizException::class, 'Question 1 carries no explanation.');
});

it('accepts a question offering the two options that are the minimum', function (): void {
    $quiz = GeneratedQuizData::hydrate(generatedQuizPayload([
        generatedQuestionPayload([
            ['text' => 'Sicily', 'is_correct' => true],
            ['text' => 'Sardinia', 'is_correct' => false],
        ]),
    ]));

    expect($quiz->questions[0]->options)->toHaveCount(2);
});
