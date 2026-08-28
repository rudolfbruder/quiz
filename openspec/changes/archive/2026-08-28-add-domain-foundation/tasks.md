## 1. Configuration and enums

- [x] 1.1 Create `config/review.php` holding the scheduling constants — `desired_retention`, `decay`, `daily_question_count`, `max_interval_days`, `min_stability_days`, `initial_stability` and `initial_difficulty` keyed by grade, `grade_factor`, `growth_rate`, `stability_brake`, `lapse_factor`, `fast_answer_ms` — and verify `php artisan config:show review` prints every key with its default.
- [x] 1.2 Create `config/generation.php` holding the generation constants — page limit, upload size limit, accepted media types, proposal count, default question count per section — and verify `php artisan config:show generation` prints them.
- [x] 1.3 Add `App\Domains\Source\Enums\SourceStatus` with cases `Pending`, `Processing`, `Ready`, `Failed`, `Rejected`, and `App\Domains\Quiz\Enums\QuizStatus` with cases `Pending`, `Processing`, `Ready`, `Failed`; verify both are string-backed and a unit test asserts the exact case sets.
- [x] 1.4 Add `App\Domains\Question\Enums\QuestionType` with case `SingleChoice`, `App\Domains\Attempt\Enums\AttemptKind` with cases `Daily` and `Practice`, and `App\Domains\Review\Enums\Rating` with cases `Again = 1`, `Hard = 2`, `Good = 3`, `Easy = 4`; verify a unit test asserts `Rating` is integer-backed with those exact values, since the review log stores the integer.

## 2. Migrations

Filenames and order are fixed by the foreign keys — see design.md. Create them in this sequence and do not renumber.

- [x] 2.1 `2026_08_28_100001_create_topics_table` — user, name, slug, timestamps; unique on user and slug; cascade on user delete. Verify `php artisan migrate:fresh` runs clean.
- [x] 2.2 `2026_08_28_100002_create_sources_table` — user, nullable topic, disk, path, original name, media type, size in bytes, nullable page count, nullable extracted text, nullable provider file id, status defaulting to `pending`, nullable failure reason, nullable analysed-at; index on user and status. Verify migration runs clean.
- [x] 2.3 `2026_08_28_100003_create_quiz_proposals_table` — source, position, title, angle, summary, nullable suggested topic name, suggested question count, `outline` json, nullable language, nullable accepted-at; unique on source and position. Verify migration runs clean.
- [x] 2.4 `2026_08_28_100004_create_quizzes_table` — user, topic, source, unique quiz proposal, title, status defaulting to `pending`, nullable failure reason, nullable generated-at; index on user and topic. Verify migration runs clean and the unique proposal constraint rejects a second quiz for the same proposal.
- [x] 2.5 `2026_08_28_100005_create_questions_table` — quiz, type defaulting to `single_choice`, text, explanation, position; index on quiz and position. Verify migration runs clean.
- [x] 2.6 `2026_08_28_100006_create_options_table` — question, text, `is_correct` boolean defaulting to false, position; index on question and position. Verify migration runs clean.
- [x] 2.7 `2026_08_28_100007_create_attempts_table` — user, topic, nullable quiz, kind defaulting to `daily`, nullable served-on date, question count, nullable correct count, started-at, nullable completed-at; unique on user, topic and served-on; index on user and completed-at. Verify migration runs clean.
- [x] 2.8 `2026_08_28_100008_create_responses_table` — attempt, question, nullable option, position, nullable `is_correct`, nullable answered-in-milliseconds, served-at, nullable answered-at; unique on attempt and question. Verify migration runs clean and a row can be inserted with a null option, per the served-before-answered requirement.
- [x] 2.9 `2026_08_28_100009_create_review_states_table` — user, question, topic, stability, difficulty, due-at, last-reviewed-at, reps, lapses; unique on user and question; index on user, topic and due-at. Verify migration runs clean.
- [x] 2.10 `2026_08_28_100010_create_review_logs_table` — user, question, nullable attempt, nullable response, rating, nullable stability and difficulty before, stability and difficulty after, nullable elapsed days, scheduled days, nullable answered-in-milliseconds, reviewed-at; index on user, question and reviewed-at. Verify migration runs clean.
- [x] 2.11 Verify the whole schema end to end: `php artisan migrate:fresh` succeeds from empty, then `php artisan migrate:rollback --step=10` unwinds all ten without a foreign key error.

