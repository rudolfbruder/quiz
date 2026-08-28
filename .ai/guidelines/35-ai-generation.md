# AI: question generation with the Laravel AI SDK

**Full detail: `.ai/reference/ai-generation.md`. Read it before writing or changing any agent, AI job, or schema.**
Activate the `ai-sdk-development` skill and search the docs first — the SDK is new.

## Rules

1. Every model call goes through `laravel/ai`. No raw HTTP to OpenAI, no other SDK, no `json_decode()` of prose.
2. Agents live in the owning domain's `Agents/` folder — no top-level `app/Ai/`. One agent per job to be done.
   An agent is a class, not a string: prompts never live in controllers, jobs, or Data objects.
3. Agents implement `Agent`, use `Promptable`, put the system prompt in `instructions()`, and take their inputs
   through the constructor. Configure with attributes (`#[Provider]`, `#[Model]`, `#[Temperature]`, `#[MaxTokens]`,
   `#[Timeout]`), not magic numbers at the call site.
4. Anything we persist comes back via `HasStructuredOutput` + `schema()`, every read field `->required()`.
   Hydrate into a Data object and validate before the database — schema-valid output can still be semantically
   wrong (zero correct options, duplicate distractors).
5. Attach the stored file (`Files\Document::fromStorage(...)` / `fromId(...)`); do not paste extracted text.
   Never attach a file that failed upload validation (type, size, 25-page limit).
6. Model calls never run in a request cycle. Generation runs in our own queued job (`AnalyzeSource`,
   `GenerateQuiz`) — not the agent's `queue()` helper — with explicit `$tries`, `$backoff`, `$timeout`, status
   transitions (`pending` → `processing` → `ready` / `failed`), a transaction around the write, and a guard
   against re-queuing a source already `processing`. A failure shows a real message and a retry, never a silent
   empty state.
7. Generate once per accepted proposal and persist; never regenerate to render a page. Check file ownership
   before dispatching and again in the job. Treat model output as untrusted input. Log agent, source id, and
   token usage — never prompt or document contents.
8. Reminder notifications are queued Laravel notifications, one class per message, copy behind `__()`, dispatched
   from a scheduled command, per-user opt-out respected, never twice for the same (user, topic, day).
9. Tests never hit a provider: fake the agent, assert on what we do with the output, and cover failure paths
   (provider throws, malformed-but-schema-valid output, zero correct options).
