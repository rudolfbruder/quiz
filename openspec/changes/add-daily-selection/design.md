## Context

See `proposal.md` — Why. `ReviewScheduler` exists, is pure and has thirty-two tests. `review_states`
carries `(user_id, topic_id, due_at)` as an index, deliberately denormalised by
`add-domain-foundation` because the due-queue query was identified there as "the hot path of the entire
application". This change writes that query and nothing else.

## Goals / Non-Goals

**Goals:**

- One owner for daily-set composition, unit-testable with no HTTP and no interface.
- Demonstrable against the seeded data before anything is built on it.

**Non-Goals:**

- Serving, answering, scoring or persisting anything. Selection reads; it does not write.
- Optimising. A topic holds tens to low hundreds of questions and the index already exists.

## Decisions

### Two branches, ordered, then padded

```
   ┌─ due questions ────────────────────────────────────┐
   │ review_states where user, topic, due_at <= moment   │
   │ order by due_at asc, question_id asc                │  ← index (user_id, topic_id, due_at)
   └────────────────────────────────────────────────────┘
                    │  take up to daily_question_count
                    ▼
   ┌─ new questions ────────────────────────────────────┐
   │ questions in topic with no review state for user    │
   │ order by quiz_id asc, position asc                  │
   └────────────────────────────────────────────────────┘
                    │  take min(remaining slots, max_new_per_day)
                    ▼
              the day's set, in that order
```

Both orderings are total and stable, so the result is deterministic without a seed, a hash or a shuffle
— which is what the spec's stability requirement needs. New questions are introduced in authoring order
so a learner meets material roughly as the source presents it, and introduction cannot restart or
reshuffle between days.

The due branch fills first, so a backlog suppresses new material with no separate rule for it. That is
the standard spaced-repetition posture — stop introducing while you are behind — arriving as a
consequence of the ordering rather than a policy someone has to remember.

Alternative considered: interleaving new questions among due ones for variety. Rejected — it would
either grow the daily set or displace a due question, and displacing a due question is the one thing
selection must not do.

### Selection takes the moment as an argument

Like `ReviewScheduler`, the action never reads the clock. The caller supplies the moment, which is what
makes "the same day always yields the same set" testable without freezing time globally.

### The action returns a Data object, not models

`DailySelectionData` carries the chosen questions in order, how many were due, how many were newly
introduced, and — when the set is short or empty — the earliest future due moment, so the caller can
explain the day rather than rendering a blank. The spec requires that explanation; putting the earliest
due moment in the return value is what lets a later surface satisfy it without a second query of its own.

### A backlog is counted but never surfaced as a total

The action knows how many questions are overdue. `DailySelectionData` deliberately does not expose it,
because the spec forbids presenting a backlog as a debt and the cheapest way to guarantee that is to
never hand the number to a view. If a legitimate need for it appears, it can be added with a reason.

### The demo is an Artisan command, not a route

`php artisan quiz:today {user} {topic}` prints the chosen set with the reason for each pick (due since
when, or newly introduced). It exists so this change is reviewable on its own — run it against the
seeded data, run it again, see the same five. No route or controller is added, and the command is not a
product surface.

## Risks / Trade-offs

**The seeded fixtures may not exercise every branch.** → The seeder produces twenty-four review states
across at least four distinct due dates, which covers due, overdue and future, but not an empty topic or
a pure backlog. Tests build those with factories rather than relying on the seeder.

**A learner with many topics runs this query per topic.** → Out of scope here and cheap on the existing
index; it only matters once a topic list exists to iterate.

## Migration Plan

Additive. Two configuration keys with defaults, no schema change, no route. Rollback is code-only.

## Open Questions

- The introduction cap's default. Three per day clears an eighty-question source in about a month at five
  a day, which suits the seeded set and is a guess for a real one. Configuration; it can move without
  touching the spec.
