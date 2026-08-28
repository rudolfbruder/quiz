## Why

The signed-in shell still advertises the starter kit it came from. The sidebar carries outbound links
to `github.com/laravel/vue-starter-kit` and the Laravel starter-kit documentation, the header shows the
Laravel mark beside the word "Laravel", and `Dashboard` — the page every signed-in person lands on —
is four boxes of hatched placeholder with nothing behind them. A person who has just uploaded their
first document arrives home and is shown nothing about it.

`add-source-upload` deliberately left `Dashboard.vue` untouched so that the upload work stayed narrow.
This is the follow-on that spends what it built: the home surface finally reads the sources that exist.

## What Changes

- Give `dashboard` a real controller and a response `Data` object. Today the route is
  `Route::inertia('dashboard', 'Dashboard')` — no props cross the boundary at all, which is why the page
  can only show placeholders.
- Fill the home surface with what is true today: three counts a person can act on (documents uploaded
  and how many are still being analysed, topics, questions generated) above a list of their most recent
  documents with the state and the quiz each produced.
- Keep the four-slot card grid the page already has. Its geometry is the layout; only the hatched
  `PlaceholderPattern` fill goes.
- Give the home surface an empty state that names the next step rather than showing three zeroes and an
  empty table. A person with no documents is told to upload one and sent to the Sources panel.
- Remove the two outbound starter-kit links from the sidebar footer. `NavFooter.vue` is kept in place,
  unused, for a future footer entry.
- Replace the Laravel mark in `AppLogoIcon.vue`, set `APP_NAME` to `Quiz` so the sidebar and every page
  title stop reading "Laravel", and change the Dashboard navigation icon from `LayoutGrid`.
- Extract the source status vocabulary and the size, type and timestamp formatters out of
  `source/Index.vue`. The home surface is their second caller, and two screens must not disagree about
  what "Analysing" is called.

**Deliberately not in this change**: anything about the daily quiz. "Due today", a streak, a last score
— every one of those needs `add-daily-selection` and `add-quiz-answering`, which have no routes yet. A
tile reading "Due today: 0" because the feature does not exist is a false statement with a number on it.
The three top slots exist so those numbers have somewhere to land; today they hold counts that are true.

Also out: polling. The Sources panel re-polls while a document is being analysed because that is where a
person watches it happen. The home surface is a snapshot and does not.

## Capabilities

### New Capabilities

- `application-shell`: what the signed-in chrome presents on every page — how the product identifies
  itself, what the sidebar offers, and the rule that the shell does not send people out of the product.
- `dashboard-home`: what a signed-in person is shown when they land, both when they have material and
  when they have none, and the guarantee that a figure on this surface is a figure the system can
  actually answer for.

### Modified Capabilities

None. `visual-system` governs theme, contrast and colour; nothing here changes those requirements.
`source-upload` and `quiz-generation` are consumed, not altered — this change reads sources, it does not
change what may be uploaded or what happens to it.

## Impact

- **New code**: `DashboardController`, a `DashboardData` response object composing the existing
  `SourceData`, a route swap in `routes/web.php`, and a `SourceStatusBadge` component plus shared source
  formatters used by both the home surface and the Sources panel.
- **Modified code**: `Dashboard.vue` rewritten, `AppSidebar.vue` loses its footer items and changes one
  icon, `AppLogoIcon.vue` gets a new mark, `source/Index.vue` gives up its local status maps and
  formatters to the shared ones.
- **Modified configuration**: `APP_NAME` in `.env` and `.env.example`. This changes every page title and
  the sidebar heading; nothing else reads it.
- **Existing tests**: `tests/Feature/DashboardTest.php` covers a route that becomes a controller and will
  need updating.
- **Untouched**: every migration, every model, every domain action. This change adds one read path and
  changes what is drawn.
- **Dependencies**: none added.
