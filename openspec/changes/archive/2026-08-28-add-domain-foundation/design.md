## Context

The application is a fresh Laravel install with authentication wired and nothing else: no domain tables, no domain models, no fixtures. See `proposal.md` — Why for the motivation.

Three constraints shape everything below.

**The project organises code by domain, not by layer.** `CLAUDE.md` makes this the rule that outranks Laravel defaults, with two carve-outs: `App\Models\User` stays put, and controllers stay under `app/Http/Controllers/<Domain>/`. This change is the first code to actually establish that tree, so it also inherits the traps the layout creates.

**Follow-on work is meant to run in parallel.** `add-source-upload`, `add-ai-generation` and `add-quiz-taking` touch different domains and could be built simultaneously — but only if the schema they share already exists and the files that admit exactly one writer have already been written.

**No maintained PHP implementation of the review algorithm exists.** The reference implementations are published for TypeScript, Python, Go, Rust, Java, Kotlin, Swift, Dart, Ruby, Clojure and Elixir. There is no PHP port in the reference set. The two on Packagist are unofficial; the more used of the two has a single `v0.1` tag and does not declare support for PHP 8.4, which this project runs.

## Goals / Non-Goals

**Goals:**

- Land the complete schema in one pass, in an order that foreign keys actually permit.
- Give every domain a factory and a seeder good enough to build a feature against without the upstream feature existing.
- Implement the scheduling maths as a pure, unit-testable function with no database and no clock.
- Claim every single-writer file now, so no follow-on change has to touch one.
- Keep the data model forward-compatible with the full reference algorithm, so that swapping the maths later needs no migration.

**Non-Goals:**

- Reproducing the reference algorithm exactly. This is a deliberate simplification — see the decision below.
- Per-user parameter optimisation. It needs thousands of reviews per user and a training step with no PHP equivalent.
- Any behaviour that consumes the schema. Selection queries, HTTP endpoints, agents and jobs all belong to later changes.

## Decisions

### One foundation change rather than one scaffolding change per domain

Splitting the schema across six per-domain changes would look tidier and parallelise worse. Migration order is fixed by foreign keys, so six agents writing migrations concurrently would produce either colliding timestamps or an order that fails on a fresh database. `DatabaseSeeder` and `routes/web.php` admit one writer each. Those files are the whole reason the split fails.

Alternative considered: per-domain scaffolding changes with a coordinator merging the shared files at the end. Rejected — it front-loads six rounds of OpenSpec ceremony for work that is mechanical, and still serialises on the merge.

Consequence: this change is broad but shallow. It contains no branching logic outside the scheduler.

### Migration filenames are assigned up front

Foreign keys make the order non-negotiable, so the order is fixed here rather than left to whoever runs `make:migration` first:

```
2026_08_28_100001_create_topics_table
2026_08_28_100002_create_sources_table
2026_08_28_100003_create_quiz_proposals_table
2026_08_28_100004_create_quizzes_table
2026_08_28_100005_create_questions_table
2026_08_28_100006_create_options_table
2026_08_28_100007_create_attempts_table
2026_08_28_100008_create_responses_table
2026_08_28_100009_create_review_states_table
2026_08_28_100010_create_review_logs_table
```

`quiz_proposals` precedes `quizzes` because a quiz references the proposal it was accepted from, not the other way round.

### Where each entity lives

| Entity | Domain | Why there |
|---|---|---|
| `Topic` | `Topic` | Depends on nothing. |
| `Source` | `Source` | Upload and storage only; carries no AI awareness. |
| `QuizProposal` | `Generation` | An AI artifact with its own acceptance lifecycle. It is an input to a quiz, not part of one. |
| `Quiz` | `Quiz` | A thin grouping row once the proposal is accepted. |
| `Question`, `Option` | `Question` | Both `Attempt` and `Review` read questions and neither cares about quizzes, which makes this a real boundary rather than a symmetric one. |
| `Attempt`, `Response` | `Attempt` | Serving and scoring. |
| `ReviewState`, `ReviewLog` | `Review` | Scheduling state, plus the maths. |
| `User` | `app/Models` | Unmoved. |

