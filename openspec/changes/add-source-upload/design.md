## Context

See `proposal.md` — Why. The schema, models and factories from `add-domain-foundation` are in place and
this change adds no migration. Three constraints from that foundation shape the approach, and one of
them forces a decision that is not obvious.

**`quizzes.quiz_proposal_id` is `NOT NULL` and `UNIQUE`.** The foundation modelled the product's real
flow, in which a source yields three candidate outlines and the person accepts one. This change skips
the choosing step, and therefore cannot create a quiz without first creating something for it to point
at.

**No model call may run in a request cycle**, and anything persisted must arrive through a declared
JSON schema. Both are non-negotiables in `CLAUDE.md`.

**The page count is unknown at upload.** The foundation left `sources.page_count` nullable for exactly
this reason and made `rejected` a status distinct from `failed`: a document accepted in good faith and
refused afterwards is a different event from analysis breaking.

## Goals / Non-Goals

**Goals:**

- One document in, one question set out, with every product limit enforced as a real refusal.
- Leave the three-outline flow cheap to add later — ideally a change that adds a screen rather than one
  that reworks what this change stores.
- Keep every failure legible to the person and recoverable without support.

**Non-Goals:**

- Extraction. The provider reads the document; the application never parses one.
- Streaming, progress percentages, or partial results. A run is pending, running, or finished.
- Retry sophistication. A small fixed number of attempts with backoff, then a readable failure.

## Decisions

### One auto-accepted proposal per source, rather than making the column nullable

The alternative was a migration making `quizzes.quiz_proposal_id` nullable. Rejected: it would weaken a
constraint the product genuinely wants, and it would leave the later three-outline change to re-tighten
it against rows that no longer satisfy it.

Instead the generation agent returns the same outline it would have returned as a candidate, and the
job writes it as a single `QuizProposal` at position 0, accepted at the moment of writing.

```
   source ──▶ agent ──▶ { topic, title, page_count, outline, questions[] }
                              │
                              ├─▶ QuizProposal  position 0, accepted_at = now
                              └─▶ Quiz ─▶ Questions ─▶ Options
```

This costs one row and buys three things: no schema change, an honest record of what the model
proposed, and a later change that only has to ask for three outlines instead of one and add a choosing
screen. Nothing written here has to be migrated when that happens.

### One agent, one schema, returning the whole quiz

`App\Domains\Generation\Agents\GenerateQuizFromSource`, configured by attributes rather than numbers at
the call site, with the source's stored file attached via `Files\Document::fromStorage($path, $disk)`.
Its schema returns:

```
   topic_name          string, required
   title               string, required
   language            string, required
   page_count          integer, required
   outline[]           { title, focus }, required
   questions[]         { text, explanation, options[] { text, is_correct } }, required
```

`page_count` is asked of the model because nothing else in the application can count pages without a
parsing dependency, which the foundation explicitly declined to take. This is the weakest link in the
change and is recorded as a risk below.

Note the agent lives under the owning domain, not `App\Ai\Agents` where `make:agent` puts it. Move it
after generating, per `CLAUDE.md`.

### The job owns the lifecycle; the action owns the write

`App\Domains\Generation\Jobs\GenerateQuiz` holds `$tries`, `$backoff`, `$timeout`, the status
transitions and the failure handler. It resolves and calls one action. No domain logic lives in it.

```
   pending ──▶ processing ──▶ ready
                   │
                   ├──▶ rejected   page count over the limit
                   └──▶ failed     provider error, unusable output, storage error
```

The transition into `processing` is a guarded update — it succeeds only from `pending` or `failed` — so
a duplicate dispatch loses the race rather than starting a second run. That satisfies the "one run at a
time" requirement without a lock table. `failed` is re-enterable so a retry works; `processing` is not.

`PersistGeneratedQuiz` does the whole write in one transaction: topic resolved or created, proposal,
quiz, questions, options, then the source moved to `ready` with its page count. Any throw rolls the lot
back and the job's failure handler records the reason.

### Model output is hydrated into Data objects and validated before the database

Schema-valid output can still be semantically wrong, which the foundation's design already anticipated:
zero correct options, duplicate distractors, an empty set. The structured response is hydrated into
`GeneratedQuizData` and validated on the rules the spec lists. A violation throws, which the job turns
into a `failed` status with a reason naming what was wrong.

Treating model output as untrusted input is the point. Nothing goes near the database on the strength of
the provider having returned two hundred OK.

