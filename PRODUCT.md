# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Primary user: a self-directed learner with a document they need to remember. Broad audience — no
single school, employer, or profession is assumed. They arrive holding material that is already
theirs (course notes, a manual, a spec, a chapter) and the job is retention: keep this in my head
over days, not cram it once.

The usage scene is short and repeated. The learner spends real effort once at upload, then returns
in small daily windows to answer a handful of questions. Sessions are minutes, not hours, and
frequently arrive by way of a reminder rather than intent.

## Product Purpose

QUIZ AI turns a user's own uploaded document into a question set, then serves it back as a small
daily quiz over time. Success is the return visit: a learner who keeps answering across days and
stops seeing the same questions is being served correctly. A one-time quiz generation that is never
revisited is a failure of the product even when the questions are good.

## Positioning

Two mechanisms define the product and future work must not erode either:

1. **Daily rhythm with a cooldown.** Not a quiz on demand. A short set per topic per day (default 5
   questions), selected to exclude questions already served to that user inside a cooldown window
   (default 3 days), with notifications that bring the learner back. The habit is the product.
2. **The user's own material only.** Every question is generated from a document the user uploaded.
   There is no generic question bank, no borrowed content, no shared corpus.

A third confirmed behavior, not yet framed as positioning: after analyzing a source the app offers
three candidate quiz outlines — different angles over the same material — and the user chooses one
before the full question set is generated.

## Operating Context

The end-to-end flow the interface must serve:

1. **Upload** — an authenticated user uploads a file; the record is created immediately as `pending`
   and the user is never made to wait on a model.
2. **Analyze** — a queued job sends the stored file to the model and asks for three candidate quiz
   outlines.
3. **Choose** — the user picks one of the three proposals; only that one is expanded.
4. **Generate** — a queued job produces the full question set as structured JSON.
5. **Serve daily** — each day the user receives a short quiz per topic, avoiding questions served
   inside the cooldown window.
6. **Remind** — notifications tell the user a fresh quiz is waiting for a topic.

Because steps 2 and 4 are queued, the interface always has real waiting states to design: `pending`,
`processing`, `ready`, and `failed` are visible product states, not implementation detail. A failure
must show a real message and a retry — never a silent empty state.

## Capabilities and Constraints

- Accepted uploads: PDF, DOCX, TXT/MD. Text-bearing documents only — no image-only scans, no OCR.
- Hard limit of 25 pages per document. Over the limit is rejected with a validation error naming the
  real page count; it is never silently truncated.
- One generation run per source at a time. A source already `processing` cannot be re-queued.
- Daily quiz length and the no-repeat cooldown are configurable defaults (5 questions, 3 days), not
  fixed product facts.
- Selection is deterministic per (user, topic, day): a page refresh must not reshuffle the quiz.
- When eligible questions run out, the app prefers the least recently served questions rather than
  serving nothing, and never returns an empty quiz without saying why.
- Authentication is Fortify, with 2FA and passkeys already wired.

Domain vocabulary, used consistently across UI, routes, and copy: **Topic** (subject area a user
studies; quizzes and cooldowns are scoped per topic), **Source** (one uploaded document),
**QuizProposal** (one of three AI-suggested outlines), **Quiz** (the accepted proposal, expanded),
**Question**, **Option** (an answer choice carrying `is_correct`), **Attempt** (one served daily
quiz), **Response** (what the user picked; also the "already asked" record).

Verbs: a user *uploads* a source and *accepts* a proposal; the app *generates* questions, *serves*
an attempt, *scores* it, and *reminds* the user.

## Brand Commitments

- The product is called **QUIZ AI**.
- The interface is in English. Copy still goes through `__()`, but English is the language being
  designed for and the one that constrains label length.
- Nothing else is committed. No logo, no palette, no typographic identity, no voice document exists
  yet. Do not invent one as fact; a visual world is chosen later in its own step.

## Evidence on Hand

None. There are no customers, testimonials, case studies, benchmarks, press mentions, screenshots,
pricing, or usage numbers. The repository is a fresh Laravel starter kit with default auth and
settings pages and no product surfaces built yet.

Future work must not fabricate any of the above. Where a surface would conventionally carry proof,
either use real product demonstration (the actual upload flow, real generated questions) or leave
the slot out.

## Product Principles

1. **The habit outranks the artifact.** Design for the returning learner on day 9, not the impressed
   visitor on day 1. Anything that makes the daily quiz easier to start beats anything that makes
   the generated quiz look more impressive.
2. **Never wait on a model in front of the user.** Upload responds instantly; generation is
   background work. Waiting states are designed states, with honest progress and honest failure.
3. **Their material, plainly theirs.** The user should always be able to see which source a question
   came from. Nothing in the product implies content the user did not provide.
4. **Refuse clearly, do not degrade quietly.** Over the page limit, wrong file type, already
   processing, no eligible questions — each says what happened and what to do, with the real number
   or reason. Silent truncation and empty states are bugs.
5. **Small by default.** Five questions, a minute a day. Resist surfaces that grow the daily
   commitment.

## Accessibility & Inclusion

No standard was established as binding. Default to the craft floor rather than a named target:
keyboard-operable quiz answering, visible focus, sufficient contrast in both themes, and correct
form/error semantics on upload. Revisit if a formal requirement (e.g. WCAG 2.1 AA) is later
confirmed.