Dependencies point one way, with no cycles: `Generation → Source, Quiz, Question, Topic`; `Attempt → Question, Topic, Review`; `Review → Question, Topic`; `Question → Quiz`; `Quiz → Source, Topic`; `Source`, `Topic` and `User` depend on nothing.

`App\Models\User` stays where it is. Moving it would mean retargeting `config/auth.php`, the Fortify wiring, the passkey integration and the two-factor columns, for no gain that this change needs. The `User` domain holds its data objects, actions, policies and seeder; the model class is the exception `CLAUDE.md` already carves out.

### Every domain model declares its factory and policy explicitly

Laravel's auto-discovery guesses `App\Models\X` and `App\Policies\XPolicy`. Both guesses miss for a model under `app/Domains/`, and the failure surfaces at test time as a class-not-found error naming a path that was never plausible. Every domain model therefore carries `#[UseFactory]` and `#[UsePolicy]` attributes rather than relying on convention.

This is the single most likely thing for a follow-on agent to omit, so the tasks call it out per model rather than once in passing.

### The scheduling model is implemented here, not depended upon

Given no maintained PHP port exists, the options were an unofficial `v0.1` package that does not declare PHP 8.4, or an implementation in the application. The scheduler is roughly sixty lines of arithmetic; the genuinely hard part of the reference project is the parameter optimiser, which is explicitly a non-goal.

The implementation is a deliberate simplification of the reference algorithm — its concepts, not its twenty-one fitted parameters:

```
DECAY  = 0.5
FACTOR = 0.9 ** (-1 / DECAY) - 1          ≈ 0.234568, derived, never hardcoded
R(t)   = (1 + FACTOR * t / S) ** -DECAY

difficulty:   D = D - 0.8 * (grade - 3)
              D = D + 0.1 * (5 - D)          mean reversion
              D = clamp(D, 1, 10)

recalled:     S = S * (1 + 2.5 * gradeFactor * (11 - D)/10 * (2 - R) * S ** -0.3)
forgotten:    S = max(minStability, 0.5 * S ** 0.4)
```

`FACTOR` is derived from the desired retention so that `R(S) = 0.9` exactly. That is what lets stability be read as "days until recall drops to 90%", and makes the due date simply the last review plus the stability at default retention. Hardcoding a factor instead would leave stability and recall probability free to drift apart, and would need a second formula to convert one into the other.

The three moderating terms are the three memory laws the reference model is built on: harder material stabilises less, already-stable material stabilises proportionally less, and material recalled after more forgetting stabilises more.

Alternative considered: porting the reference algorithm faithfully, weights and all. Rejected for now — the twenty-one weights are only meaningful alongside the optimiser that fits them, and the default weights are tuned to a review pattern (many reviews per day, sub-day learning steps) that a once-daily quiz does not produce.

Forward compatibility is preserved by the data, not the maths: the stored columns, the four-point grade scale and the append-only log are all what the reference algorithm consumes. Replacing the formulas later is a code change with no migration, and existing review states can be recomputed by replaying the log.

### Learning steps are omitted

The reference algorithm interposes sub-day steps — typically one minute and ten minutes — before a card graduates to day-scale intervals. This application serves a quiz once a day, so a ten-minute interval can never be honoured. A question graduates to a day-scale interval on its first review, and a lapse is expressed as a collapsed stability rather than a distinct relearning state.

### Review state is created lazily and carries no state column

No review state exists for a user and question until the first review. Absence therefore means "new", which removes both a redundant `new` enum value and the write amplification of materialising a row per user per question at generation time. It also keeps question generation ignorant of users entirely.

