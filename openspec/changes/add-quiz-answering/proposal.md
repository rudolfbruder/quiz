## Why

`add-domain-foundation` landed the schema, the fixtures and a fully tested `ReviewScheduler` — and
nothing calls it. There is no action that selects a day's questions, no attempt lifecycle, no HTTP
surface, and no way for a person to answer anything. The product currently exists only as structure.

It also left an unresolved contradiction. `CLAUDE.md` describes selection as a three-day cooldown read
off the `responses` table, while the foundation's own design makes `review_states.due_at` the hot path
and calls it "the hot path of the entire application". Those are two different products. The cooldown
was a pitch, never a promise to anyone, and it is superseded here: the scheduler decides when a
question returns, because a fixed rotation cannot tell an easy question from one the learner keeps
losing.

This change turns the foundation into something a person can use daily, against the seeded question
set, with no upload and no model call anywhere in it.

## What Changes

- **BREAKING (documented behaviour, no shipped code):** the daily quiz is selected by scheduler due
  dates, not by a three-day cooldown. `CLAUDE.md`'s selection rules and `PRODUCT.md`'s first
  positioning mechanism are rewritten to match. No migration — the schema always supported both.
- Add `SelectDailyQuestions`, the single owner of daily-set composition: due questions first, most
  overdue leading, remaining slots filled with never-seen questions up to a per-day cap, deterministic
  per user, topic and day.
- Add the attempt lifecycle — `StartAttempt`, `RecordResponse`, `ScoreAttempt` — including the
  write-back that finally gives `ReviewScheduler` a caller: a graded answer updates the learner's
  `ReviewState` and appends a `ReviewLog` entry.
- Add the HTTP surface these need: controllers under `app/Http/Controllers/<Domain>/`, request and
  response `Data` objects, and routes filled into the existing `routes/attempt.php`,
  `routes/review.php` and `routes/topic.php` stubs.
- Add the quiz-taking surfaces in Inertia + Vue: a topic list, the daily quiz itself, per-question
  answering with its explanation, a result surface, and the honest empty state for a day with nothing
  due.
- Build the surfaces in the `glyph-grid` world, and migrate `Dashboard` and the auth surfaces onto it
  so the signed-in journey has no visible seam.
- Correct the ramp in code to match the re-derived `DESIGN.md`: `RAMP_MEANING` and `simulateField` in
  `resources/js/lib/glyph.ts`, and the readout in `RetentionField.vue`, currently describe the
  superseded cooldown. `simulateField` is re-derived against the scheduler's retrievability curve, and
  `RetentionField` gains a mode that reads a real learner's `review_states`.
- Add two configuration keys: a cap on new questions introduced per day, and a one-day floor on the
  interval a daily quiz can honour (`min_stability_days` is half a day, which a once-daily quiz can
  never serve — the same reason the foundation dropped sub-day learning steps).

Explicitly out of scope: upload and text extraction, any agent or model call, any queued job,
notifications and reminders, practice attempts outside the daily rhythm, and the signed-out landing
page. Those remain `add-source-upload`, `add-ai-generation` and a later `add-landing-page`.

## Capabilities

### New Capabilities

- `daily-selection`: how a day's question set is composed for one learner and one topic — which
  questions are eligible, in what order, how new material is introduced, how a backlog behaves, and
  why the same day always yields the same set.
- `quiz-taking`: the lifecycle of one served attempt — how questions are recorded as served before
  they are answered, how an answer is graded and scored, how the grade reaches the scheduler, and what
  the learner is shown at each state including failure and emptiness.

### Modified Capabilities

None. `add-domain-foundation` is not yet archived, so its `study-data-model` and `review-scheduling`
specs are not in `openspec/specs/`. Neither needs a requirement change: the scheduler's maths and the
data model are both unchanged by this work, which only adds the callers they were built for.

## Impact

- **New code**: three or four actions per domain across `Attempt` and `Review`, their request and
  response `Data` objects, three controllers, the routes in three existing stubs, and roughly five Vue
  pages with the quiz-answering components `DESIGN.md` lists as missing.
- **Modified code**: `resources/js/lib/glyph.ts` and `RetentionField.vue` (ramp re-derivation);
  `Dashboard.vue` and the auth pages (world migration); `config/review.php` (two keys); `CLAUDE.md` and
  `PRODUCT.md` (selection rule); `DESIGN.md` is already updated ahead of this change and says so.
- **Untouched**: every migration, model, factory and seeder from the foundation. The schema supports
  this change as landed.
- **Dependencies**: none added.
- **Risk**: the quiz-answering surfaces are the first real test of the `glyph-grid` system, and the
  system has open questions it has not answered — how correctness reads with a single ink and no red,
  where the longest prose in the product goes, and whether the learner sees their retention field
  mid-quiz. Those are design decisions this change has to make rather than inherit.
- **Downstream**: `add-source-upload` and `add-ai-generation` become the only things standing between
  the seeded demo and a learner's own document. A later `add-landing-page` consumes the corrected ramp.