### The topic is matched on slug, per user

The model names a topic; the application slugs it and looks for one the person already owns. `topics`
carries `unique(user_id, slug)`, so matching on slug is both the natural key and the constraint that
prevents a duplicate. A second document the model calls "Roman Republic" joins the first one's topic
rather than creating "roman-republic-2".

Alternative considered: always create a topic per source. Rejected — daily quizzes and the cooldown are
scoped per topic, so a topic per document would fragment a subject into unrelated daily queues.

### The panel is its own page in the existing shell, not the dashboard

Uploading gets a dedicated `Sources` page reached from a new entry in `AppSidebar.vue`, inside the
`AppLayout` shell the starter kit already provides. `Dashboard` and `routes/web.php` are not touched.

```
   AppSidebar
     ├── Dashboard          unchanged
     └── Sources    ◀── new
            │
            ▼
     source/Index.vue
       ├── upload form
       ├── uploads table   name, type, size, state, pages, quiz, questions, when
       └── topics  table   name, quizzes, questions
```

There is no role system in this application — no roles table, no admin middleware, no admin concept in
`PRODUCT.md` — so "admin panel" is read as the signed-in application area behind Fortify's `auth`
middleware, which is the only privileged area that exists. Roles and permissions arrive later as their
own change, on `spatie/laravel-permission`. This page is where that change will attach: the route group
gains a permission middleware and `SourcePolicy` gains a role check, with nothing else here moving. The
policy exists in this change partly so that hook is already in place.

A separate page rather than a dashboard section, because the dashboard is the daily-quiz surface in
`add-daily-quiz` and uploading is a rarer, heavier activity: `PRODUCT.md` describes real effort once at
upload, then short daily returns. Those two rhythms do not belong on one screen.

The page polls only while at least one source is pending or processing, using Inertia's polling with a
partial reload of the sources prop, and stops once everything has settled. No broadcasting, no
websockets — the work takes seconds and the page is open in front of the person.

Alternative considered: broadcasting the status change. Rejected as premature for a surface that is open
for the duration of the work it is watching.

### Authorization is a policy, not a scoped query

`SourcePolicy` with `view` and `delete`, wired by `#[UsePolicy]` on the model because auto-discovery
cannot resolve a policy for a model outside `App\Models`. The foundation added no policies, so this
change also establishes the pattern: attribute on the model, never convention.

The job re-checks ownership rather than trusting its payload, since a source can be deleted between
dispatch and execution.

## Risks / Trade-offs

**The model's reported page count may be wrong, and the 25-page limit depends on it.** → Accepted for
this cut, and it is the change's weakest point. The alternative is a PDF parsing dependency the
foundation declined for good reasons — byte-level heuristics break on compressed object streams. The
mitigations are that the limit is enforced after a real read of the document rather than guessed, the
recorded reason names the count so a wrong refusal is visible and arguable, and a rejected source can be
deleted and re-uploaded. If it proves unreliable in use, a parsing dependency becomes its own change
with evidence behind it.

**A large document produces a large response and may hit the token ceiling.** → `#[MaxTokens]` is set on
the agent, and a truncated response fails structural validation rather than storing a partial quiz. The
person sees a failure with a reason, not half a quiz.

**This is the first change that spends money per run.** → One run per source, guarded against
re-queueing, never triggered by a page view. Usage is logged per run with the agent, source id and token
count — never prompt or document contents.

**Uploads are stored on the local disk.** → Fine for now and configurable. It becomes a deployment
concern the moment there is more than one application server, and is worth naming before that day rather
than after.

**A person could upload something confidential.** → It leaves the application for the provider by
design, which is the product. Worth stating plainly on the upload surface rather than in a policy
document nobody reads.

## Migration Plan

Additive. No migration, no new dependency. Deployment needs a queue worker running and an OpenAI key
present; without a worker, uploads sit at `pending` forever, which is visible on the dashboard rather
than silent. The `sync` queue driver works for local development and makes the whole flow observable in
one request.

Rollback is code-only. Sources already stored keep their files and rows.

## Open Questions

- Whether a rejected or failed source should be deleted automatically after some time, or left for the
  person to clear. Left to the person here; a cleanup policy needs usage to design against.
- How many questions to ask for. The agent asks for a count derived from the outline's sections and
  `config('generation.questions_per_section')`, which is a guess until real documents go through it.