The reference model's `learning` / `review` / `relearning` states are dropped for the same reason learning steps are: with no sub-day steps, behaviour is fully determined by stability, difficulty and lapse count. If the distinction is ever needed, the column can be added and backfilled from the log.

### Served questions are recorded before they are answered

A response row is written when a question is served, with no selected option. Answering fills the row in.

The alternative — writing a row only when the user answers — loses every question served to a user who abandoned the attempt. Those questions would then be treated as never shown and would be served again immediately, silently defeating scheduling. A separate pivot for served questions would also work but adds a table to express something a nullable column already expresses.

### Two constraints cannot live in the schema

Both need partial indexes, which Blueprint cannot express portably:

- at most one accepted proposal per source;
- a daily attempt unique per user, topic and date, but only when the attempt is a daily one.

`quiz_proposals` gets a unique index on source and position, and `attempts` on user, topic and served date. The conditional parts are enforced in the actions that create these rows — which belong to later changes — and each is called out in the specs so those changes inherit the obligation.

### Two columns are denormalised deliberately

`quizzes.user_id` and `review_states.topic_id` are both derivable by traversal. They are stored because the due-queue query — questions due for one user in one topic at one moment — is the hot path of the entire application, and both columns keep it to a single index lookup rather than a chain of joins through quizzes and sources.

### Page count is nullable because nothing counts pages at upload

Counting PDF pages without a parsing library is unreliable: the byte-level heuristics break on compressed object streams, which modern PDFs use routinely. Rather than take a dependency, the page count is reported by document analysis in a later change, and `sources.page_count` stays null until then. This is why `rejected` is a distinct status from `failed`: a document that turns out to exceed the page limit was accepted at upload in good faith and refused afterwards, which is a different thing from analysis breaking.

### Seeders carry the same weight as the schema

The seeders are what let the three follow-on changes proceed at once, so they are treated as a deliverable rather than a convenience. Two carry real requirements:

- the question fixtures must be a believable quiz — plausible distractors, exactly one correct option, real explanations — because the quiz-taking work is built and reviewed against them;
- the review fixtures must backdate review states across a spread of stabilities, so that the due queue has a non-trivial shape on the first run. Seeded uniformly, every question comes due on the same day and nothing about scheduling is observable.

## Risks / Trade-offs

**The simplified scheduler will not match the reference algorithm's accuracy.** → Accepted deliberately. The append-only log means the decision is reversible: swap the formulas and replay. The log is the mitigation, which is why it is in this change and not deferred.

**The change is broad, so a mistake in it blocks three parallel changes.** → It is also shallow and almost entirely declarative. The one part with real logic, the scheduler, is pure and unit-tested against a fixed progression before anything depends on it.

**A follow-on agent may omit `#[UseFactory]` or `#[UsePolicy]` on a new model.** → Called out per model in the tasks, and the failure is loud at first test run rather than silent.

**Deriving `FACTOR` from desired retention couples the two.** → That coupling is the point; it is what keeps stability meaningful. Desired retention stays configurable and `FACTOR` is recomputed from it, so changing one cannot leave the other stale.

**Denormalised columns can drift from their source of truth.** → Both are set once at creation and never updated: a quiz does not change owner, and a question does not change topic without its quiz moving with it.

**Ten tables land before any of them is exercised by a feature.** → Every table is required by an already-planned change, and the schema was reviewed in full before this proposal. Nothing here is speculative.

## Migration Plan

The change is purely additive: ten new tables, no alteration to any existing one. Deployment is `migrate`, and rollback is `migrate:rollback` over ten steps with no data loss risk, since no existing table is touched.

Seeders are development fixtures and are not run in production.

## Open Questions

None that affect the specs, the approach or the task breakdown. Two decisions are deliberately deferred to the changes that need them: whether `Easy` is ever awarded automatically from answer speed alone belongs to `add-quiz-taking`, which owns the mapping from a correct answer to a grade; and how many new questions per day backfill a short due queue belongs to `add-fsrs-scheduling`, which owns selection.
