# Project: Quiz

An AI-powered study app. A logged-in user uploads a document (PDF, Word, or plain text). The app stores it, sends
it to OpenAI through the Laravel AI SDK, and generates quiz questions from its content. From then on the user
receives a small daily quiz — questions they have not seen recently — and notifications reminding them to take it.

## The flow, end to end

1. **Upload.** Authenticated user uploads a file. It is validated (type, size, page count) and stored on a disk.
   The upload row is created immediately with a `pending` status; the user is not made to wait.
2. **Analyze.** A queued job sends the stored file to the model as an attachment and asks for **three candidate
   quiz outlines** for that source — different angles/scopes over the same material.
3. **Choose.** The user picks one of the three proposals. Only the chosen one is expanded.
4. **Generate.** A queued job produces the full question set (many questions, each with its own answer options and
   exactly-marked correct option(s)) as **structured JSON output** — never free text that we then parse.
5. **Serve daily.** Each day the user gets a short quiz (default 5 questions) per topic, selected so it avoids
   questions they have already been served inside a cooldown window (default 3 days).
6. **Remind.** Notifications tell the user a fresh quiz is waiting for a topic.

## Hard limits (enforce, do not "handle gracefully" by silently truncating)

- Accepted types: PDF, TXT/MD. Text-bearing documents only for now — no image-only scans, no OCR.
  DOCX is **not** accepted: OpenAI will not take it as a document attachment, so supporting it means a
  local parsing dependency and a second code path. That is its own change, not a config edit.
- Maximum **25 pages** per document. Reject above that with a validation error naming the real page count.
- One generation run per source at a time. A source already `processing` cannot be re-queued.

## Stack (fixed — do not add to it without approval)

| Concern | Choice |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Auth | Laravel Fortify (2FA + passkeys already wired) |
| AI | `laravel/ai` (Laravel AI SDK), OpenAI provider — **the only** way we call a model |
| Boundary types | `spatie/laravel-data` — **all** input and output |
| Views | Inertia v3 + Vue 3 (`<script setup lang="ts">`) |
| Styling | Tailwind CSS v4 only; design work goes through the `impeccable` skill |
| Routing to JS | Laravel Wayfinder (generated) |
| Background work | Laravel queues + notifications |
| Tests | Pest 5 |
| Static analysis | Larastan / PHPStan level 7 |
| Spec workflow | OpenSpec (`openspec/`) |

## Non-negotiables

These override any generic Laravel habit. Violating one is a bug, not a style choice.

1. **Code is organised by domain, not by layer.** `app/Domains/<Domain>/{Models,Data,Actions,Services,Jobs,
   Notifications,Agents,Enums,Policies}`. `User` and controllers keep their Laravel-default homes. See
   `.ai/20-architecture`.
2. **Every HTTP boundary crosses a `Data` object.** No `FormRequest` in new code, no API Resources, no raw arrays or
   Eloquent models handed to `Inertia::render()`. See `.ai/30-data-objects`.
3. **All model calls go through a `laravel/ai` agent class** in the owning domain's `Agents/` folder. No raw HTTP to OpenAI, no other
   SDK, no inline prompt strings in controllers or jobs. See `.ai/35-ai-generation`.
4. **Any AI output the app persists must come from a declared JSON schema** (`HasStructuredOutput`). Never parse
   prose, never `json_decode()` a model reply.
5. **Model calls never happen in the request cycle.** Upload responds immediately; generation runs queued.
6. **Fortify owns authentication.** See `.ai/60-auth`.
7. **Tailwind utilities only.** See `.ai/80-styling`.
8. **Wayfinder for every URL in JS.** See `.ai/70-frontend`.
9. **Every change ships with a test**, with AI faked. See `.ai/90-testing`.
10. **Every feature starts as an OpenSpec change.** See `.ai/10-workflow`.

## Domain vocabulary

Use these names consistently — tables, models, Data objects, routes, Vue pages, notifications. The exact columns
and relations are pinned per feature in its OpenSpec change; the *names* are settled here.

- `Topic` — the subject area a user studies ("the domain"). Daily quizzes and cooldowns are scoped per topic.
- `Source` — one uploaded document: disk path, original name, mime type, page count, extraction status.
  Named `Source`, never `Document` — `Laravel\Ai\Files\Document` already owns that name in our imports.
- `QuizProposal` — one of the three AI-suggested outlines for a source. Exactly one gets accepted.
- `Quiz` — the accepted proposal, expanded. Belongs to a source and a topic; has many questions.
- `Question` — belongs to a quiz; has many options and an explanation.
- `Option` — one answer choice on a question; carries the `is_correct` flag.
- `Attempt` — one served daily quiz for one user: the questions given, when, and the resulting score.
- `Response` — the option(s) a user picked for one question inside one attempt. This table is also the
  "already asked" record the no-repeat selection reads.

Verbs: a user *uploads* a source, *accepts* a proposal; the app *generates* questions, *serves* an attempt,
*scores* it, and *reminds* the user.

## Selection rules (the part that is easy to get wrong)

- A question is **eligible** for a user's daily quiz when it has not been served to that user within the cooldown
  window (default 3 days, configurable — not hardcoded at the call site).
- If eligible questions run out, prefer the **least recently served** ones rather than serving nothing or repeating
  yesterday's set. Never return an empty quiz silently — say why.
- Selection is deterministic per (user, topic, day) so a page refresh does not reshuffle the quiz.
- Selection logic lives in one action (`SelectDailyQuestions`) and is unit-tested directly. Do not scatter
  `whereNotIn` fragments across controllers.

## Language

- All user-facing strings go through `__()`, including notification bodies.
- Code, comments, commit messages, and specs are written in English.
