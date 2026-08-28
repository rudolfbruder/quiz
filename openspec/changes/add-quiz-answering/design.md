## Context

`add-daily-selection` decides which questions a learner gets; this change serves them, records the
answers and feeds the grades back to `ReviewScheduler`, which still has no caller. See `proposal.md` —
Why. Three constraints shape everything below.

**The scheduler is pure and stays pure.** It takes prior state, a grade and an explicit moment, and
returns a decision. Nothing in this change may push a clock read, a query or a model into it.

**The visual system is settled elsewhere.** `glyph-grid` was retired by
`retire-glyph-grid-theme`, which deleted `resources/js/lib/glyph.ts` and `resources/js/components/glyph/`
and replaced the token set. `DESIGN.md` (`phosphor-dark`) and the `visual-system` spec are the
authority on appearance; this change reads them at build time rather than restating them here, and
decides only what is specific to answering a question.

## Goals / Non-Goals

**Goals:**

- Give `ReviewScheduler` its caller, without leaking persistence into it.
- Answer what is specific to these surfaces and not covered by `DESIGN.md`: where the explanation goes, and what a day with nothing due looks like.
- Never send an option's correctness to the browser before the learner has answered it.

**Non-Goals:**

- Choosing the day's questions. That is `add-daily-selection`, and this change calls it.
- A self-reported difficulty control. Grading is derived from the answer alone — see below.
- Any styling migration. These surfaces use the tokens the starter kit already uses.

## Decisions

### Grading: wrong is `Again`, right is `Good`, fast and right is `Easy`, `Hard` is never automatic

`add-domain-foundation` deferred this deliberately: "whether `Easy` is ever awarded automatically from
answer speed alone belongs to `add-quiz-taking`, which owns the mapping from a correct answer to a
grade." The four-point scale exists; a single-choice question only tells us correct or incorrect, so
the remaining signal is time.

```
   incorrect                        → Again
   correct, ≥ fast_answer_ms        → Good
   correct, < fast_answer_ms        → Easy
   (nothing)                        → Hard
```

Speed is used only to promote, never to demote. A fast correct answer is strong evidence of easy
recall; a slow correct answer is ambiguous — the learner may have been interrupted, or the stem may
simply be long. Reading a two-line stem and four options and answering correctly inside five seconds is
not something an unsure learner does, which is exactly what makes a hard threshold a usable signal
here. `Hard` stays unawarded rather than being handed to every slow correct answer, because that would
punish long questions and distracted moments identically.

Alternative considered: a self-report control after each answer, the way spaced-repetition tools
conventionally do it. Rejected — `PRODUCT.md` principle 5 is "five questions, a minute a day", and a
second tap on every question is a third more interaction for a signal we can approximate.

The mapping lives as `Rating::fromAnswer(bool $correct, ?int $milliseconds, int $fastThresholdMs)`,
with the threshold passed in rather than read from config inside the enum, so it stays pure and
testable. If `Hard` is ever awarded, this is the one place that changes.

### Determinism is storage, not recomputation

Selection runs once. `StartAttempt` writes a `response` row per selected question at serve time, and
every later read of that day's quiz reads those rows. So "the same set on reload" is not a property of
the query being re-run identically — it is a property of the query not being re-run at all.

```
   POST /topics/{topic}/attempts
        │
        ├─ attempt exists for (user, topic, today)? ── yes ──▶ return it, unchanged
        │
        no
        ▼
   SelectDailyQuestions ──▶ StartAttempt ──▶ one response row per question, served_at set,
                                             option/verdict/answered_at all null
```

`attempts` already carries `unique(user_id, topic_id, served_on)`, so two concurrent starts race into
the constraint rather than into two attempts; the loser catches the violation and returns the winner's
attempt. This is why `served_on` must be bound as a date instance and not a string — the seeder work
already hit that and it is recorded there.

Alternative considered: recomputing selection on every request and never persisting until an answer
arrives. Rejected — it loses the served-but-unanswered record, which the data model exists to keep, and
it makes determinism depend on nothing having changed in `review_states` between two page loads, which
is false the moment the learner answers.

### The scheduler's caller is a single action, and it owns the whole write

`ApplyReview` takes the learner, the question, the grade, the answer time and the moment; reads the
existing `ReviewState` or nothing; calls `ReviewScheduler`; then writes both sides in one transaction —
the `ReviewState` upserted with the returned stability, difficulty, due moment, reps and lapses, and a
`ReviewLog` row appended.

`RecordResponse` fills in the response row and calls `ApplyReview`. Nothing else in the application may
call the scheduler, for the same reason nothing else may prompt a model directly: one caller means one
place where a scheduling change lands.

