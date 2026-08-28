## 1. Dependency and configuration

- [ ] 1.1 Require `laravel/horizon:^5.48` — not `^6`, which stops at Laravel 12 — and verify
      `composer show laravel/horizon` reports an installed 5.x version and `composer install` completes
      against Laravel 13.
- [ ] 1.2 Run `php artisan horizon:install`, register the published `App\Providers\HorizonServiceProvider`
      in `bootstrap/providers.php`, and verify `php artisan horizon:status` runs without an exception and
      `config/horizon.php` exists.
- [ ] 1.3 Add the generation timing keys to `config/generation.php` — attempt count 3, backoff `[30, 120]`,
      job timeout 300 seconds — under a heading that states the ordering they belong to (agent timeout
      below job timeout below `retry_after`), and verify `php artisan config:show generation` prints them.
- [ ] 1.4 Raise `retry_after` on the `redis` connection in `config/queue.php` from 90 to 360, with the
      reason written beside it, and verify `php artisan config:show queue.connections.redis.retry_after`
      reports 360.
- [ ] 1.5 Configure the published `local` supervisor in `config/horizon.php`: `queue` `['default']`,
      `maxProcesses` 2, `tries` and `timeout` read from `config/generation.php`, `memory` 512; verify
      `php artisan config:show horizon.environments.local` shows the timeout matching the job's.
- [ ] 1.6 Set `QUEUE_CONNECTION=redis` in `.env` and `.env.example`, leaving `CACHE_STORE` and
      `SESSION_DRIVER` on `database`, and verify `php artisan config:show queue.default` reports `redis`
      and `php artisan tinker --execute 'Illuminate\Support\Facades\Redis::connection()->ping();'`
      answers.

## 2. The job stops holding its own numbers

- [ ] 2.1 Have `App\Domains\Generation\Jobs\GenerateQuiz` read `$tries`, `$backoff` and `$timeout` from
      `config/generation.php` — via `tries()`, `backoff()` and `retryUntil()`/`timeout()` methods rather
      than the literal properties — and verify the existing generation feature tests still pass unchanged.
- [ ] 2.2 Add a unit test over the timing ordering itself: the agent's `#[Timeout]` attribute value is
      below `config('generation.job_timeout')`, which is below
      `config('queue.connections.redis.retry_after')`; verify the test fails when any one of the three is
      moved past its neighbour.

## 3. Proving it end to end

- [ ] 3.1 Run the affected suites — `vendor/bin/pest tests/Unit/Generation tests/Feature/Source` — and
      verify they pass against the Redis-backed connection with `Queue::fake()` still in force where it
      was.
- [ ] 3.2 With `php artisan horizon` running in a second terminal, upload one real PDF through the app and
      verify the run appears in `/horizon` while in flight and the source reaches `ready` with its
      questions stored.
- [ ] 3.3 Upload a document near the 20 MB limit, record the worker's peak memory against the 512 MB
      ceiling, and verify the run completes rather than the worker being killed — this is the measurement
      design.md leaves open, so write the observed figure into `config/horizon.php` beside the `memory`
      value.

## 4. Documentation

- [ ] 4.1 Add Horizon to the "Background work" row of `CLAUDE.md`'s stack table, and verify the row reads
      as the approved stack rather than an undocumented addition.
- [ ] 4.2 Rewrite `README.md`'s setup, running and troubleshooting sections: Redis is now required for the
      queue, `composer dev` still runs `queue:listen` for the fast edit loop, `php artisan horizon` runs
      separately for the dashboard, and a code change is only picked up by Horizon after
      `php artisan horizon:terminate`; verify the troubleshooting section names the Redis container check
      as the first thing to try when an upload sits at `pending`.
- [ ] 4.3 Record the timing-ordering invariant as a path-scoped rule with the Boost `record-rule` tool
      against `config/**`, and verify it appears under `.ai/rules`.

## 5. Quality gates

- [ ] 5.1 Run `vendor/bin/pint --dirty --format agent` and `composer types:check`, and verify both pass.
- [ ] 5.2 Ask for a full `php artisan test --compact` run and verify the suite is green before the change
      is applied.
