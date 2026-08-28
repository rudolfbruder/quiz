<laravel-boost-guidelines>
=== .ai/00-project-context rules ===

# Project: Quiz

An AI-powered study app. A logged-in user uploads a document (PDF, Word, or plain text). The app stores it, sends
it to OpenAI through the Laravel AI SDK, and generates quiz questions from its content. From then on the user
receives a small daily quiz — questions they have not seen recently — and notifications reminding them to take it.

## The flow, end to end

1. **Upload.** Authenticated user uploads a file. It is validated (type, size, page count) and stored on a disk.
   The upload row is created immediately with a `pending` status; the user is not made to wait.
2. **Analyze.** A queued job sends the stored file to the model as an attachment and asks for **three candidate
   quiz outlines** for that source — different angles/scopes over the same material.
3. **Choose.** The user picks one of the three proposals. Only the chosen one is expanded.
4. **Generate.** A queued job produces the full question set (many questions, each with its own answer options and
   exactly-marked correct option(s)) as **structured JSON output** — never free text that we then parse.
5. **Serve daily.** Each day the user gets a short quiz (default 5 questions) per topic, selected so it avoids
   questions they have already been served inside a cooldown window (default 3 days).
6. **Remind.** Notifications tell the user a fresh quiz is waiting for a topic.

## Hard limits (enforce, do not "handle gracefully" by silently truncating)

- Accepted types: PDF, DOCX, TXT/MD. Text-bearing documents only for now — no image-only scans, no OCR.
- Maximum **25 pages** per document. Reject above that with a validation error naming the real page count.
- One generation run per source at a time. A source already `processing` cannot be re-queued.

## Stack (fixed — do not add to it without approval)

| Concern | Choice |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Auth | Laravel Fortify (2FA + passkeys already wired) |
| AI | `laravel/ai` (Laravel AI SDK), OpenAI provider — **the only** way we call a model |
| Boundary types | `spatie/laravel-data` — **all** input and output |
| Views | Inertia v3 + Vue 3 (`<script setup lang="ts">`) |
| Styling | Tailwind CSS v4 only; design work goes through the `impeccable` skill |
| Routing to JS | Laravel Wayfinder (generated) |
| Background work | Laravel queues + notifications |
| Tests | Pest 5 |
| Static analysis | Larastan / PHPStan level 7 |
| Spec workflow | OpenSpec (`openspec/`) |

## Non-negotiables

These override any generic Laravel habit. Violating one is a bug, not a style choice.

1. **Code is organised by domain, not by layer.** `app/Domains/<Domain>/{Models,Data,Actions,Services,Jobs,
   Notifications,Agents,Enums,Policies}`. `User` and controllers keep their Laravel-default homes. See
   `.ai/20-architecture`.
2. **Every HTTP boundary crosses a `Data` object.** No `FormRequest` in new code, no API Resources, no raw arrays or
   Eloquent models handed to `Inertia::render()`. See `.ai/30-data-objects`.
3. **All model calls go through a `laravel/ai` agent class** in the owning domain's `Agents/` folder. No raw HTTP to OpenAI, no other
   SDK, no inline prompt strings in controllers or jobs. See `.ai/35-ai-generation`.
4. **Any AI output the app persists must come from a declared JSON schema** (`HasStructuredOutput`). Never parse
   prose, never `json_decode()` a model reply.
5. **Model calls never happen in the request cycle.** Upload responds immediately; generation runs queued.
6. **Fortify owns authentication.** See `.ai/60-auth`.
7. **Tailwind utilities only.** See `.ai/80-styling`.
8. **Wayfinder for every URL in JS.** See `.ai/70-frontend`.
9. **Every change ships with a test**, with AI faked. See `.ai/90-testing`.
10. **Every feature starts as an OpenSpec change.** See `.ai/10-workflow`.

## Domain vocabulary

Use these names consistently — tables, models, Data objects, routes, Vue pages, notifications. The exact columns
and relations are pinned per feature in its OpenSpec change; the *names* are settled here.

- `Topic` — the subject area a user studies ("the domain"). Daily quizzes and cooldowns are scoped per topic.
- `Source` — one uploaded document: disk path, original name, mime type, page count, extraction status.
  Named `Source`, never `Document` — `Laravel\Ai\Files\Document` already owns that name in our imports.
- `QuizProposal` — one of the three AI-suggested outlines for a source. Exactly one gets accepted.
- `Quiz` — the accepted proposal, expanded. Belongs to a source and a topic; has many questions.
- `Question` — belongs to a quiz; has many options and an explanation.
- `Option` — one answer choice on a question; carries the `is_correct` flag.
- `Attempt` — one served daily quiz for one user: the questions given, when, and the resulting score.
- `Response` — the option(s) a user picked for one question inside one attempt. This table is also the
  "already asked" record the no-repeat selection reads.

Verbs: a user *uploads* a source, *accepts* a proposal; the app *generates* questions, *serves* an attempt,
*scores* it, and *reminds* the user.

