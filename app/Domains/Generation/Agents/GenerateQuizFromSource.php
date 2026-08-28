<?php

declare(strict_types=1);

namespace App\Domains\Generation\Agents;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Turns one stored document into an outline and a full set of questions.
 *
 * The document is attached to the prompt rather than extracted into it: the
 * provider reads the file, and the application never parses one. The disk and
 * path of the stored file are the only per-run inputs; everything else about
 * how the model is called is fixed by the attributes on this class.
 *
 * This agent answers in a declared schema and nothing else. Its output is
 * hydrated into `GeneratedQuizData` and checked there before any of it reaches
 * the database — a schema-valid answer can still be a useless one.
 */
#[Provider(Lab::OpenAI)]
#[Model('gpt-5.4')]
#[Temperature(0.4)]
#[MaxTokens(32000)]
#[Timeout(240)]
final class GenerateQuizFromSource implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly Repository $config,
        private readonly string $disk,
        private readonly string $path,
    ) {}

    /**
     * Prompt the model with the stored document attached.
     */
    public function generate(): AgentResponse
    {
        return $this->prompt($this->task(), attachments: [
            Files\Document::fromStorage($this->path, disk: $this->disk),
        ]);
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<'PROMPT'
        You write multiple-choice study questions from the single document attached to the prompt.

        Work only from the attached document:

        - Every question, option and explanation must be answerable from that document alone. Never
          add material from your own knowledge and never invent a fact the document does not state.
        - Write in the language the document itself is written in, and report that language.
        - Never mention "the document", "the text", "the author" or a page number inside a question.

        Every question must hold together on its own:

        - Give each question exactly one correct option. Never mark two options correct, and never
          leave a question without a correct option.
        - Give each question at least four options, every one of them different from the others.
        - Write distractors a reader who went through the material once could genuinely believe.
          Draw them from the same document and keep them at the same length and level of detail as
          the correct option. An option that is obviously absurd, or conspicuously longer than the
          rest, gives the answer away and teaches nothing.
        - Write an explanation for every question saying why the correct option is right and, where
          it helps, why the most tempting distractor is wrong.

        Report the attached document's true page count in `page_count`: how many pages the document
        actually has, counted from the document itself. Never estimate the page count from the
        amount of text, and never report the number of outline sections or questions in its place.
        PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topic_name' => $schema->string()
                ->description('The subject area this document belongs to, as a person would name it.')
                ->required(),
            'title' => $schema->string()
                ->description('A title for the quiz built from this document.')
                ->required(),
            'language' => $schema->string()
                ->description('The language the document is written in, named in English.')
                ->required(),
            'page_count' => $schema->integer()
                ->min(1)
                ->description('The number of pages the attached document actually has.')
                ->required(),
            'outline' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'title' => $schema->string()->required(),
                    'focus' => $schema->string()
                        ->description('What this section of the material covers.')
                        ->required(),
                ])
            )->required(),
            'questions' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'text' => $schema->string()->required(),
                    'explanation' => $schema->string()->required(),
                    'options' => $schema->array()->items(
                        $schema->object(fn (JsonSchema $schema): array => [
                            'text' => $schema->string()->required(),
                            'is_correct' => $schema->boolean()->required(),
                        ])
                    )->required(),
                ])
            )->required(),
        ];
    }

    /**
     * The prompt sent alongside the attached document.
     *
     * The number of questions is not a property of this run but of the product:
     * it follows the outline the model draws up, at the configured number of
     * questions per section.
     */
    public function task(): string
    {
        return sprintf(
            'Read the attached document, divide it into the sections a study outline would have, '
            .'and write %d questions for each of those sections.',
            $this->questionsPerSection(),
        );
    }

    /**
     * How many questions each section of the outline is expanded into.
     */
    public function questionsPerSection(): int
    {
        return $this->config->integer('generation.questions_per_section');
    }
}
