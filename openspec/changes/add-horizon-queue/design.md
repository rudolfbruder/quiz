## Context

See `proposal.md` — Why. Three facts shape the approach:

- Redis is already running locally in Docker on `127.0.0.1:6379`, with `maxmemory-policy noeviction` and
  no password. The `redis` block in `config/database.php` and the `REDIS_*` values in `.env` already point
  at it correctly. `appendonly` is `no`.
- Horizon is Redis-only. There is no version of this change that keeps the `database` queue driver.
  `laravel/horizon` ^5.48 declares `illuminate/queue ^9.21|^10|^11|^12|^13`; the `6.x-dev` and `dev-master`
  branches stop at `^12` and must not be used.
- `composer dev` runs `queue:listen --tries=1 --timeout=0` as one of four multiplexed processes. The job's
  own `$tries`/`$timeout` properties already win over those CLI flags, so the flags are not the problem —
  the worker's unstated 128 MB memory ceiling is.

## Goals / Non-Goals

**Goals:**

- One place where the timing of a generation run is written down, and one test that fails when the
  ordering between those numbers is broken.
- A worker sized for the largest document the upload rules actually accept, rather than for the default.
- Visibility of a run while it is in flight.

**Non-Goals:**

- Deciding anything about a production host. Every production concern — process supervision, deploy-time
  worker restart, the dashboard gate, the scheduler, SQLite under concurrent writers — is named in the
  proposal and left out on purpose.
- Changing what a generation run does, what it produces, or how failures are reported.
- Moving cache or session off the database driver. They are unrelated to the queue and moving them would
  put application state in a container whose `appendonly` is off.

## Decisions

### Horizon rather than `queue:work` under a process manager

A plain worker with `--timeout=300 --memory=512` fixes the two contradictions in the proposal without any
new dependency, and that is a real alternative. It was rejected because the thing this application most
lacks is a view of a four-minute run while it is happening: queue depth, runtime, wait time, and the
payload of a failed run with a button to retry it. That is the whole reason a dashboard exists, and the
generation job is exactly the workload it is for. The cost is honest and accepted: Redis becomes required
to run the queue, and one more package to keep current.

### One supervisor, one queue

The published `local` supervisor is configured and nothing is added beside it. A second queue exists to
stop a short job being stuck behind a long one, and there is currently no short job — `GenerateQuiz` is
the only queued class in the application. Splitting now would mean choosing queue names, routing, and a
second set of timing numbers to keep aligned, all to separate one job from nothing. The split belongs to
the change that introduces the daily reminder notifications, which is where the problem first appears.

`maxProcesses` is set to 2. It reads as a worker-count setting and is really a spend setting: it is the
number of OpenAI calls this application can have open at once. Two is enough to prove concurrency works
and small enough that a mistake in a loop cannot run up a bill unattended.

### `retry_after` on the connection, not per queue

`retry_after` is a property of a queue connection, so a single `redis` connection carries one value for
everything on it. 360 is chosen as 300 plus a margin, and it means a job that genuinely wedges is invisible
for six minutes rather than ninety seconds. With one job type whose expected runtime is minutes, that is
the correct trade. The alternative — a second connection (`redis-long`) so short work keeps a 90-second
`retry_after` — is real, and is the thing to reach for at the same moment the queue splits, not before.

### The timing numbers live in `config/generation.php`

`GenerateQuiz` currently holds `3`, `[30, 120]` and `300` as literals, and `config/horizon.php` will hold
`300` again as the supervisor timeout. Two files holding the same number is how they come to disagree. The
job reads its values from config; the supervisor's `timeout` reads the same key.

The agent's `#[Timeout(240)]` cannot join them: a PHP attribute argument must be a constant expression, so
it cannot call `config()`. Sharing a class constant between the attribute and the config file was
considered and rejected — it makes `config/generation.php` import a domain agent, which inverts the
direction everything else in this application points. The agent's timeout therefore stays a literal, and
the ordering it participates in is enforced the only way left:

```
agent #[Timeout(240)]  <  job timeout 300  <  redis retry_after 360
        provider gives up      worker gives up      queue re-dispatches
```

A test asserts that chain directly against `config()` and the agent's attribute. It is a config test, not
a feature test, because no feature test can reach the failure: the bug only appears against a real
provider on a real document, which is precisely what the suite is forbidden from doing.

### `memory` 512, from an estimate that is written down

The 128 MB default is wrong; 512 is a judgement, not a measurement. The reasoning is in the proposal —
a 20 MB file becomes a 20 MB string, then ~27 MB of base64, then a copy in the request body, alongside a
decoded 32k-token answer. 512 leaves room for that without leaving so much that a genuine leak goes
unnoticed. What would change it is one run of a document at the top of the accepted range with the
worker's peak memory recorded.

### `composer dev` keeps `queue:listen`; Horizon runs separately

`queue:listen` reloads application code between jobs, which is why the dev script uses it. Horizon does
not: a worker boots the application once and holds it, so a code change is only picked up after
`php artisan horizon:terminate`. Replacing the dev process with Horizon would trade a fast edit loop for
parity with a production configuration that does not exist yet. Instead the dev script is left alone, the
queue name and every timing number live in config where both consumers read them, and Horizon is run in a
second terminal when the dashboard is wanted. The divergence is small and named rather than discovered.

### The dashboard gate is left at Horizon's default

`/horizon` renders job payloads. Horizon's published gate authorises nobody outside the `local`
environment, which is the correct behaviour for a change that is local-only, and no `is_admin` column or
role enum exists to write a better one against. The gate is left exactly as published, and the proposal
records that it must be written before the dashboard is reachable from anywhere but this machine.

## Risks / Trade-offs

- **The Redis container is not running when someone dispatches a job** → the upload request throws a
  connection exception instead of the queue quietly absorbing it, which is the better of the two failures.
  README's troubleshooting section gains the check.
- **`appendonly no` on the container** → restarting it drops everything pending. Acceptable locally, where
  the recovery is re-uploading a test document; recorded because the same setting in production would be
  silent job loss.
- **Horizon workers hold stale code** → the first confusing symptom after this lands will be a code change
  that appears to do nothing. Documented next to the command that fixes it.
- **`retry_after` 360 hides a wedged job for six minutes** → accepted, per the decision above.
- **512 MB is an estimate** → a real measurement is the open question below; being wrong high wastes
  headroom, being wrong low reproduces the bug this change exists to fix.
- **Horizon 6.x does not support Laravel 13** → the constraint is pinned to `^5.48` and the reason belongs
  in the proposal, so a later `composer update` that offers 6.x is not mistaken for a routine bump.
- **The `jobs` table stops being written and stays in the schema** → harmless, and cheaper than a migration
  that would have to be reversed the moment anyone wants the database driver back.

## Migration Plan

There is no data to migrate. The switch is configuration, and the rollback is the same switch.

1. Add the dependency and publish Horizon's config, provider and assets.
2. Add the timing keys to `config/generation.php`; point `GenerateQuiz` and the Horizon supervisor at them.
3. Raise `retry_after` on the `redis` connection.
4. Flip `QUEUE_CONNECTION` to `redis` in `.env` and `.env.example`.
5. Run the suite, then run one real upload end to end with `php artisan horizon` in a second terminal.

**Rollback**: set `QUEUE_CONNECTION=database`. The `jobs` table is untouched and the `database` connection's
own `retry_after` is not changed by this work, so the old path is exactly as it was — with the original bug,
which is the argument for fixing `retry_after` there too if the rollback ever outlives an afternoon.

## Open Questions

- What does a 20 MB PDF actually peak at? Answerable later by running one and reading the worker's memory,
  and it changes a single number in `config/horizon.php` — no spec, no approach and no task moves with it.
