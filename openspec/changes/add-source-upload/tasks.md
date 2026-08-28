## 1. Upload boundary

- [x] 1.1 Add `App\Domains\Source\Data\StoreSourceData` carrying the uploaded file, with rules drawn
      from `config/generation.php` — accepted extensions and media types, and the size limit in
      kilobytes — and verify a unit test asserts a `.docx` and an oversize file are both rejected while
      a small PDF passes.
- [x] 1.2 Add `App\Domains\Source\Data\SourceData` as the shape the dashboard receives: id, original
      name, media type, size, status, page count, failure reason, uploaded-at, and the derived quiz
      title and question count where one exists; verify a unit test builds one from a `ready` source
      with a quiz and asserts the question count.
- [x] 1.3 Add `App\Domains\Topic\Data\TopicSummaryData` carrying a topic's id, name, slug, how many
      quizzes it holds and how many questions across them, and verify a unit test builds one from a topic
      with two quizzes and asserts the totals.
- [x] 1.4 Add `App\Domains\Source\Policies\SourcePolicy` with `view` and `delete`, wire it with
      `#[UsePolicy]` on the `Source` model, and verify a feature test asserts a second user receives a
      403 for both abilities.
- [x] 1.5 Add a `sources` relation to `App\Models\User` and verify a test asserts it returns only that
      user's sources, most recent first.

## 2. Storing an upload

- [x] 2.1 Add `App\Domains\Source\Actions\StoreUploadedSource` writing the file to a disk and creating
      the `pending` row with disk, path, original name, media type and size in bytes; verify a feature
      test with `Storage::fake()` asserts the file exists on the disk and the row records its real size
      with a null page count and no failure reason.
- [x] 2.2 Add `App\Domains\Source\Actions\DeleteSource` removing the stored file and the row, and verify
      a feature test asserts the file is gone from the fake disk and the quiz, questions and options
      generated from it are removed with it.
- [x] 2.3 Add `SourceController` with `store` and `destroy`, authorising `destroy` through the policy,
      and add both routes to the existing `routes/source.php` stub behind the `auth` middleware; verify
      `php artisan route:list --except-vendor` shows them and a feature test asserts an unauthenticated
      upload is redirected to login.
- [x] 2.4 Verify the upload endpoint refuses a source already `processing`: a feature test asserts
      deleting one returns a validation error naming the state, and the row and file both survive.

## 3. The generation agent

- [x] 3.1 Create the agent with `php artisan make:agent GenerateQuizFromSource --structured`, move it to
      `App\Domains\Generation\Agents`, fix its namespace, and verify `composer dump-autoload` followed by
      a unit test resolving the class passes.
- [x] 3.2 Write the agent's `instructions()` as the system prompt — generate questions only from the
      attached document, one correct option per question, plausible distractors, a real explanation per
      question, and report the document's true page count — and verify a unit test asserts the
      instructions mention the page count and the single-correct-option rule, so the prompt cannot be
      silently emptied.
- [x] 3.3 Configure the agent with `#[Provider]`, `#[Model]`, `#[Temperature]`, `#[MaxTokens]` and
      `#[Timeout]` attributes reading nothing from the call site, and verify a unit test asserts the
      attributes are present on the class.
- [x] 3.4 Define `schema()` returning `topic_name`, `title`, `language`, `page_count`, an `outline` array
      of `{title, focus}` and a `questions` array of `{text, explanation, options[{text, is_correct}]}`,
      every read field `->required()`; verify a unit test asserts `Agent::fake()` with no explicit
      response produces output satisfying every required key.
- [x] 3.5 Verify the agent attaches the stored file rather than pasting text: a test fakes the agent and
      asserts the prompt carried a `Files\Document` attachment built from the source's disk and path.

## 4. Validating what comes back

- [x] 4.1 Add `App\Domains\Generation\Data\GeneratedQuizData` with its nested question and option data
      objects, hydrated from the structured response, and verify a unit test round-trips a realistic
      payload into typed objects.
- [x] 4.2 Enforce the semantic rules on that data — at least one question, at least two options per
      question, exactly one correct option, no duplicate option text within a question, a non-empty
      explanation — and verify unit tests assert each violation throws with a message naming what was
      wrong.
- [x] 4.3 Verify a payload that is schema-valid but semantically empty (zero questions) is rejected by
      the same path, with a unit test asserting the failure message says so.

## 5. Persisting a run

- [x] 5.1 Add `App\Domains\Generation\Actions\PersistGeneratedQuiz` resolving or creating the topic by
      slug for that user, then writing the proposal at position 0 with `accepted_at` set, the quiz, its
      questions and their options, inside one transaction; verify a feature test asserts a full quiz
      lands and the source becomes `ready` with its page count recorded.
