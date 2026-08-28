## Why

The application can hold a quiz, serve it and schedule it, but it cannot yet be given one. Every
question in the database today came from a seeder. Until a signed-in person can upload their own
document and get questions back from it, the product's central promise — *your material, not a
borrowed bank* — is unexercised, and `laravel/ai` is an installed dependency with no caller.

This is the first cut, deliberately narrow: one document in, one question set out, on the dashboard
that already exists behind Fortify's auth.

## What Changes

- Add document upload for a signed-in user: PDF, TXT and MD, size-limited, stored on a disk, with the
  `source` row created immediately as `pending` so the response never waits on anything.
- Add one queued job that attaches the stored file to a single `laravel/ai` agent and receives the
  whole question set back as structured JSON — topic name, quiz title, page count, and the questions
  with their options and explanations.
- Persist the result in one transaction: a topic if the user has none matching, a quiz, its questions,
  and their options, with the source moving `pending` → `processing` → `ready`.
- Enforce the product's hard limits as real refusals: unsupported type and oversize at upload, the
  25-page limit after analysis reports the real count, and one generation run per source at a time.
  Each failure records a reason a person can read.
- Surface all of it on a dedicated Sources panel inside the signed-in application shell, reached from a
  new sidebar entry: an upload form, a table of every document the person has uploaded with its live
  status and the quiz it produced, and a table of the topics those documents have created. `Dashboard`
  is left exactly as it is.
- Add a `Source` policy so a source can only be seen or deleted by the user who uploaded it.

**Deliberately not in this change**, and each is a later one: the three-candidate-outline choice
(`quiz_proposals` gets exactly one auto-accepted row per source here, because `quizzes` requires one);
DOCX, which OpenAI does not accept as a document attachment and which would need a parsing dependency;
notifications; taking the quiz, which `add-daily-quiz` already specifies; and any `glyph-grid` design
migration — this surface is built on the dashboard as it stands today.

## Capabilities

### New Capabilities

- `source-upload`: what a person may upload, what is refused and why, where a document is kept, and
  the lifecycle a source moves through from accepted to ready, failed or rejected.
- `quiz-generation`: turning one stored document into a question set — what the model is asked for,
  what makes returned output acceptable or not, how a topic and quiz come into being, and what the
  person sees while it happens and when it does not work.

### Modified Capabilities

None. `add-domain-foundation` is not archived, so its specs are not yet under `openspec/specs/`, and
neither its data model nor its scheduling requirements change here.

## Impact

- **New code**: `Source` and `Generation` domain actions, one agent, one queued job, request and
  response `Data` objects, a `SourcePolicy`, a `SourceController`, routes in the existing
  `routes/source.php` stub, and a `source/Index.vue` page carrying the upload form and both tables.
- **Modified code**: `AppSidebar.vue` gains a Sources entry, and `app/Models/User.php` gains a `sources`
  relation. `routes/web.php` and `Dashboard.vue` are untouched.
- **Untouched**: every migration. The schema landed by `add-domain-foundation` carries this change
  without alteration.
- **Dependencies**: none added. The provider reads PDF, TXT and MD directly, which is why DOCX is out.
- **Configuration**: `config/generation.php` already holds the page limit, the size limit and the
  accepted types. `config/ai.php` supplies the provider.
- **Cost**: this is the first change that spends money at runtime. One generation run per source, never
  repeated to render a page.
