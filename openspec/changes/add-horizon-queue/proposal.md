## Why

Two numbers in this repository contradict each other, and the contradiction is invisible until someone
uploads a large document.

`GenerateQuiz::$timeout` is 300 seconds, because the agent behind it is allowed 240. The queue's
`retry_after` is 90 seconds, on both the `database` and `redis` connections, because that is what Laravel
ships. Any run that passes 90 seconds is therefore released and handed to a second worker while the first
is still mid-call to OpenAI. `claim()` saves the data — the second run finds the source `processing` and
returns — but an attempt is spent for nothing and the queue marks the job complete while the real one is
still running. A rule that the job already depends on is not stated anywhere: **`retry_after` must exceed
the longest job timeout.**

The second contradiction is memory. `composer dev` runs `queue:listen`, whose default worker limit is
128 MB. Uploads are accepted up to 20 MB (`generation.max_upload_kilobytes`). `StoredDocument::content()`
reads the whole file into one string, the provider driver base64-encodes it (~27 MB), the request body
copies it again, and then a 32k-token structured answer is decoded and hydrated into a Data object
carrying every question and option. A document at the top of the accepted range plausibly peaks at or
past 128 MB. The worker is then SIGKILLed: `failed()` never runs, no `failure_reason` is written, and the
source is left `processing` — the one status `claim()` will not re-enter. The person sees a document that
never finishes and offers no retry.

Underneath both: a generation takes up to four minutes and there is no way to watch one happen. The only
evidence a run produced is a log line written after it ended.

## What Changes

- Add `laravel/horizon` and move the queue onto Redis, which is already running locally in Docker
  (`127.0.0.1:6379`, `maxmemory-policy noeviction`, no password). `QUEUE_CONNECTION` goes from `database`
  to `redis`. Cache and session stay on the database driver and are not touched.
- Raise the `redis` connection's `retry_after` from 90 to 360 so it sits above the job's 300-second
  timeout, and record why the ordering exists rather than leaving it as a number someone later trims.
- Configure the published local Horizon supervisor for work that is slow on purpose: `timeout` 300 to
  match the job, `memory` 512 in place of the 128 default, `tries` 3, and `maxProcesses` 2 — which is, in
  practice, the cap on how many OpenAI calls this application can have in flight at once.
- Have `GenerateQuiz` read `$tries`, `$backoff` and `$timeout` from `config/generation.php` instead of
  holding them as literals, so the job's numbers and the supervisor's numbers cannot drift apart in a
  later edit.
- Add a test over the ordering itself — agent timeout below job timeout below `retry_after` — because
  this is the one class of bug here that no feature test would ever catch.

Deliberately not in this change, each one considered and left out:

- **Splitting queues.** One job type exists. A second queue earns its place when the daily reminder
  notifications land and a short job can actually be stuck behind a long one.
- **Production process management** — supervisord, `stopwaitsecs`, `horizon:terminate` on deploy. There
  is no production host yet, and the deploy step matters only once there is one.
- **The dashboard authorization gate.** `/horizon` exposes job payloads, and Horizon's default gate
  already refuses everything outside `local`. That refusal is exactly right for this change and must be
  replaced with a real gate before the dashboard is ever reachable off this machine.
- **The scheduler** (`horizon:snapshot` for metrics graphs). Nothing is scheduled in this application yet.
- **SQLite write concurrency** under several worker processes.
- **Recovering a source stuck in `processing`.** Raising the memory limit makes the kill less likely; it
  does not make the state recoverable. That is its own change, and it is the one that actually gives a
  person their document back.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

None. This change adds no behaviour and removes none. The behaviour it protects is already written down
in `quiz-generation` — "generation happens once per document, not once per view", and "a failed
generation is recoverable, and says so" — and the point of the change is that the runtime currently
fails to honour those under load, through configuration rather than through code. Writing a new
requirement here would be inventing one to satisfy validation. `.openspec.yaml` sets `skip_specs: true`.

## Impact

- **Dependencies**: `laravel/horizon` (^5.48, which supports Laravel 13 — the 6.x branch does not).
  Running the queue locally now requires Redis; the Docker container already covers it.
- **Configuration**: `config/queue.php` (`retry_after`), `config/horizon.php` (new, published),
  `config/generation.php` (job timing keys), `.env` and `.env.example` (`QUEUE_CONNECTION`).
- **Code**: `app/Domains/Generation/Jobs/GenerateQuiz.php` loses three literals;
  `app/Providers/HorizonServiceProvider.php` is published and registered in `bootstrap/providers.php`.
- **Migrations**: none. Horizon keeps its own state in Redis. The `jobs` table falls out of use and
  stays; `failed_jobs` is still written, since the failed driver remains `database-uuids`.
- **Documentation**: `CLAUDE.md`'s stack table gains Horizon on the "Background work" row — the table is
  fixed by convention and this is the approval landing in it. `README.md`'s setup, "running the app" and
  troubleshooting sections all describe `queue:listen` against a database queue and need rewriting.
- **Tests**: existing suites use `Queue::fake()` and are unaffected by the driver change. One new test.
- **Downstream**: the daily reminder notifications get a working queue to arrive on, and the first
  supervisor to split when they do.