## 3. Domain models

Every model in this section MUST declare `#[UseFactory]` and, where a policy exists, `#[UsePolicy]`. Auto-discovery cannot resolve either for a model outside `App\Models`, and omitting them fails at test time with a misleading class-not-found error.

- [x] 3.1 Add `App\Domains\Topic\Models\Topic` with its user and quizzes relations, and verify a feature test creates one through its factory and reads it back.
- [x] 3.2 Add `App\Domains\Source\Models\Source` casting status to `SourceStatus` and `analysed_at` to a datetime, with user, topic and proposals relations; verify a test asserts the status cast returns the enum.
- [x] 3.3 Add `App\Domains\Generation\Models\QuizProposal` casting `outline` to an array and `accepted_at` to a datetime, with source and quiz relations; verify a test round-trips an outline of sections through the array cast.
- [x] 3.4 Add `App\Domains\Quiz\Models\Quiz` casting status to `QuizStatus`, with user, topic, source, proposal and questions relations; verify a test asserts the relations resolve.
- [x] 3.5 Add `App\Domains\Question\Models\Question` casting type to `QuestionType`, and `App\Domains\Question\Models\Option` casting `is_correct` to boolean, with their quiz, options and question relations; verify a test creates a question with options and asserts exactly one is correct.
- [x] 3.6 Add `App\Domains\Attempt\Models\Attempt` casting kind to `AttemptKind`, `served_on` to a date and the timestamps to datetimes, and `App\Domains\Attempt\Models\Response` casting `is_correct` to a nullable boolean; verify a test creates an attempt with a response carrying no option.
- [x] 3.7 Add `App\Domains\Review\Models\ReviewState` casting stability and difficulty to floats and `due_at` and `last_reviewed_at` to datetimes, with a scope for states due at a supplied moment; verify a test asserts the scope returns only states due at or before that moment.
- [x] 3.8 Add `App\Domains\Review\Models\ReviewLog` casting rating to `Rating` and the numeric columns to floats, with timestamps disabled in favour of the explicit reviewed-at column; verify a test asserts a written log entry cannot be updated through the model.

## 4. Factories

- [x] 4.1 Add `Database\Factories\Topic\TopicFactory` producing a topic with a slug derived from its name, and verify `Topic::factory()->create()` resolves through the model's `#[UseFactory]` attribute rather than convention.
- [x] 4.2 Add `Database\Factories\Source\SourceFactory` defaulting to `pending` with no page count, plus states for `ready`, `failed` and `rejected` that set a failure reason where the status requires one; verify a test asserts each state produces the documented status and reason combination.
- [x] 4.3 Add `Database\Factories\Generation\QuizProposalFactory` producing a realistic multi-section outline and a suggested topic name, plus an `accepted` state; verify a test asserts the outline survives the array cast with its section titles and focuses intact.
- [x] 4.4 Add `Database\Factories\Quiz\QuizFactory` wiring user, topic, source and proposal consistently so a created quiz shares its source's owner; verify a test asserts the quiz, its source and its topic all belong to the same user.
- [x] 4.5 Add `Database\Factories\Question\QuestionFactory` and `OptionFactory`, with the question factory producing four options of which exactly one is correct; verify a test asserts every generated question has exactly one correct option and no duplicate option text.
- [x] 4.6 Add `Database\Factories\Attempt\AttemptFactory` with `daily` and `practice` states, and `ResponseFactory` defaulting to served-but-unanswered with an `answered` state; verify a test asserts the default state leaves option, correctness and answered-at all empty.
- [x] 4.7 Add `Database\Factories\Review\ReviewStateFactory` accepting a stability and deriving `due_at` from the last review plus that stability, and `ReviewLogFactory`; verify a test asserts a state built with a stability of 12 days is due 12 days after its last review.

## 5. Seeders