- [x] 5.2 Verify topic reuse: a feature test asserts a second source whose generated topic name slugs to
      an existing topic of that user joins it rather than creating a second topic, and that another
      user's identical topic name is untouched.
- [x] 5.3 Verify the write is all-or-nothing: a feature test forcing a failure partway asserts no quiz,
      question, option or proposal row remains and the source is not left `ready`.

## 6. The queued job

- [x] 6.1 Add `App\Domains\Generation\Jobs\GenerateQuiz` with explicit `$tries`, `$backoff` and
      `$timeout`, moving the source to `processing` through a guarded update that succeeds only from
      `pending` or `failed`; verify a feature test asserts a source already `processing` is left
      untouched and the job returns without prompting the agent.
- [x] 6.2 Have the job resolve and call the agent, hydrate and validate the response, and call
      `PersistGeneratedQuiz`; verify a feature test with the agent faked asserts the whole path produces
      a `ready` source and a quiz whose question count matches the fake.
- [x] 6.3 Enforce the page limit in the job: a page count above `config('generation.max_pages')` sets the
      source to `rejected` with a reason naming the actual count and the limit, and stores nothing;
      verify feature tests assert both the rejection and that a count at the limit proceeds.
- [x] 6.4 Add the job's failure handling — a provider exception, an invalid payload, or a storage error
      all set `failed` with a readable reason; verify feature tests cover all three and assert no partial
      quiz survives any of them.
- [x] 6.5 Verify the job re-checks ownership and existence: a feature test asserts a job whose source was
      deleted between dispatch and execution exits without error and without writing anything.
- [x] 6.6 Dispatch the job from `StoreUploadedSource` and verify a feature test asserts the upload
      request itself never prompts the agent, using `Queue::fake()` to assert the job was queued instead.
- [x] 6.7 Log the agent name, source id and token usage on completion, and verify a test asserts the log
      contains none of the prompt or document contents.

## 7. The sources panel

- [x] 7.1 Add `SourceController@index` returning the person's sources as `SourceData` and their topics as
      `TopicSummaryData`, with a `sources` route in the existing `routes/source.php` stub behind the
      `auth` middleware; verify a feature test asserts the page renders with both props and that another
      person's sources and topics are absent.
- [x] 7.2 Add a Sources entry to `AppSidebar.vue` beneath Dashboard, using the Wayfinder route rather
      than a literal URL and a Lucide icon consistent with the existing entries; verify `npm run build`
      succeeds and the entry shows as active when the sources page is open. `Dashboard.vue` and
      `routes/web.php` must not be modified by this change.
- [x] 7.3 Add `resources/js/pages/source/Index.vue` inside `AppLayout` with the upload form, using the
      existing form components and Wayfinder for the action, stating the accepted types and size limit in
      the interface and noting plainly that the document is sent to the AI provider; verify a feature test
      asserts a posted file reaches the controller and a validation error is returned for a `.docx`.
- [x] 7.4 Add the uploads table to that page — original name, media type, size, state, page count where
      known, the quiz title and question count where one exists, and when it was uploaded; verify a
      feature test asserts a `ready` source's quiz title and question count appear in the props.
- [x] 7.5 Add the topics table to that page — topic name, how many quizzes it holds and how many questions
      across them; verify a feature test asserts a person with one topic holding two quizzes sees the
      combined question count.
- [x] 7.6 Show the failure reason for a `failed` or `rejected` source in the uploads table, with a retry
      control for `failed` and a delete control for both; verify a feature test asserts retrying a failed
      source clears its reason and queues the job again.
- [x] 7.7 Poll the sources prop while any source is `pending` or `processing` and stop once all have
      settled; verify by uploading with the `sync` queue driver that the table reaches `ready` without a
      manual reload.
- [x] 7.8 Add the empty state for a person who has uploaded nothing — one message explaining what to do
      rather than two empty tables — and verify a feature test asserts a new person's page carries empty
      collections and renders without error.

## 8. Quality gates

- [x] 8.1 Run `vendor/bin/pint --dirty --format agent` and verify it reports no remaining style issues.
- [x] 8.2 Run `vendor/bin/phpstan analyse` and verify the new code passes at level 7 with no new baseline
      entries.
- [x] 8.3 Run this change's tests with `php artisan test --compact` scoped to `tests/Feature/Source`,
      `tests/Feature/Generation`, `tests/Feature/Topic` and `tests/Unit/Generation`, and verify every test added by this change
      passes with no test reaching a real provider.
- [ ] 8.4 Verify the whole flow by hand once against a real document: upload a short PDF with a queue
      worker running, watch the dashboard reach `ready`, and confirm the stored questions read as real
      questions about that document.
- [ ] 8.5 Ask the user to run the complete suite with `php artisan test --compact` and confirm nothing
      pre-existing regressed.
