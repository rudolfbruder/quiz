## Why

`add-daily-selection` decides which questions a learner gets today. Nothing yet serves them, records
what was answered, or tells the learner whether they were right — and `ReviewScheduler` still has no
caller, so answering changes nothing about when a question returns.

This change closes that loop: a person opens a topic, answers today's questions, sees why each answer
was right or wrong, and their memory of each question moves. It runs entirely against the seeded
question set, with no upload and no model call anywhere in it.

## What Changes

- Add the attempt lifecycle — `StartAttempt`, `RecordResponse`, `ScoreAttempt` — recording every
  question as served before it is answered, so a question served to someone who walked away is not
  treated as never shown.
- Add `ApplyReview`, the write-back that finally gives `ReviewScheduler` a caller: a graded answer
  updates the learner's `ReviewState` and appends a `ReviewLog` entry, in one transaction.
- Add the grade mapping this change owns — wrong is `Again`, correct is `Good`, fast and correct is
  `Easy` — which `add-domain-foundation` deferred here by name.
- Add the HTTP surface these need: controllers under `app/Http/Controllers/<Domain>/`, request and
  response `Data` objects, and routes filled into the existing `routes/attempt.php` and
  `routes/topic.php` stubs.
- Add the answering surfaces in Inertia + Vue: a topic list, the day's questions, per-question answering
  with its verdict and explanation, a result surface, and the honest empty state for a day with nothing
  due.

Explicitly out of scope: choosing the day's questions (`add-daily-selection`, which this change
consumes); any retention visualisation, since `retire-glyph-grid-theme` deleted the component that drew
one; any theme work, which `DESIGN.md` and the `visual-system` spec own; and upload, generation,
notifications and the signed-out landing page.

## Capabilities

### New Capabilities

- `quiz-taking`: the lifecycle of one served attempt — how questions are recorded as served before
  they are answered, how an answer is graded and scored, how the grade reaches the scheduler, and what
  the learner is shown at each state including failure and emptiness.

### Modified Capabilities

None. `study-data-model` and `review-scheduling` are now in `openspec/specs/`, and neither needs a
requirement change: the scheduler's maths and the data model are both unchanged by this work, which only
adds the callers they were built for.

## Impact

- **New code**: actions across `Attempt` and `Review`, their request and response `Data` objects, two
  controllers, routes in two existing stubs, and the Vue pages carrying the quiz-answering components
  `DESIGN.md` lists as missing.
- **Modified code**: none outside those. `config/review.php`, `CLAUDE.md` and `PRODUCT.md` are edited by
  `add-daily-selection`. The theme is inherited from the shadcn token set, not edited here.
- **Untouched**: every migration, model, factory and seeder from the foundation. The schema supports
  this change as landed.
- **Dependencies**: none added.
- **Risk**: these are the first product surfaces built on the current theme, and the theme is being
  reworked as this is written. `DESIGN.md` is the authority at build time; this change decides only what
  is specific to answering — where the explanation goes, and what an empty day looks like.
- **Depends on**: `add-daily-selection`, whose action this change calls. Nothing here re-derives it.
- **Downstream**: nothing is blocked on this. A retention visualisation, if one returns, has a slot on the
  topic and result surfaces.
