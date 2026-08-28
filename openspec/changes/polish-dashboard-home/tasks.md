## 1. The home surface read path

- [ ] 1.1 Add `config/dashboard.php` holding `recent_source_count` (5) and verify
      `php artisan config:show dashboard.recent_source_count` prints it. The list length is configuration,
      not a literal at the call site — the same rule the cooldown window follows.
- [ ] 1.2 Add `topics()` and `quizzes()` relations to `App\Models\User`, and verify a unit test asserts
      each returns only that user's rows and none belonging to a second user.
- [ ] 1.3 Add `App\Domains\Dashboard\Data\DashboardData` with a `forUser()` factory returning the recent
      sources as `SourceData`, the document total, how many of them are unsettled (`pending` or
      `processing`), the topic total and the question total; verify a unit test builds it for a user with
      four sources — one `ready` with a quiz, one `processing`, one `failed` — and asserts every figure.
- [ ] 1.4 Verify `DashboardData::forUser()` costs a fixed number of queries regardless of how many
      documents exist: a unit test wrapping `DB::listen` (or `assertQueryCount` equivalent) over a user
      with twenty sources asserts the same count as a user with two.
- [ ] 1.5 Verify the recent list is bounded and ordered: a unit test with more sources than
      `recent_source_count` asserts exactly that many are returned, newest first.
- [ ] 1.6 Add `App\Http\Controllers\DashboardController` rendering `Dashboard` with `DashboardData`,
      replace the `Route::inertia('dashboard', 'Dashboard')` line in `routes/web.php` with it behind the
      existing `auth`/`verified` middleware, and verify `php artisan route:list --name=dashboard` shows
      the controller and the existing `DashboardTest` assertions still pass.
- [ ] 1.7 Extend `tests/Feature/DashboardTest.php` with the scoping case the spec requires: a feature test
      asserts a second user's sources appear in neither the props' list nor any count.
- [ ] 1.8 Run `npm run types:check` after regenerating Wayfinder output and verify the frontend picks up
      the new controller action without type errors.

## 2. Shared source presentation

- [ ] 2.1 Add `resources/js/components/SourceStatusBadge.vue` taking a `SourceStatus` and rendering both
      its word and its badge variant from one place, so no colour carries the state alone; verify
      `npm run types:check` passes and every one of the five statuses renders a word.
- [ ] 2.2 Add `resources/js/lib/source.ts` with `mediaTypeLabel`, `formatSize` and `formatUploadedAt`
      moved verbatim from `source/Index.vue`, and verify `npm run types:check` passes.
- [ ] 2.3 Migrate `source/Index.vue` onto both — delete its local `statusLabels`, `statusVariants`,
      `mediaTypeLabel`, `formatSize` and `formatUploadedAt` — and verify the existing
      `tests/Feature/Source` suite still passes and the Sources table renders unchanged.

## 3. The home surface

- [ ] 3.1 Add `resources/js/types/dashboard.ts` matching `DashboardData` and verify `npm run types:check`
      passes with `Dashboard.vue` consuming it.
- [ ] 3.2 Replace the three placeholder tiles in `Dashboard.vue` with the three counts — documents (with
      how many are being analysed), topics, questions — keeping the existing grid geometry and dropping
      `PlaceholderPattern`; verify a feature test asserts the three figures in the props match the user's
      real material.
- [ ] 3.3 Replace the wide placeholder with the recent-documents list — name, state via
      `SourceStatusBadge`, the quiz and its question count where one exists, and when it was uploaded —
      with a link to the full Sources listing built through Wayfinder; verify a feature test asserts a
      `ready` source's quiz title and question count reach the props and an unanalysed one claims neither.
- [ ] 3.4 Add the empty state for a person who has uploaded nothing: one message naming the next step and
      the way to the Sources panel, replacing the surface's content rather than sitting under a row of
      zeroes. Verify a feature test asserts a new person's props carry an empty list and zero counts and
      the page renders without error.
- [ ] 3.5 Verify no figure on the surface reports a capability that does not exist: read the rendered
      page against `specs/dashboard-home` and confirm nothing about a daily quiz, a streak or a score
      appears in any form, including as a zero or a dash.
- [ ] 3.6 Verify the surface does not poll: with a `pending` source present, confirm no repeating request
      is issued from the home surface and the unsettled document is still identifiable in words with the
      way to the Sources listing offered.

## 4. The shell

- [ ] 4.1 Remove `footerNavItems` and the `<NavFooter>` usage from `AppSidebar.vue` along with the now
      unused `BookOpen` and `FolderGit2` imports, leaving `NavFooter.vue` in place unused; verify
      `npm run lint:check` passes and no entry in the rendered sidebar leaves the product.
- [ ] 4.2 Change the Dashboard navigation icon in `AppSidebar.vue` from `LayoutGrid` to `GraduationCap`
      and verify `npm run types:check` passes and the sidebar renders it.
- [ ] 4.3 Replace the Laravel mark in `AppLogoIcon.vue` with the `GraduationCap` glyph on the existing
      rounded primary square, and verify the sidebar header and the auth layouts that use it all render
      without a broken or clipped mark.
- [ ] 4.4 Set `APP_NAME=Quiz` in `.env.example`, and verify the sidebar heading and page titles read
      "Quiz" after updating your own `.env` and running `php artisan config:clear`. Note in the change
      that every deployed environment needs the same edit.

## 5. Quality gates

- [ ] 5.1 Run `vendor/bin/pint --dirty --format agent` and verify it reports no remaining style issues.
- [ ] 5.2 Run `vendor/bin/phpstan analyse` and verify the new code passes at level 7 with no new baseline
      entries.
- [ ] 5.3 Run `npm run lint:check`, `npm run format:check` and `npm run types:check` and verify all three
      pass.
- [ ] 5.4 Run this change's tests with `php artisan test --compact` scoped to `tests/Feature/DashboardTest.php`,
      `tests/Feature/Source` and the new unit tests, and verify every test added by this change passes.
- [ ] 5.5 Verify the two surfaces agree by hand: upload a document, and confirm the state word shown for
      it on the home surface is the same word the Sources panel shows.
- [ ] 5.6 Ask the user to run the complete suite with `php artisan test --compact` and confirm nothing
      pre-existing regressed.
