## Why

`retire-glyph-grid-theme` leaves the landing page as a compiling placeholder — the copy is there, the
design is not. It is also the only surface in the product that was ever fully designed, and the one a
person sees before they have any reason to trust the app, so it cannot stay a placeholder.

The copy itself is worth keeping. The previous build earned it: a plain promise, an honest account of
the mechanism, five steps from document to habit, and a section stating what the product refuses. That
survives. What changes is that it is now expressed with cards, icons and ordinary typography instead of
a character grid.

## What Changes

- Rebuild `resources/js/pages/Welcome.vue` on the `phosphor-dark` system: a hero with one primary
  action, the daily-quiz mechanism explained without the retention field simulation, the five steps
  from document to habit, the limits section, and a footer.
- Use the shadcn card set and `@lucide/vue` icons for the steps and the limits, where the previous build
  used numbered rows on a dashed grid.
- Replace the seven-character ramp legend, which explained a mechanism through glyph density, with a
  plain explanation of the same mechanism. The idea it carried — a question you have just seen stays
  away for a few days — is stated in words.
- Keep the header and footer conventional: wordmark set as type, log-in and register links, and the
  authenticated variant linking to the dashboard.

## Capabilities

No spec-level behaviour changes. `visual-system` in `retire-glyph-grid-theme` specifies what any surface
must hold, and the landing page's content is copy rather than behaviour. `.openspec.yaml` sets
`skip_specs: true`.

## Impact

- **Modified code**: `resources/js/pages/Welcome.vue` only.
- **Depends on**: `retire-glyph-grid-theme`. The tokens and the component sanction come from there, and
  the placeholder this change replaces is created there.
- **Untouched**: the `/` route, which stays `Route::inertia`, and every backend file.
- **Tests**: none added. There is no behaviour here beyond two links that
  `tests/Feature/ExampleTest.php` already covers.
