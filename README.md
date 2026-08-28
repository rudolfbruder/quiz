# Quiz

An AI-powered study app. You upload a document you need to remember, the app turns it into questions,
and gives a few back each day — timed to arrive just as you are about to forget them.

Two commitments define the product:

- **Your material only.** Every question comes from a document you uploaded. There is no shared question
  bank and no borrowed content.
- **A daily rhythm, not a quiz on demand.** A short set per topic per day. The habit is the product.

Full product walkthrough: [`.ai/guidelines/05-project-explanation.md`](.ai/guidelines/05-project-explanation.md).

---

## The flow, end to end

```
1. UPLOAD     a PDF is stored                  → marked pending, the response is instant
2. GENERATE   a queued job sends it to OpenAI  → questions, options, explanations
3. SERVE      each day, per topic              → the questions that are due
4. ANSWER     the learner picks an option      → verdict and explanation
5. SCHEDULE   the answer moves the due date    → right pushes it out, wrong brings it close
6. REMIND     a notification brings them back
```

Steps 2 and 5 are the real machinery. Everything else is plumbing around them.

**What runs today:** steps 1 and 2. A document can be uploaded and turned into questions. The review
scheduler (step 5) is built and covered by tests, but nothing calls it yet — there is no way to answer a
question in the UI. Serving, answering, scoring and reminders are not built.

Check `openspec/changes/` and `openspec/specs/` for the current state before trusting that summary.

---

## Requirements

| | |
|---|---|
| PHP | 8.4 (8.3 is the composer floor; the project targets 8.4) |
| Composer | 2.x |
| Node | 20+ with npm |
| Database | SQLite by default — no server needed |
| OpenAI | An API key, for generation only |

---

## Setup

```bash
git clone <repo> quiz && cd quiz

composer setup      # install, copy .env, generate key, migrate, npm install, npm run build
```

`composer setup` does not create the SQLite file. If `database/database.sqlite` does not exist yet:

```bash
touch database/database.sqlite && php artisan migrate
```

Then add your OpenAI key to `.env`:

```dotenv
OPENAI_API_KEY=sk-...
```

`config/ai.php` defaults to the `openai` provider, so this is the one credential the app cannot run
generation without. Everything else in `.env.example` works as shipped: SQLite, database queue, database
sessions and cache, mail to the log.

Finally, seed the demo data:

```bash
php artisan db:seed
```

That gives you one account — `test@example.com` / `password` — with two documents on the Roman Republic,
eighty hand-written questions, and backdated review states spread across a range of due dates so the
scheduling has something to show. Seeding is keyed on the email address, so re-running it does not
duplicate the account or overwrite a password you changed.

---

## Running

```bash
composer dev
```

This starts four processes at once: the PHP server, **a queue worker**, log tailing (Pail), and Vite.
The queue worker is not optional — generation runs on the queue, so without it an upload sits at
`pending` forever.

The app is at http://localhost:8000. Log in with the demo account above.

If you would rather run the pieces yourself:

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
```

---

## Checking the main process

The only complete path through the app today is **upload → generate → ready**. Here is how to verify it
is working, from the outside in.

### 1. Confirm the queue worker is running

Generation is a queued job with a 300-second timeout. If nothing is consuming the queue, an upload will
look like it hung. `composer dev` covers this; if you started the server by hand, start a worker too.

### 2. Upload a document

Log in and open **Sources** (`/sources`). Upload a short PDF — a few pages is enough to prove the path,
and cheaper than a long one.

Accepted: **PDF, TXT, MD only**, up to 20 MB and **25 pages**. DOCX is deliberately not accepted: OpenAI
will not take it as a document attachment. There is no OCR, so an image-only scan will produce nothing
useful.

### 3. Watch the status move

The Sources table polls. The source walks through the lifecycle owned by
`app/Domains/Generation/Jobs/GenerateQuiz.php`:

```
pending ──▶ processing ──▶ ready
                │
                ├──▶ rejected   the document is longer than the 25-page limit
                └──▶ failed     provider error, unusable answer, storage error
