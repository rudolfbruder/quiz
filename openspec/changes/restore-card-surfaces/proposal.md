## Why

`phosphor-dark` says cards, borders and shadows are correct here, and the tokens back that up:
`--card` (`#131b13`), `--border` (`#212e1f`), `--radius: 0.5rem`, and a full shadcn card component in
`resources/js/components/ui/card`. The markup never used them.

`Welcome.vue` had no card at all — five question-and-answer blocks separated only by `border-t`
hairlines, every one of them sitting directly on `--background`. `source/Index.vue` used
`rounded-xl border p-6` and `overflow-x-auto rounded-xl border`: a border with no fill and no
elevation, which on a near-black ground reads as an outline rather than a surface. The result was a
flat sheet that looked closer to the retired `glyph-grid` austerity than to the system that replaced
it, and the user reported it as such.

The system was right; the pages had not been built to it. This change brings the pages to the system
and states the rule strongly enough in DESIGN.md that the next surface cannot read it as optional.

## What Changes

- **DESIGN.md — the card rule is binding, not a suggestion.** A grouped block of content sits on a real
  card surface (`bg-card` fill, border, `shadow-sm`) built from `components/ui/card`. A bordered box
  with no fill is not a card, and a section floating on the page ground under a hairline rule is not
  one either. Records the full-bleed table recipe (`gap-0 overflow-hidden py-0` on the card, `px-0` on
  the content) so a table meets the card's own edge instead of a second inset border.
- **DESIGN.md — "The cast".** One soft radial green wash behind the top of the *public* surfaces, given
  as the exact utility, `aria-hidden`, `pointer-events-none`, behind everything. Explicitly banned from
  the signed-in application, so the green keeps meaning *act on this*.
- **`Welcome.vue`** — every answer moves onto a card. The two long answers keep full width; the three
  short ones become a three-up grid, so the page has rhythm rather than five identical slabs. The
  fact list and the step list become inset panels on `--muted` inside their cards. The hero stays on
  the page ground with the cast behind it, one display step larger, and keeps its single `rise-in`
  moment. The closing call to action moves onto a card. Copy is unchanged, word for word.
- **`source/Index.vue`** — the upload form, the empty state, and both tables become `Card`s. The
  polling notice moves into `CardAction`. The raw `<progress>` element, which shipped the browser's
  own unthemed bar, becomes a tokened bar carrying the same `role="progressbar"` and value attributes.
  Table rows gain a hover fill and numeric columns gain `tabular-nums`.
- **`AuthCardLayout.vue`** — the page ground moves from `bg-muted` to `bg-background`, so the card
  separates by border and shadow as the system intends rather than sitting on a near-identical fill.
  The logo drops `text-black dark:text-white` for `text-foreground` and gains an accessible name. The
  cast is applied here too.

## Capabilities

No spec-level behaviour changes. Every route, controller, prop shape and form submission is untouched;
this is the visual layer only. `.openspec.yaml` sets `skip_specs: true`.

## Impact

- **Modified code**: `DESIGN.md`, `resources/js/pages/Welcome.vue`, `resources/js/pages/source/Index.vue`,
  `resources/js/layouts/auth/AuthCardLayout.vue`.
- **Depends on**: `retire-glyph-grid-theme` for the palette, and `polish-auth-surfaces` for pointing
  `AuthLayout` at `AuthCardLayout` in the first place.
- **Untouched**: the signed-in `Dashboard.vue`, which still carries starter-kit placeholders and is its
  own piece of work.