- [x] 5.1 Add `Database\Seeders\User\UserSeeder` creating a known demo account, and verify seeding twice does not create a duplicate.
- [x] 5.2 Add `Database\Seeders\Topic\TopicSeeder` and `Database\Seeders\Source\SourceSeeder` producing one topic with two `ready` sources for the demo user; verify a test asserts both sources belong to that user and topic.
- [x] 5.3 Add `Database\Seeders\Generation\QuizProposalSeeder` producing three proposals per source with exactly one accepted, and verify a test asserts no source ends with two accepted proposals.
- [x] 5.4 Add `Database\Seeders\Quiz\QuizSeeder` and `Database\Seeders\Question\QuestionSeeder` expanding each accepted proposal into roughly forty questions with plausible distractors and real explanations; verify a test asserts the seeded set has no duplicate question text and every question has exactly one correct option.
- [x] 5.5 Add `Database\Seeders\Attempt\AttemptSeeder` producing a completed daily attempt and an abandoned one whose responses carry no option; verify a test asserts the abandoned attempt still records every question it served.
- [x] 5.6 Add `Database\Seeders\Review\ReviewSeeder` backdating review states across a spread of stabilities and due dates, some overdue and some far in the future, with matching log entries; verify a test asserts the seeded states are due across at least four distinct dates rather than clustering on one.
- [x] 5.7 Rewrite `DatabaseSeeder` to call every domain seeder in dependency order, and verify `php artisan migrate:fresh --seed` completes from an empty database with no foreign key error.

## 6. Review scheduler

- [x] 6.1 Add `App\Domains\Review\Data\ScheduledReviewData` carrying the grade, the stability and difficulty before and after, elapsed days, granted interval and resulting due moment, with the before-values nullable for a first review; verify a unit test constructs one for a first review with all before-values absent.
- [x] 6.2 Implement `App\Domains\Review\Services\ReviewScheduler` reading its constants from `config/review.php`, taking the current state, a grade and an explicit moment, and returning `ScheduledReviewData`; verify it neither reads the clock nor touches the database, asserted by a unit test with no database and a fixed moment.
- [x] 6.3 Implement retrievability as the power-law decay with `FACTOR` derived from desired retention, and verify unit tests assert recall probability is 1.0 at zero elapsed days, exactly 0.9 at an elapsed time equal to stability, and between 0 and 0.9 far beyond it.
- [x] 6.4 Implement first-review initialisation from grade, and verify a unit test asserts initial stabilities increase and initial difficulties decrease across `Again`, `Hard`, `Good`, `Easy`.
- [x] 6.5 Implement the difficulty update with mean reversion and clamping, and verify unit tests assert difficulty stays within 1 to 10 under repeated `Again` and repeated `Easy`, holds steady at the midpoint under `Good`, and moves toward the midpoint from an extreme.
- [x] 6.6 Implement the stability increase on a successful review, and verify a unit test asserts the progression 3.0, 6.6, 12.7, 22.5, 37.1, 57.8 days within 5% when a new question is graded `Good` at each due moment with midpoint difficulty.
- [x] 6.7 Verify the three moderating factors independently with unit tests: a higher-difficulty question gains less stability than an otherwise identical one; a question reviewed long after its due moment gains more than one reviewed on time; and `Hard`, `Good`, `Easy` produce increasing stability from identical state.
- [x] 6.8 Implement the lapse path, and verify unit tests assert a 22.5-day stability graded `Again` drops below 2 days, that stability never falls under the configured minimum, that it never increases on a lapse, and that the lapse count rises by one.
- [x] 6.9 Implement the interval cap and the desired-retention conversion, and verify unit tests assert a stability beyond the configured maximum yields the capped interval while the stored stability keeps its true value, and that raising desired retention shortens the granted interval while lowering it lengthens it.
- [x] 6.10 Verify determinism with a unit test asserting that scheduling the same state, grade and moment twice returns identical stability, difficulty and due moment, and that a moment in the past is honoured rather than replaced by the present.

## 7. Route stubs and shared wiring

- [x] 7.1 Create empty `routes/topic.php`, `routes/source.php`, `routes/generation.php`, `routes/quiz.php`, `routes/attempt.php` and `routes/review.php`, each carrying only its opening PHP tag and imports, so no follow-on change has to edit a shared routing file.
- [x] 7.2 Add the `require` statements for those six files to `routes/web.php`, and verify `php artisan route:list --except-vendor` still resolves without error.

## 8. Quality gates

- [x] 8.1 Run `vendor/bin/pint --dirty --format agent` and verify it reports no remaining style issues.
- [x] 8.2 Run `vendor/bin/phpstan analyse` and verify the new code passes at level 7 with no new baseline entries.
- [x] 8.3 Run the change's own tests with `php artisan test --compact` scoped to `tests/Unit/Review` and `tests/Feature`, and verify every test added by this change passes.
- [ ] 8.4 Ask the user to run the complete suite with `php artisan test --compact` and confirm nothing pre-existing regressed.
