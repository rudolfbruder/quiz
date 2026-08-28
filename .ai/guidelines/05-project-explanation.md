# How the app works

A walkthrough of the product and the machinery behind it, for anyone — human or agent — arriving without
context. `00-project-context.md` states the rules; this file explains the thing those rules are about.

## What it is

A person uploads a document they need to remember. The app turns it into questions and gives a few back
each day, timed to arrive just as they are about to forget them.

Two commitments define the product and neither may be eroded:

- **Their material only.** Every question comes from a document the user uploaded. There is no shared
  question bank and no borrowed content.
- **A daily rhythm, not a quiz on demand.** A short set per topic per day. The habit is the product; a
  quiz generated once and never revisited is a failure even when the questions are good.

## The flow, end to end

```
1. UPLOAD     a PDF is stored              → marked pending, the response is instant
2. GENERATE   a queued job sends it to OpenAI → questions, options, explanations
3. SERVE      each day, per topic          → the questions that are due
4. ANSWER     the learner picks an option  → verdict and explanation
5. SCHEDULE   the answer moves the due date → right pushes it out, wrong brings it close
6. REMIND     a notification brings them back
```

Steps 2 and 5 are the two pieces of real machinery. Everything else is plumbing around them.

## What exists today

> Snapshot as of the `add-source-upload` change (August 2026). This section dates faster than the rest
> of the file — check `openspec/changes/` and `openspec/specs/` before trusting it.

```
  BUILT                                NOT BUILT
  ─────────────────────────────        ──────────────────────
  auth (Fortify, 2FA, passkeys)        selecting a day's set
  10 tables, models, factories         serving an attempt
  seeded demo: 80 real questions       answering and scoring
  upload → generate → persist          the scheduler write-back
  the Sources panel                    reminders
  ReviewScheduler, 32 tests   ◀── exists; nothing calls it
```

A document can be uploaded and turned into questions. Nobody can answer them yet. The memory model is
built and proven, and nothing feeds it.

## The memory model

For each person and each question the app holds:

- **stability** — the number of days until recall of that question falls to 90%
- **difficulty** — 1 to 10, how hard this person finds this question
- **due date** — the last review plus the stability

A correct answer increases stability, moderated three ways: a harder question gains less, an already
stable question gains proportionally less, and a question recalled *late* gains more — nearly forgetting
something and then remembering it is stronger evidence than recalling it on time.

```
graded Good at each due moment →  3.0  6.6  12.7  22.5  37.1  57.8 days
graded Again at 22.5 days      →  collapses to 1.7 days, lapse count rises
```

Grading is derived from the answer alone, with no self-rating: incorrect is `Again`, correct is `Good`,
and correct within the configured fast-answer threshold is `Easy`. `Hard` is never awarded
automatically, because a slow correct answer is ambiguous — the learner may simply have been interrupted.

This is a deliberate simplification of FSRS: its concepts, not its twenty-one fitted parameters. The
stored columns and the append-only log are what the full algorithm consumes, so the maths can be
replaced later without a migration, and existing state recomputed by replaying the log.

## How a day's questions are chosen

```
1. questions whose due date has passed, most overdue first
2. if slots remain → never-seen questions, capped per day
3. stop at the daily count (default 5)
```

Four consequences fall out of that ordering rather than needing rules of their own:

- A backlog **pauses new material automatically** — due questions already fill the day.
- The set never grows. Forty overdue still means five today.
- The same person, topic and day always yields the same set, so a refresh cannot reshuffle it.
- An overdue total is never displayed. A debt counter is the most reliable way to stop someone
  returning, and returning is the whole product.

This supersedes an earlier fixed three-day cooldown. A fixed rotation cannot tell an easy question from
one the learner keeps losing.

## The data model

| Entity | What it is |
|---|---|
| `Topic` | the subject area; daily quizzes and scheduling are scoped per topic |
| `Source` | one uploaded document, with its processing status |
| `QuizProposal` | the AI's outline of a source; currently one per source, auto-accepted |
| `Quiz` | the accepted proposal, expanded into questions |
| `Question` / `Option` | the question, its choices, and which are correct |
| `Attempt` / `Response` | one served day, and what was shown and picked |
| `ReviewState` / `ReviewLog` | a person's memory of one question, and its append-only history |

Three decisions in there are easy to undo by accident:

- **A response is written when a question is served, before it is answered.** Otherwise a learner who
  abandons a quiz leaves no trace, every question they saw counts as never shown, and the same set comes
  straight back.
- **Review state is created lazily.** No row means "never reviewed", which is why there is no `new`
  status to keep in sync.
- **`ReviewLog` is immutable.** The model throws on update and on delete. It is what makes a change to
  the scheduling maths recoverable.

## Generation

A queued job attaches the stored file to a `laravel/ai` agent and receives the whole question set back
as structured JSON — never prose that is then parsed. Schema-valid output is still checked before
anything is written: at least one question, at least two options each, exactly one correct, no duplicate
option text, a real explanation. Output that fails is a recorded failure with a readable reason, not a
partial quiz.

The source moves `pending → processing → ready`, or to `rejected` when the document turns out to exceed
the page limit, or `failed` when the run breaks. The move into `processing` is a guarded update that
succeeds only from `pending` or `failed`, which is what stops two runs starting at once.

## Where the code lives

Organised by domain rather than by layer: `app/Domains/{Topic,Source,Generation,Quiz,Question,Attempt,Review}/`,
each owning its own models, actions, data objects, jobs and agents. Controllers stay in
`app/Http/Controllers/<Domain>/`, and `User` stays in `app/Models` because Fortify is bound to it.

The rules that shape all of it are in `20-architecture.md`; the ones most visible in day-to-day code are
that every HTTP boundary crosses a `spatie/laravel-data` object, every model call goes through an agent
class with a declared schema, and no model call ever runs while a request is waiting.

## Seeing it run

Log in, open Sources, and upload a short PDF with a queue worker running. The table polls until the
document reaches `ready` and the questions exist in the database. There is no way to answer them yet.

The seeded demo data is a worked example of the finished shape: two documents on the Roman Republic,
eighty hand-written questions, and backdated review states spread across a range of due dates so the
scheduling has something to show.
