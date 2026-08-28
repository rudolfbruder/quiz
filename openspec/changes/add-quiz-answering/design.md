## Context

`add-domain-foundation` shipped the schema, seeded fixtures and a tested `ReviewScheduler`, and no
caller for any of it. See `proposal.md` — Why. Three constraints shape everything below.

**The scheduler is pure and stays pure.** It takes prior state, a grade and an explicit moment, and
returns a decision. Nothing in this change may push a clock read, a query or a model into it.

**The design system has open questions that this change has to close.** `DESIGN.md` says the
quiz-answering surfaces are "the first real test of this system" and lists three things it does not
answer: how correctness reads with a single ink and no red, where the longest prose in the product
goes, and whether the learner sees their retention field mid-quiz. Those are settled here.

**`DESIGN.md` is deliberately ahead of the code.** Its ramp table was re-derived against the
scheduler's retrievability curve while `resources/js/lib/glyph.ts` still runs the superseded cooldown.
Correcting that is part of this change, and `DESIGN.md` is the specification for it.

## Goals / Non-Goals

**Goals:**

- One owner for daily-set composition, unit-testable without HTTP.
- Give `ReviewScheduler` its caller, without leaking persistence into it.
- Close the three open `glyph-grid` questions with decisions a later surface can inherit.
- Make the signed-in retention field incapable of drifting from the server's maths.
- Never send an option's correctness to the browser before the learner has answered it.

**Non-Goals:**

- Optimising selection. One topic holds tens to low hundreds of questions; a straightforward indexed
  query is correct and fast, and the schema already carries the denormalised columns for it.
- A self-reported difficulty control. Grading is derived from the answer alone — see below.
- Migrating every starter-kit surface. Only what the daily journey walks through.

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

### Selection is one query, ordered, then padded

```
   ┌─ due questions ────────────────────────────────────┐
   │ review_states where user, topic, due_at <= now      │
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
— which is what the spec's stability requirement needs. New questions are introduced in authoring
order (`quiz`, then `position`) rather than at random, so a learner meets the material roughly the way
the source presents it, and introduction cannot restart or reshuffle between days.

The due branch fills first, so a backlog suppresses new material with no separate rule for it. That is
the standard spaced-repetition posture — stop introducing while you are behind — arriving as a
consequence of the ordering rather than as a policy someone has to remember.

Alternative considered: interleaving new questions among due ones for variety. Rejected — it would
either grow the daily set or displace a due question, and displacing a due question is the one thing
selection must not do.

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

### Correctness reads as ink plus a word, never ink alone

`DESIGN.md` forbids a second hue and forbids `ink-40` and below from carrying meaning. So correctness
cannot be a colour and cannot be a fade alone.

```
  ── before answering ────────────────────────────────────
     ·  Sardinia                        all options ink-80
     ·  Corsica                         gutter empty
     ·  Sicily                          focus ring = 1px ink-100
     ·  Malta

  ── after answering wrongly ─────────────────────────────
  ┌ INCORRECT ────────────────────────  ink-100, rule above  (DESIGN: an error is a
  │                                                            full-ink thing with a rule)
  │  -  Sardinia          YOUR ANSWER   ink-60 + the words
  │  .  Corsica                         ink-40, decorative
  │  @  Sicily            CORRECT       ink-100
  │  .  Malta                           ink-40
  └────────────────────────────────────
```

The gutter uses ramp characters as themselves — `@` for the answer, `-` for what the learner chose, `.`
for the options that were never in play. `DESIGN.md` already permits typed characters used as typing.
Every meaningful state carries a word beside the ink, so the ramp is reinforcement rather than the
message, and nothing a learner must read sits below `ink-60`.

Alternative considered: fading the wrong choice to `ink-40` for the drama of it. Rejected — it reads
well and breaks the contrast floor on the one line the learner most wants to re-read.

### The explanation is always shown, never disclosed behind a control

It appears below a dashed rule once the question is answered, for a correct answer as well as a wrong
one, at body size and the 60ch measure. No accordion, no "show explanation".

This is the longest prose in a product `DESIGN.md` calls hostile to prose, and hiding it behind a tap
would be the obvious way to protect the grid. It is also the only part of the surface that teaches
anything, and `PRODUCT.md` principle 1 puts the returning learner above the tidy artifact. The seeded
explanations run three or four lines at this measure, which is exactly the paragraph length the type
scale is specified for, so the tension is smaller in practice than it looks in principle.

### The field is not on the answering surface

The retention field appears on the topic surface before a session and on the result surface after it,
and never while a question is open.

```
   topic ──▶ [ field: where you stand ]
     │
     ▼
   quiz  ──▶ [ one question. progress as five ramp cells. nothing else. ]
     │
     ▼
   result ─▶ [ field again: what changed today ]
