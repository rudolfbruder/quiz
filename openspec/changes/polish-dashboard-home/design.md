## Context

See `proposal.md` — Why. Three facts about the current code shape the approach:

- `dashboard` is `Route::inertia('dashboard', 'Dashboard')`. There is no controller and nothing crosses
  the boundary, so the page has never had props to render.
- Only `routes/source.php` has routes. `topic.php`, `quiz.php`, `attempt.php`, `review.php` and
  `generation.php` are empty stubs. Models and migrations exist for all of them; HTTP surfaces do not.
  Anything the home surface reports must come from sources, topics and quizzes.
- `SourcePanelData::forUser()` loads *every* source with its quiz eager-loaded. The home surface wants a
  bounded slice plus three aggregates. That is a different query set, not a filter over the same one.

The domain layout puts every Data object inside a domain (`.ai/20-architecture`), and the home surface
reads from three of them.

## Goals / Non-Goals

**Goals:**

- One read path for the home surface, owned somewhere that stays correct when the daily quiz lands.
- A bounded, predictable query count that does not grow with the number of documents a person has.
- Source status vocabulary and formatting stated once, used by both surfaces.

**Non-Goals:**

- Any write path. The home surface reads; upload, retry and delete stay on the Sources panel.
- Caching or denormalised counters. At this scale the aggregates are cheap; adding a counter column is a
  change of its own with invalidation to own.
- Restyling the Sources panel. It gives up its local status maps and formatters and is otherwise left
  alone.

## Decisions

### A `Dashboard` domain owns the home surface's Data object

`DashboardData` composes sources, topics and quizzes. Putting it in `Domains\Source\Data` makes the
Source domain own a shape that is about to hold attempt and review figures — wrong within one change.
A top-level `app/Data/` contradicts `.ai/20-architecture` and needs approval. So:
`App\Domains\Dashboard\Data\DashboardData`, with `App\Http\Controllers\DashboardController`.

The domain owns no model, and that is intended: `.ai/20-architecture` defines a domain as a vertical
slice, and the home surface is a read-only slice. It depends on `Source`, `Topic` and `Quiz` through
their public API and is depended on by nobody, so the one-way dependency rule holds.

*Alternative considered*: no Data object, arrays straight into `Inertia::render()`. Rejected outright —
non-negotiable #2.

### `DashboardData` composes the existing `SourceData` rather than a new row shape

`SourceData::fromModel()` already carries name, media type, size, status, page count, failure reason,
uploaded-at, quiz title and question count — a superset of what the home list shows, and the frontend
`Source` type already matches it. A second, narrower row type would need a second TypeScript type and
would drift.

*Trade-off*: the home surface ships a few fields it does not render. Cheaper than two shapes that
disagree.

### Four queries, none of them per-row

- Latest sources: `$user->sources()->with(['quiz' => withCount('questions')])->latest()->limit(N)`.
- Documents and how many are unsettled: one grouped count over `sources` by status, so the tile's total
  and its "n analysing" line come from the same read. `pending` and `processing` are the unsettled pair.
- Topics: `Topic::where('user_id', ...)->count()`.
- Questions: one aggregate counting `questions` joined to the person's `quizzes` — `Quiz` has a direct
  `user()` relation, so this does not need to reach through `sources`.

The count is fixed regardless of how many documents exist. `N` (how many recent documents to list) is
config, not a literal at the call site — the same rule the cooldown window follows.

### `User` gains `topics()` and `quizzes()` relations

`SourcePanelData` already reaches past the model with `Topic::query()->where('user_id', ...)`. A second
caller doing the same is the point at which the relation earns its place. `Quiz` and `Topic` both have
`user_id` and a `user()` belongsTo, so the inverse is missing rather than absent by design.

### The home surface is a snapshot; it does not poll

The Sources panel polls every 3s while anything is `pending` or `processing`, because that is the surface
where a person watches a document being read — the upload form is there, and the poll starts as a direct
consequence of what they just did. Landing on home does not carry that intent, and a background poll on
the default page of the application is a cost paid by everyone on every visit.

The spec's obligation is met instead by making an unsettled document identifiable and offering the way
to the listing where its progress is followed.

*Alternative considered*: poll only when an unsettled document is present. Still means the most-visited
page opens a repeating request for the exact people most likely to leave it open. Revisit if it turns out
people do wait on the home surface.

### Status vocabulary and formatters extracted on their second caller

`source/Index.vue` holds `statusLabels`, `statusVariants`, `mediaTypeLabel`, `formatSize` and
`formatUploadedAt` as page-local consts. The home surface is the second real caller, which is the
threshold `.ai/20-architecture` sets. Extract to:

- `components/SourceStatusBadge.vue` — takes a `SourceStatus`, renders the badge with its word and
  variant. Keeping label and variant in one component is what makes the spec's "in words as well as in
  any colour" true by construction rather than by two lists staying in sync.
- `lib/source.ts` — `mediaTypeLabel`, `formatSize`, `formatUploadedAt`.

`source/Index.vue` is migrated to both in the same change. Leaving it on its own copies would defeat the
extraction.

### The empty state lives on the home surface and points at Sources

The home surface has no upload form and is not getting one — upload belongs to the Sources panel, which
owns the validation messages and the progress bar. So the empty state is a message plus the way there.

Consequence for the tiles: when a person has nothing, three zeroes tell them nothing. The empty state
replaces the surface's content rather than sitting beneath a row of zeroes.

### `APP_NAME` changes in `.env` and `.env.example`

`AppLogo.vue` reads the shared `name` prop, and page titles come from the same value, so one variable
covers both halves of the identity requirement. `.env` is not committed; `.env.example` is, and both need
it or the next person to set the project up gets "Laravel" back.

## Risks / Trade-offs

- **The logo mark and the Dashboard navigation icon are the same glyph** (`GraduationCap`), roughly 100px
  apart in the same sidebar → Accepted deliberately by the user. If it reads as a duplicate in place, the
  logo is the one to change; it has no meaning to lose.
- **`.env` is per-machine.** Changing `APP_NAME` in `.env.example` does not change anyone's running
  install → The task list has to say "update your own `.env`", and any deployed environment needs the
  same edit or its titles keep saying Laravel.
- **`tests/Feature/DashboardTest.php` asserts against a route that becomes a controller** → It stays and
  grows: the two existing assertions (guest redirect, signed-in 200) remain true and are joined by the
  scoping, bounding and empty-state cases the spec requires.
- **The "no figure the system cannot answer for" requirement is a standing constraint, not a one-time
  check** → It is written into `dashboard-home` precisely so the next change that adds a tile has to meet
  it rather than rediscover it.
- **`Domains\Dashboard` is a domain with no model** → If it never grows past composing other domains'
  Data objects, it is a folder holding one class. Acceptable: the alternative places that class somewhere
  it will have to move from.

## Migration Plan

No data migration. Deployment order does not matter — no schema change, no queue change, no new
dependency. The only environment step is setting `APP_NAME=Quiz` wherever the application runs; missing
it degrades to the old titles rather than breaking anything.

Rollback is reverting the commit. The `dashboard` route returns to `Route::inertia` and nothing else in
the application reads `DashboardData`.
