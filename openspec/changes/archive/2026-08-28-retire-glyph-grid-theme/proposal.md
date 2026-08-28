## Why

`glyph-grid` drew the entire interface in monospace characters on a strict grid — no cards, no icons,
no shadows, no radius, no second hue. It was internally coherent, and it was too austere to build a
product in: every ordinary surface had to be reinvented before it could be built, and each reinvention
read worse than the standard component it replaced. It is retired.

What was worth keeping is the dark ground and the green. `DESIGN.md` has been rewritten as
`phosphor-dark`: the same near-black green-cast ground and the same `#8cc93c`, applied through the
shadcn component set the starter kit already ships, with cards, icons, shadows and rounded corners all
allowed. This change makes the code match that document.

The landing page is the only surface actually built on `glyph-grid`, and it is rebuilt in its own
change (`redesign-welcome-page`). This change is the theme and the demolition.

## What Changes

- Replace the token set in `resources/css/app.css` with the measured `phosphor-dark` palette from
  `DESIGN.md`: dark ground, `#8cc93c` primary, `#ef5f63` destructive, and a real `--muted-foreground`
  that clears the contrast floor on both the page and a card.
- **BREAKING (user-visible)**: remove light mode. The product is dark only. The appearance control in
  settings is removed rather than left as a switch that does nothing, along with the `.dark` class
  plumbing and the `@custom-variant dark` declaration it depends on.
- Delete the `glyph-grid` machinery: the `[data-world='glyph']` scope, `.phosphor-scan`,
  `.rule-dashed`, `.rule-dashed-y`, `.glyph-range`, `.glyph-cell`, the `html:has([data-world='glyph'])`
  rule, the ink ramp tokens, `resources/js/lib/glyph.ts` and `resources/js/components/glyph/`.
- Remove the Martian Mono font from `vite.config.ts`. Instrument Sans is the single face.
- Re-establish red as the error colour. `--destructive` is a real red again and `InputError` uses it
  through the token rather than a hard-coded `red-600`.

**Deliberately not in this change**: the landing page rebuild, which depends on this one and cannot be
done until the tokens land; and any auth work beyond what the token swap does for free.

## Capabilities

### New Capabilities

- `visual-system`: what the product looks like and what that appearance guarantees — a single dark
  theme with no light variant, one brand colour spent on one action per screen, a measured contrast
  floor, and the rule that colour never carries meaning on its own.

### Modified Capabilities

None. No archived specs exist under `openspec/specs/` yet.

## Impact

- **Modified code**: `resources/css/app.css` (token set replaced, glyph block deleted),
  `vite.config.ts` (Martian Mono removed), `resources/js/components/InputError.vue` (token, not
  `red-600`).
- **Deleted code**: `resources/js/lib/glyph.ts`, `resources/js/components/glyph/GlyphMatrix.vue`,
  `GlyphHeadline.vue`, `RetentionField.vue`, `resources/js/components/AppearanceTabs.vue` and the
  appearance composable and settings route that drive it.
- **Temporarily broken**: `resources/js/pages/Welcome.vue` imports `RetentionField` and the ramp from
  `glyph.ts`. It cannot survive this change intact — see the migration note in `design.md`.
- **Untouched**: every backend file, every route except the appearance settings route, every
  `Data` object, and all seven auth pages and both settings pages, which inherit the new palette
  through the tokens without being edited.
- **Dependencies**: none added. `@lucide/vue` and the shadcn set are already installed and are now
  sanctioned rather than banned.