```

The move into `processing` is a guarded update that only succeeds from `pending` or `failed`. That is
what stops two runs starting on the same source at once. A failure records a readable reason and offers
a retry — never a silent empty state.

Watch it from the terminal instead if you prefer:

```bash
php artisan pail                                    # live logs, including the job
php artisan queue:listen -v                         # per-job start/finish lines
```

### 4. Confirm the questions landed

```bash
php artisan tinker --execute 'App\Domains\Source\Models\Source::latest()->first()->only(["id","original_name","status","failure_reason"]);'
php artisan tinker --execute 'App\Domains\Question\Models\Question::count();'
```

A `ready` source with new questions attached is the whole path working. There is no UI to answer them
yet — that is expected, not a bug.

### 5. When it fails

`failure_reason` on the source is the first place to look; it is written by the job and truncated to
1000 characters. Common causes, in rough order of likelihood:

- **`OPENAI_API_KEY` missing or wrong** — the job records a provider error after three attempts with a
  30s then 120s backoff.
- **`rejected`** — the document is over 25 pages. The real page count is only known after analysis, which
  is why this is a status rather than an upload validation error.
- **`failed` with an unusable-output reason** — the model returned schema-valid JSON that did not survive
  the semantic checks: at least one question, at least two options each, exactly one correct, no
  duplicate option text, a real explanation. Partial quizzes are never written.

The job retries three times on its own. Beyond that, use the retry action on the Sources row.

---

## Configuration worth knowing

| File | Controls |
|---|---|
| `config/generation.php` | page limit (25), upload size (20 MB), accepted types, proposal count, questions per section |
| `config/review.php` | the memory model: desired retention, decay, daily question count (5), interval bounds, stability growth, difficulty, lapse, fast-answer threshold |
| `config/ai.php` | provider selection; `default` is `openai` |

Nothing in these is hardcoded at a call site. If you find a magic number in a controller or action, that
is a bug.

---

## Quality gates

```bash
composer test          # clears config, then Pint --test, PHPStan, and the full Pest suite
composer lint          # Pint, fixes in place
composer types:check   # PHPStan (Larastan) at level 7
php artisan test --compact
```

Run the narrowest set while working:

```bash
php artisan test --compact tests/Feature/Source
vendor/bin/pest --filter=generatesQuiz
```

The suite currently runs 193 tests. Tests never hit a provider — the agent is faked, and the failure
paths (provider throws, schema-valid but semantically wrong output) are covered explicitly.

Before finalising any PHP change:

```bash
vendor/bin/pint --dirty --format agent
```

---

## Where the code lives

Organised by domain, not by layer:

```
app/Domains/<Domain>/{Models,Data,Actions,Services,Jobs,Notifications,Agents,Enums,Policies}
app/Http/Controllers/<Domain>/     thin HTTP layer
app/Models/User.php                stays here — Fortify is bound to it
resources/js/pages/<domain>/       Inertia pages mirror the domain names
routes/<domain>.php                one route file per domain, required from web.php
tests/{Feature,Unit}/<Domain>/
```

Domains: `Topic`, `Source`, `Generation`, `Quiz`, `Question`, `Attempt`, `Review`, `User`.

Domain models need `#[UseFactory]` and `#[UsePolicy]` attributes — Laravel's auto-discovery guesses
`App\Models\X`, which is wrong here, and the failure surfaces as a confusing "class not found" at test
time.

---

## Stack

| Concern | Choice |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Auth | Laravel Fortify (2FA + passkeys wired) |
| AI | `laravel/ai` with the OpenAI provider — the only way we call a model |
| Boundary types | `spatie/laravel-data` — all input and output |
| Views | Inertia v3 + Vue 3 (`<script setup lang="ts">`) |
| Styling | Tailwind CSS v4 |
| Routing to JS | Laravel Wayfinder (generated) |
| Background work | Laravel queues + notifications |
| Tests | Pest 5 |
| Static analysis | Larastan / PHPStan level 7 |
| Spec workflow | OpenSpec (`openspec/`) |

Do not add to it without approval.

---

## Documentation map

| File | What it holds |
|---|---|
| `.ai/guidelines/05-project-explanation.md` | how the app works — the product, the memory model, the data model |
| `.ai/guidelines/00-project-context.md` | the rules those explanations are about |
| `.ai/guidelines/10-workflow.md` | OpenSpec workflow, skills, order of work |
| `.ai/guidelines/20-architecture.md` | the domain layout and what each building block is for |
| `.ai/guidelines/35-ai-generation.md` | agents, schemas, queued generation |
| `.ai/rules/` | narrower, path-scoped rules discovered along the way |
| `PRODUCT.md` | product intent |
| `DESIGN.md` | the design system |
| `openspec/` | proposals, delta specs and task lists — every feature starts here |
