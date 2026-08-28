## Why

The application currently has no domain code at all: no tables, no models, no fixtures. Every planned feature — uploading a source, generating questions from it, taking a quiz, scheduling reviews — depends on the same persistence layer and the same review-scheduling maths.

Those features are independent in principle, but if each one brought its own slice of the schema they would collide in practice on the files that only one writer can own: `DatabaseSeeder`, `routes/web.php`, and above all the migration ordering, where foreign keys make the sequence non-negotiable. Landing the whole data model, its fixtures, and the scheduler once, up front, is what makes `add-source-upload`, `add-ai-generation` and `add-quiz-taking` buildable in parallel afterwards.

Seed data is the other half of that unlock: with realistic fixtures, the quiz-taking work can be built and tested against seeded questions without waiting for the AI generation pipeline to exist.

## What Changes

- Introduce eight domains under `app/Domains/`: `Topic`, `Source`, `Generation`, `Quiz`, `Question`, `Attempt`, `Review`, `User`. Dependencies point one way only, with no cycles.
- Add ten migrations in a fixed order dictated by foreign keys: `topics` → `sources` → `quiz_proposals` → `quizzes` → `questions` → `options` → `attempts` → `responses` → `review_states` → `review_logs`.
- Add the domain models, each carrying explicit `#[UseFactory]` and `#[UsePolicy]` attributes, because Laravel's auto-discovery cannot resolve factories or policies for models living outside `App\Models`.
- Add the lifecycle enums: `SourceStatus`, `QuizStatus`, `QuestionType`, `AttemptKind`, `Rating`.
- Add a factory and a seeder per domain, namespaced `Database\Factories\<Domain>` and `Database\Seeders\<Domain>`. Fixtures must be realistic enough to build against: a believable question set with plausible distractors, and backdated review states with varied stability so the due queue is non-trivial on the first run.
- Add `Review/Services/ReviewScheduler`: a pure, clock-injected implementation of the FSRS-lite scheduling model (stability, difficulty with mean reversion, power-law retrievability, lapse handling), with unit tests and no database access.
- Add `config/review.php` and `config/generation.php` so no scheduling or generation constant is ever hardcoded at a call site.
- Claim the single-writer files once, so no later change has to touch them: `DatabaseSeeder` listing every domain seeder, and `routes/<domain>.php` stubs together with their `require` statements in `routes/web.php`.
- `App\Models\User` stays where it is. Fortify, the auth guards, the passkey wiring and the two-factor columns are all bound to that class; the `User` domain holds only its data objects, actions, policies and seeder.
- `QuizProposal` belongs to the `Generation` domain rather than `Quiz`. It is an AI artifact with its own acceptance lifecycle, not part of the accepted quiz.

Explicitly out of scope: file upload and text extraction, any agent or model call, any queued job, any controller or Inertia page, notifications, and the daily question selection query. This change ships the structure those features will be built on, and nothing that uses it.

## Capabilities

### New Capabilities

- `study-data-model`: the durable structure of the study domain — topics, sources, AI proposals, quizzes, questions and options, attempts and responses, and per-user review state — together with the relationships, cascade behaviour and uniqueness guarantees the database itself enforces.
- `review-scheduling`: the FSRS-lite scheduling model that decides, from a graded review, how a question's memory strength changes and when it should next be shown.

### Modified Capabilities

None. This is the first change in the project; there are no existing specs.

## Impact

- **New code**: the `app/Domains/` tree, ten migrations, eight factories and seeders, five enums, one service, two config files.
- **Modified code**: `routes/web.php` gains `require` statements for the per-domain route files. `DatabaseSeeder` is rewritten to call the domain seeders.
- **Untouched**: `app/Models/User.php`, `app/Actions/Fortify/`, `app/Http/`, and everything under `resources/js/`. This change adds no HTTP surface.
- **Dependencies**: none added. No Composer package and no npm package. The scheduler is implemented in the application rather than pulled from a library, since no maintained PHP port of FSRS exists.
- **Database**: ten new tables. No existing table is altered, so the change is additive and reversible by rollback.
- **Downstream**: unblocks `add-source-upload`, `add-ai-generation` and `add-quiz-taking`, which can then proceed in parallel, followed by `add-fsrs-scheduling` which consumes the scheduler to serve daily quizzes.