## Selection rules (the part that is easy to get wrong)

- A question is **eligible** for a user's daily quiz when it has not been served to that user within the cooldown
  window (default 3 days, configurable — not hardcoded at the call site).
- If eligible questions run out, prefer the **least recently served** ones rather than serving nothing or repeating
  yesterday's set. Never return an empty quiz silently — say why.
- Selection is deterministic per (user, topic, day) so a page refresh does not reshuffle the quiz.
- Selection logic lives in one action (`SelectDailyQuestions`) and is unit-tested directly. Do not scatter
  `whereNotIn` fragments across controllers.

## Language

- All user-facing strings go through `__()`, including notification bodies.
- Code, comments, commit messages, and specs are written in English.

=== .ai/10-workflow rules ===

# Workflow

## Spec first: OpenSpec

Any change that adds or alters behaviour is an OpenSpec change before it is code.

1. `openspec-propose` — write the proposal, delta specs, and task list into `openspec/changes/<change-id>/`.
2. `openspec-apply-change` — implement the tasks, ticking them off as they land.
3. `openspec-archive-change` — archive once shipped and specs are synced.

Use `openspec-explore` when the requirement is still fuzzy — think there, not in the implementation.
Use `openspec-update-change` when the plan shifts mid-flight; never let code drift from an unrevised change.

Skip OpenSpec only for: typo fixes, formatting, dependency bumps, and non-behavioural refactors.
When in doubt, propose — a small proposal costs less than a wrong implementation.

**One change, one concern.** Do not fold an unrelated fix into an open change; propose a second one.

## Skills

Activate the matching skill *before* writing code in its domain, not after getting stuck:

| Working on | Skill |
|---|---|
| Any Laravel backend code | `laravel-best-practices` |
| Auth, login, 2FA, passkeys | `fortify-development` |
| Vue pages, forms, navigation | `inertia-vue-development` |
| Route helpers in TS | `wayfinder-development` |
| Utility classes / layout | `tailwindcss-development` |
| Any UI design or polish work | `impeccable` |
| Writing or reviewing tests | `testing-best-practices` |
| `Laravel\Ai\` usage | `ai-sdk-development` |
| Recording new house rules | `infer-conventions` |

## Order of work inside a change

1. Migration + model + factory.
2. Data objects (request and response) with their validation rules.
3. Route + controller + action.
4. Feature test — red, then green.
5. Vue page and components.
6. Quality gates (`.ai/95-quality-gates`).

Backend before frontend. A Vue page written against a prop shape that does not exist yet is rework.

## Recording decisions

When a decision is durable and non-obvious, record it with the Boost `record-rule` tool so it lands in `.ai/rules`
for the whole team. Do not store project rules in personal agent memory. Guidelines in `.ai/guidelines` are the
project-wide baseline; `.ai/rules` holds narrower, path-scoped rules discovered along the way.

=== .ai/20-architecture rules ===

# Architecture

## Domain-driven layout — this is the rule that outranks Laravel defaults

Application code lives under `app/Domains/<Domain>/`. Each domain owns everything it needs: models, services,
repositories, jobs, notifications, agents, data objects, enums, events, policies. A domain is a vertical slice, not
a layer. Code inside a domain folder is grouped by *what it belongs to*, then by *what it is*.

Two deliberate exceptions:

- **`app/Models/User.php` stays where it is.** Fortify, the auth guards, and framework conventions are bound to it.
- **Controllers stay in `app/Http/Controllers/<Domain>/`.** Standard Laravel HTTP layer, grouped by domain folder.

```
app/
  Domains/
    Source/                         uploads: storing, validating, extracting documents
      Models/            Source.php
      Data/              StoreSourceData.php  SourceData.php
      Actions/           StoreUploadedSource.php  DeleteSource.php
      Services/          SourceTextExtractor.php  PageCounter.php
      Repositories/      (only if it earns its place — see below)
      Jobs/              AnalyzeSource.php
      Notifications/
      Enums/             SourceStatus.php
      Policies/          SourcePolicy.php
      Events/            SourceUploaded.php
    Quiz/                           proposals, questions, generation
      Models/            Quiz.php  QuizProposal.php  Question.php  Option.php
      Data/              QuizData.php  GeneratedQuestionData.php  AcceptProposalData.php
      Actions/           AcceptQuizProposal.php  PersistGeneratedQuestions.php
      Agents/            ProposeQuizOutlines.php  GenerateQuizQuestions.php
      Jobs/              GenerateQuiz.php
      Enums/             QuestionType.php
      Policies/          QuizPolicy.php
    Attempt/                        serving daily quizzes, scoring, reminders
      Models/            Attempt.php  Response.php
      Data/              AttemptData.php  SubmitResponseData.php
      Actions/           SelectDailyQuestions.php  StartAttempt.php  ScoreAttempt.php
      Notifications/     DailyQuizAvailable.php
      Jobs/
    Topic/
      Models/            Topic.php
      Data/
      Actions/
  Models/
    User.php                        stays here — framework/Fortify bound
  Http/
    Controllers/<Domain>/           thin HTTP layer, grouped by domain
    Middleware/
  Actions/Fortify/                  Fortify-owned, do not repurpose
  Concerns/                         cross-domain traits only
  Providers/