**The daily interval floor lives here, not in the scheduler.** `min_stability_days` is half a day, and
a quiz served once a day cannot honour that — the same argument `add-domain-foundation` used to drop
sub-day learning steps. The scheduler keeps returning the true value; `ApplyReview` floors the *stored
due moment* at the following day. Stability is not floored, only the due date, so the maths stays
honest and only the serving cadence is respected.

### Correctness is marked, labelled, and legible without colour

After answering, the correct option and the learner's own choice are both marked. The marking follows
whatever `DESIGN.md` and the `visual-system` spec specify at build time — read them then, not now.

The requirement this change adds on top is the one an options list makes non-obvious: correct and
incorrect options sit adjacent by construction, so whatever distinguishes them must survive being read
in greyscale. Each marked option carries an icon and a word ("Correct answer", "Your answer") and the
colour reinforces them. `visual-system`'s requirement "Colour never carries meaning alone" makes that
mandatory rather than a nicety.

### The explanation is always shown, never disclosed behind a control

It appears below a dashed rule once the question is answered, for a correct answer as well as a wrong
one, at body size and the 60ch measure. No accordion, no "show explanation".

This is the longest prose in a product `DESIGN.md` calls hostile to prose, and hiding it behind a tap
would be the obvious way to protect the grid. It is also the only part of the surface that teaches
anything, and `PRODUCT.md` principle 1 puts the returning learner above the tidy artifact. The seeded
explanations run three or four lines at this measure, which is exactly the paragraph length the type
scale is specified for, so the tension is smaller in practice than it looks in principle.

### The answering surface shows one question and its progress

There is no retention visualisation to place — `retire-glyph-grid-theme` deleted the component that drew
it. The decision outlives its subject: if one returns, it belongs on the topic surface before a session
and the result surface after it, never beside an open question. Updating live would show the learner the
effect of an answer before they have seen whether it was right; not updating would show stale data
beside fresh.

The answering surface carries progress — question three of five — and nothing further.

### Correctness never reaches the browser before it is earned

The response `Data` object omits `is_correct` from every option of an unanswered question, and adds it
only for questions the current attempt has already answered. This is a serialisation rule, not a UI
one: an Inertia page ships its props as JSON in the document, so "the UI does not show it" is not a
control.

### No styling migration is sequenced behind this change

`retire-glyph-grid-theme` replaced the token set every shadcn component in the starter kit already
consumes, so these surfaces inherit the current theme by being built from those components. There is no
seam to close and no migration change to wait for.

## Risks / Trade-offs

**A lucky guess on a four-option question is graded `Again`-free and inflates stability.** → Accepted;
there is no cheap fix without a self-report control this change has rejected. The explanation is shown
on every answer including correct ones, which is the partial compensation: a learner who guessed right
still reads why. The append-only log means a future grading change can be replayed over history.

**The five-second threshold may be wrong for long stems.** → It is configuration, not a constant, and
`Easy` only ever promotes. The worst case of a badly tuned threshold is a question pushed further out
than it deserved, which the next lapse corrects.

**These are the first product surfaces built on the current theme, while that theme is still being
reworked.** → Build the question surface first, against the seeded Roman Republic content, which is real
prose at real length. Re-read `DESIGN.md` at that point rather than trusting this document's summary of
it, and do not build the remaining surfaces on an unproven one.

**A day can legitimately have nothing in it.** → A designed state, not a failure. `add-daily-selection`
already returns the earliest future due moment for exactly this, so the surface explains the day rather
than rendering blank.

**Everything is exercised against seeded data only.** → Upload and generation are not in this change,
so the only material is the seeder's two quizzes in one topic for one demo user. Tests must use
factories rather than the seeder for anything the seeder does not happen to produce — a topic with
nothing due, a learner mid-backlog, a question never seen.

## Migration Plan

Purely additive. No schema change, so nothing to roll back but code. Two configuration keys are added
to `config/review.php`: a cap on new questions per day, and the daily interval floor. Both have
defaults, so a deployment that misses them still behaves.

`CLAUDE.md` and `PRODUCT.md` are edited by `add-daily-selection`, not here. `DESIGN.md` needs no change
for this work.

## Open Questions

- The introduction cap's default. Three per day clears an eighty-question source in about a month at
  five a day, which feels right for the seeded set and is a guess for a real one. It is configuration
  and can move without touching specs or tasks.
- Whether the topic surface shows one field per topic or a single combined one. Only matters once a
  learner has more than one topic, which needs upload to be interesting.
