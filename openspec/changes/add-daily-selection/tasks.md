## 1. Configuration and documentation

- [ ] 1.1 Add `max_new_per_day` and `daily_interval_floor_days` to `config/review.php` with documented
      defaults, and verify `php artisan config:show review` prints both.
- [ ] 1.2 Replace the three-day cooldown in `CLAUDE.md`'s "Selection rules" section with the rule this
      change implements — due first, most overdue leading, new questions capped, deterministic per
      learner, topic and day — and verify no reference to a cooldown window remains in that file.
- [ ] 1.3 Reframe `PRODUCT.md`'s first positioning mechanism so the promise is unchanged and the
      mechanism is the scheduler, and verify the file no longer states a fixed three-day window.

## 2. Selection

- [ ] 2.1 Add `App\Domains\Attempt\Data\DailySelectionData` carrying the chosen questions in order, the
      count that were due, the count newly introduced, and the earliest future due moment when the set is
      short or empty; verify a unit test constructs one for an empty day with the next due moment present.
- [ ] 2.2 Add `App\Domains\Attempt\Actions\SelectDailyQuestions` taking a user, a topic and an explicit
      moment, reading its limits from `config/review.php`; verify a unit test asserts it never reads the
      clock by scheduling twice with a fixed moment and getting identical results.
- [ ] 2.3 Implement the due branch — review states for that user and topic due at or before the moment,
      ordered by due date then question id; verify a test asserts a question due in four days is excluded
      while one due yesterday is included.
- [ ] 2.4 Implement most-overdue-first ordering and the daily cap; verify a test with forty due questions
      and a daily count of five asserts the five longest overdue are returned, in that order, and the set
      is never larger than the cap.
- [ ] 2.5 Implement the new-question branch — questions in the topic with no review state for that user,
      ordered by quiz then position — filling only the remaining slots and never more than
      `max_new_per_day`; verify tests assert a quiet day introduces up to the cap, a busy day introduces
      none, and an empty due queue still introduces only the cap rather than a full set.
- [ ] 2.6 Verify determinism across a day boundary: a test asserts two calls with the same user, topic and
      moment return identical question ids in identical order, and that a different moment on the same day
      does not reshuffle the set.
- [ ] 2.7 Implement the short and empty cases — return what exists, and report the earliest future due
      moment so a caller can explain the day; verify tests assert a topic with two due questions returns
      two with the reason available, and a fully-held topic returns none with the next due moment set.
- [ ] 2.8 Verify scoping: a test asserts selection considers only the requested topic and only the
      requesting learner's own review history, with a second learner's state for the same question having
      no effect.

## 3. Demonstration

- [ ] 3.1 Add an Artisan command `quiz:today {user} {topic}` printing the selected questions with why each
      was picked — due since when, or newly introduced — and verify running it twice against the seeded
      database prints the same set both times.
- [ ] 3.2 Verify the command against the seeded data by hand: run it for the demo user and the seeded
      topic, confirm five questions come back, and confirm the reasons match what the review states say.

## 4. Quality gates

- [ ] 4.1 Run `vendor/bin/pint --dirty --format agent` and verify it reports no remaining style issues.
- [ ] 4.2 Run `vendor/bin/phpstan analyse` and verify level 7 passes with no new baseline entries.
- [ ] 4.3 Run `php artisan test --compact` scoped to `tests/Unit/Attempt` and `tests/Feature/Attempt` and
      verify every test added by this change passes.
- [ ] 4.4 Ask the user to run the complete suite and confirm nothing pre-existing regressed.
