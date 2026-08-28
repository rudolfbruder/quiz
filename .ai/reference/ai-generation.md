# Reference: AI question generation with the Laravel AI SDK

Full detail behind `.ai/guidelines/35-ai-generation`. Read this before writing or changing any agent, AI job, or
structured-output schema. Activate the `ai-sdk-development` skill and search the docs — the SDK is new.

## Where AI code lives

Agents live inside the domain they serve — there is no top-level `app/Ai/`. See `.ai/20-architecture`.

```
app/Domains/Quiz/Agents/ProposeQuizOutlines.php     3 candidate outlines for a source
app/Domains/Quiz/Agents/GenerateQuizQuestions.php   full question set for an accepted proposal
app/Domains/Source/Jobs/AnalyzeSource.php           queued: upload -> proposals
app/Domains/Quiz/Jobs/GenerateQuiz.php              queued: accepted proposal -> questions
app/Domains/Quiz/Data/GeneratedQuestionData.php     what the agent output is hydrated into
```

One agent per job to be done. An agent is a class, not a string — prompts never live in controllers, jobs, or
Data objects.

## Agent shape

Agents implement `Laravel\Ai\Contracts\Agent`, use the `Promptable` trait, and put the system prompt in
`instructions()`. Anything the prompt needs is constructor-injected and resolved with `make()`.

```php
namespace App\Domains\Quiz\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[Temperature(0.4)]
final class GenerateQuizQuestions implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(private int $questionCount) {}

    public function instructions(): string
    {
        return <<<'PROMPT'
        You write multiple-choice study questions from a supplied document.

        Rules:
        - Use only facts stated in the document. Never invent material.
        - Each question has exactly one correct option unless told otherwise.
        - Distractors must be plausible and drawn from the same document.
        - Never reference "the document", "the text", or page numbers in a question.
        - Write questions in the language of the source material.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'questions' => $schema->array()->items(
                $schema->object(fn ($schema) => [
                    'question' => $schema->string()->required(),
                    'explanation' => $schema->string()->required(),
                    'options' => $schema->array()->items(
                        $schema->object(fn ($schema) => [
                            'text' => $schema->string()->required(),
                            'isCorrect' => $schema->boolean()->required(),
                        ])
                    )->required(),
                ])
            )->required(),
        ];
    }
}
```

Configure the agent with attributes (`#[Provider]`, `#[Model]`, `#[Temperature]`, `#[MaxTokens]`, `#[Timeout]`),
not with magic numbers passed at every call site. Override per-prompt only when a specific call genuinely differs.

## Structured output is mandatory

Anything we persist comes back through `HasStructuredOutput` and a `schema()` definition.

- Nested shapes use `$schema->object(fn ($schema) => [...])`; lists use `$schema->array()->items(...)`.
- Mark every field the app reads as `->required()`. Constrain what you can (`->enum([...])`, `->min()`, `->max()`).
- The response is accessed like an array: `$response['questions']`.
- **Hydrate it into a Data object immediately** (`GeneratedQuestionData::collect($response['questions'])`) and
  validate it before it reaches the database. A schema-conformant answer can still be semantically wrong —
  a question with zero correct options, or four identical distractors, is rejected by us, not by the model.

## Sending the file

Attach the stored file; do not paste extracted text into the prompt unless extraction is the point.

```php
use Laravel\Ai\Files;

$response = GenerateQuizQuestions::make(questionCount: 50)->prompt(
    'Generate study questions covering the attached material.',
    attachments: [Files\Document::fromStorage($source->path, disk: $source->disk)],
);
```

- Upload once, reuse many times: for a source that will be prompted more than once, `->put()` it with the provider
  and store the returned file id on the `Source` row, then attach with `Files\Document::fromId($source->file_id)`.
- Delete the provider-side file when the source is deleted.
- Never attach a file that failed validation (type, size, 25-page limit). Validation happens on upload, not here.

## Queue everything

Model calls never run inside a web request. Upload returns immediately with a `pending` source.

- Generation runs in a queued job (`AnalyzeSource`, `GenerateQuiz`) inside its domain, not via the agent's `queue()`
  helper — we want our own job class so status transitions, retries, and failure handling are explicit and testable.
- The job stays thin: it resolves an action (`PersistGeneratedQuestions`) and calls it. Domain logic is not in the job.
- Each job sets `$tries`, a sane `$backoff`, and a `$timeout` above the agent's own timeout.
- Persist status on the model (`pending` → `processing` → `ready` / `failed`) and surface it in the UI. A failed
  generation shows the user a real message and a retry affordance — never a silent empty state.
- Write results in a transaction: a half-written quiz is worse than no quiz.
- Guard against double-runs: a source already `processing` is not re-queued.

## Cost and safety

- Token spend is real. Generate once per accepted proposal and persist; never regenerate to render a page.
- Only prompt with files the requesting user owns. Check ownership before dispatching, and again in the job.
- Treat model output as untrusted input: it is rendered to users, so it is escaped like any other user content and
  never interpolated into SQL, shell, or markup.
- Log the agent, source id, and token usage for each run. Do not log full prompt or document contents.

## Notifications

Reminder notifications ("a new quiz is ready for <topic>") are Laravel notifications, queued, one class per
message, with the copy behind `__()`. They are dispatched from a scheduled command, never from a model callback.
Respect a per-user opt-out and never send twice for the same (user, topic, day).

## Testing AI

Tests never hit a provider. Fake the agent and assert on what we do with the output.

```php
GenerateQuizQuestions::fake([
    ['questions' => [/* ... */]],
]);

// ... dispatch the job ...

GenerateQuizQuestions::assertPrompted('Generate study questions covering the attached material.');
```

- `Agent::fake()->preventStrayPrompts()` in tests that must not call anything else.
- Cover the failure paths explicitly: provider throws, malformed-but-schema-valid output, zero correct options.
- Assert the queued job was dispatched from the controller, and test the job's behaviour separately.