database/
  migrations/                       all migrations, flat, standard Laravel
  factories/<Domain>/               namespaced Database\Factories\<Domain>
  seeders/
resources/js/
  pages/<domain>/                   Inertia pages mirror the domain names
  components/  layouts/  types/
routes/web.php, routes/settings.php
tests/Feature/<Domain>/  tests/Unit/<Domain>/
```

Namespaces follow the path: `App\Domains\Quiz\Models\Question`, `App\Domains\Attempt\Actions\ScoreAttempt`.
No composer change is needed — `App\` already maps to `app/`.

### Two traps this layout creates — handle them explicitly

Laravel's auto-discovery guesses `App\Models\X` and `App\Policies\XPolicy`. Domain models break both guesses, so
wire them with attributes instead of hoping:

```php
namespace App\Domains\Quiz\Models;

use App\Domains\Quiz\Policies\QuizPolicy;
use Database\Factories\Quiz\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(QuizFactory::class)]
#[UsePolicy(QuizPolicy::class)]
class Quiz extends Model
{
    //
}
```

Never rely on convention-based factory or policy resolution for a domain model. Missing `#[UseFactory]` surfaces as
a confusing "class not found `Database\Factories\Domains\Quiz\Models\QuizFactory`" at test time.

### Domain boundaries

- A domain may depend on another domain's **models, Data objects, and actions** — through their public API, not by
  reaching into its internals.
- Dependencies point one way. `Attempt` reads `Quiz`; `Quiz` never reads `Attempt`. If you need the reverse, raise
  a domain event instead of adding the back-reference.
- Cross-domain orchestration belongs in the controller or in an action of the domain that owns the outcome — never
  in a model.
- Shared code that genuinely belongs to no single domain goes in `app/Concerns/`. If two domains keep needing the
  same thing, that is a signal the boundary is wrong; fix the boundary rather than growing a shared bucket.
- No `app/Services/`, `app/Repositories/`, `app/Jobs/`, or `app/Notifications/` at the top level. Those live inside
  a domain.

## What each building block is for

- **Controller** — accepts a request `Data` object, calls one action or one job dispatch, returns an Inertia
  response or a redirect. Thin: if it needs a local variable to hold intermediate state, that logic belongs in an
  action. No business rules, no queries beyond a `find`.
- **Action** — one use case, one public entry point (`__invoke` or a single verb method), constructor-injected
  dependencies. The default place for domain behaviour. Receives Data objects or models, returns models or Data
  objects. Never touches `Request`, `session()`, or `redirect()`.
- **Service** — a cohesive collaborator wrapping one external or technical concern with several related methods
  (`SourceTextExtractor`, `PageCounter`). Use a service when the thing has state or a real API surface; use an
  action when it is one use case.
- **Repository** — **only if needed.** Justify it: the same non-trivial query is needed by two or more callers, or
  the data comes from somewhere that is not Eloquent. Otherwise query Eloquent directly from the action. A
  repository that only forwards `find`/`create`/`save` to a model is noise and should be deleted.
- **Job** — queued work. Thin: it resolves an action and calls it. Retries, backoff, timeouts, and status
  transitions live here; the domain logic does not.
- **Notification** — one class per message, queued, copy behind `__()`.
- **Model** — persistence, relations, casts, scopes. No HTTP awareness, no view formatting, no AI calls.
- **Data object** — the only shape that crosses the HTTP boundary, in either direction.
- **Agent** — the only way a model is prompted. See `.ai/35-ai-generation`.

## Request flow

```
Route  →  Controller  →  Action / Job dispatch  →  Service / Model
   ↑           ↓                    ↓
Wayfinder   Data object        Agent (queued only)
                ↓
          Inertia page (Vue)
```

## Rules

- **No fat controllers, no anaemic actions.** Business rules live in actions, services, and models.
- **No logic in Blade.** `resources/views/app.blade.php` is the only Blade file and stays a shell.
- **Single source of truth for validation:** the request Data object. Never re-validate in the controller or action.
- **Single source of truth for shape:** the response Data object. New field for the frontend? Add it there — do not
  sneak an extra key into `Inertia::render()`.
- Shared props (`auth.user`, `sidebarOpen`, flash) belong in `HandleInertiaRequests::share()` and nowhere else.
- **No model call in a request cycle.** Controllers dispatch jobs; jobs call agents. See `.ai/35-ai-generation`.
- Reach for a new abstraction on the second real caller. One caller = inline it.

=== .ai/35-ai-generation rules ===

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

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Test every code change by adding or updating a test.
- Run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
