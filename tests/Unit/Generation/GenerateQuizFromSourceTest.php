<?php

declare(strict_types=1);

use App\Domains\Generation\Agents\GenerateQuizFromSource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Files\StoredDocument;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

uses(TestCase::class);

/**
 * No test here reaches a provider: the agent is faked before every prompt, and
 * the document it attaches lives on a faked disk.
 */
function generationSourceDisk(): string
{
    return 'local';
}

function generationSourcePath(): string
{
    return 'sources/the-punic-wars.pdf';
}

function generateQuizFromSource(): GenerateQuizFromSource
{
    Storage::fake(generationSourceDisk())->put(generationSourcePath(), 'A stored document.');

    return GenerateQuizFromSource::make(
        disk: generationSourceDisk(),
        path: generationSourcePath(),
    );
}

it('resolves from the container in its own domain namespace', function (): void {
    expect(generateQuizFromSource())->toBeInstanceOf(GenerateQuizFromSource::class)
        ->and(generateQuizFromSource())->toBeInstanceOf(Agent::class)
        ->and(generateQuizFromSource())->toBeInstanceOf(HasStructuredOutput::class);
});

it('states the page count and the single correct option rule in its instructions', function (): void {
    $instructions = Str::lower((string) generateQuizFromSource()->instructions());

    expect($instructions)->toContain('page_count')
        ->and($instructions)->toContain('page count')
        ->and($instructions)->toContain('exactly one correct option')
        ->and($instructions)->toContain('never mark two options correct');
});

it('asks for the configured number of questions per outline section', function (): void {
    config()->set('generation.questions_per_section', 6);

    expect(generateQuizFromSource()->questionsPerSection())->toBe(6)
        ->and(generateQuizFromSource()->task())->toContain('6 questions for each');
});

it('carries its provider, model and generation limits as attributes', function (): void {
    $reflection = new ReflectionClass(GenerateQuizFromSource::class);

    $attributes = [Provider::class, Model::class, Temperature::class, MaxTokens::class, Timeout::class];

    foreach ($attributes as $attribute) {
        expect($reflection->getAttributes($attribute))->not->toBeEmpty(
            $attribute.' is not declared on the agent.'
        );
    }
});

it('returns every field the schema declares required when faked', function (): void {
    GenerateQuizFromSource::fake();

    $response = generateQuizFromSource()->generate();

    expect($response['topic_name'])->toBeString()
        ->and($response['title'])->toBeString()
        ->and($response['language'])->toBeString()
        ->and($response['page_count'])->toBeInt()
        ->and($response['outline'])->toBeArray()->not->toBeEmpty()
        ->and($response['questions'])->toBeArray()->not->toBeEmpty();

    foreach ($response['outline'] as $section) {
        expect($section)->toHaveKeys(['title', 'focus']);
    }

    foreach ($response['questions'] as $question) {
        expect($question)->toHaveKeys(['text', 'explanation', 'options']);

        foreach ($question['options'] as $option) {
            expect($option)->toHaveKeys(['text', 'is_correct']);
        }
    }
});

it('attaches the stored document rather than pasting its text into the prompt', function (): void {
    GenerateQuizFromSource::fake();

    generateQuizFromSource()->generate();

    GenerateQuizFromSource::assertPrompted(function (AgentPrompt $prompt): bool {
        $attachment = $prompt->attachments->sole();

        return $attachment instanceof StoredDocument
            && $attachment->path === generationSourcePath()
            && $attachment->disk === generationSourceDisk();
    });

    GenerateQuizFromSource::assertPrompted(
        fn (AgentPrompt $prompt): bool => ! str_contains($prompt->prompt, 'A stored document.')
    );
});