```

`DESIGN.md` allows one authored moment per surface, and mid-question the field competes with the only
thing that matters. It is also dishonest either way: updating live shows the learner the cell they just
lit before they have seen whether they were right, and not updating shows a stale picture beside fresh
information.

Progress keeps the world's grammar without being the field — five cells, one per question, filling as
they are answered. Field-adjacent, not the field.

### The signed-in field is computed server-side; only the marketing simulation runs in TypeScript

`DESIGN.md` warns that `simulateField` is a display-only reimplementation and that "if the two ever
disagree, the action wins". Rather than maintain that warning, the signed-in field is not a
reimplementation at all: the server computes each question's ink level from `ReviewScheduler`'s own
retrievability function and sends levels, not stabilities. The Vue component renders integers.

`simulateField` survives only for the signed-out simulation, where there is no learner to read, and is
re-derived against the same curve so the marketing picture and the product agree in shape. Drift is
then possible only in a picture of nobody.

`RAMP_MEANING` is rewritten to the retrievability readings in `DESIGN.md`, and `RetentionField`'s
`cooling` readout becomes a due count.

### Correctness never reaches the browser before it is earned

The response `Data` object omits `is_correct` from every option of an unanswered question, and adds it
only for questions the current attempt has already answered. This is a serialisation rule, not a UI
one: an Inertia page ships its props as JSON in the document, so "the UI does not show it" is not a
control.

### World migration is limited to the daily journey

`Dashboard`, the application shell it renders inside, and `Login` move to `glyph-grid`. `Register`,
password reset, email verification, two-factor, passkeys and settings stay on the incumbent shadcn
tokens.

`DESIGN.md` says to migrate a surface when you build on it, not speculatively, and this is the smallest
cut that leaves no seam inside `Welcome → Login → Dashboard → quiz`. It does leave a seam at
`Login → Register` and at settings, which is a known and stated debt rather than an oversight. This
narrows what `proposal.md` describes as "the auth surfaces" to the one auth surface the daily journey
actually crosses.

## Risks / Trade-offs

**A lucky guess on a four-option question is graded `Again`-free and inflates stability.** → Accepted;
there is no cheap fix without a self-report control this change has rejected. The explanation is shown
on every answer including correct ones, which is the partial compensation: a learner who guessed right
still reads why. The append-only log means a future grading change can be replayed over history.

**The five-second threshold may be wrong for long stems.** → It is configuration, not a constant, and
`Easy` only ever promotes. The worst case of a badly tuned threshold is a question pushed further out
than it deserved, which the next lapse corrects.

**The quiz surfaces are the first prose-heavy test of a world built for density.** → This is the real
risk in the change and it cannot be designed away in advance. The mitigation is order of work: build
the question surface first with the seeded Roman Republic content, which is real prose at real length,
and correct the type scale in `DESIGN.md` if it does not hold. Do not build five surfaces on an
unproven one.

**Two selection branches can both come back empty.** → That is a designed state, not a failure: the
spec requires the learner be told nothing is due and when the next question falls due. It needs the
earliest future `due_at` as a second query, which is cheap on the same index.

**Everything is exercised against seeded data only.** → Upload and generation are not in this change,
so the only material is the seeder's two quizzes in one topic for one demo user. Tests must use
factories rather than the seeder for anything the seeder does not happen to produce — a topic with
nothing due, a learner mid-backlog, a question never seen.

## Migration Plan

Purely additive. No schema change, so nothing to roll back but code. Two configuration keys are added
to `config/review.php`: a cap on new questions per day, and the daily interval floor. Both have
defaults, so a deployment that misses them still behaves.

`CLAUDE.md` and `PRODUCT.md` are edited to replace the three-day cooldown with the selection rule
specified here. `DESIGN.md` is already correct and carries a note saying the code has not caught up;
that note is removed as part of the ramp work.

## Open Questions

- The introduction cap's default. Three per day clears an eighty-question source in about a month at
  five a day, which feels right for the seeded set and is a guess for a real one. It is configuration
  and can move without touching specs or tasks.
- Whether the topic surface shows one field per topic or a single combined one. Only matters once a
  learner has more than one topic, which needs upload to be interesting.
