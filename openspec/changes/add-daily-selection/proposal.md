## Why

`add-domain-foundation` shipped a tested `ReviewScheduler` and nothing calls it. Before any surface can
serve a learner a quiz, one question has to be answerable in code: *which five questions does this
person get today, in this topic?*

That question is small, has no user interface, and is the thing every later surface waits on. Landing it
alone means the rule can be proved against real data — the seeded eighty-question Roman Republic set —
before a single Vue file exists to get it wrong.

It also settles a contradiction that has been sitting in the repository. `CLAUDE.md` describes selection
as a three-day cooldown read off `responses`; `add-domain-foundation`'s design makes `review_states.due_at`
the hot path. The cooldown was a pitch, never a promise, and it is superseded here.

## What Changes

- **BREAKING (documented behaviour, no shipped code):** the daily set is chosen by scheduler due dates,
  not a three-day cooldown. `CLAUDE.md`'s selection rules and `PRODUCT.md`'s first positioning mechanism
  are rewritten to match. No migration — the schema always supported both.
- Add `SelectDailyQuestions`, the single owner of daily-set composition: due questions first, most
  overdue leading, remaining slots filled with never-seen questions up to a per-day cap, deterministic
  per learner, topic and day.
- Add two configuration keys: a cap on new questions introduced per day, and a one-day floor on the
  interval a once-daily quiz can honour, since `min_stability_days` is half a day.
- Add an Artisan command that prints a learner's set for a topic, so the rule is demonstrable and
  reviewable without any interface.

Deliberately not in this change: serving, answering, scoring, the write-back to `ReviewState`, and every
Vue surface. Those are `add-quiz-answering`. No HTTP route is added here.

## Capabilities

### New Capabilities

- `daily-selection`: how a day's question set is composed for one learner and one topic — which
  questions are eligible, in what order, how new material is introduced, how a backlog behaves, and why
  the same day always yields the same set.

### Modified Capabilities

None. `study-data-model` and `review-scheduling` are in `openspec/specs/` and neither changes here: this
adds a new capability that consumes them.

## Impact

- **New code**: one action in the `Attempt` domain, a response `Data` object for the chosen set, and one
  Artisan command. Roughly a dozen tasks.
- **Modified code**: `config/review.php` gains two keys; `CLAUDE.md` and `PRODUCT.md` have the cooldown
  rule replaced.
- **Untouched**: every migration, model, factory and seeder. No route, no controller, no Vue file.
- **Dependencies**: none.
- **Downstream**: `add-quiz-answering` consumes this action. Any later visualisation of a learner's
  retention reads the same rule rather than reimplementing it.
