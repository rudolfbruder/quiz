# Styling

## The design ID is binding

Every user-facing surface in this application belongs to one visual world:

**Design ID: `phosphor-dark`.**

[DESIGN.md](../../DESIGN.md) is the authority for it: the measured palette, the type scale, the
component posture, motion and the contrast floor. Read it before writing any markup or CSS. It
describes what is actually built, not an intention, and it outranks habit and anything a component
library ships with by default.

It supersedes `glyph-grid`, the monospace character-grid system this project ran until
`retire-glyph-grid-theme`. If you find markup or guidance referring to `data-world="glyph"`, the
`. : - = + * @` ink ramp, `text-ink-*`, `bg-ground`, `border-rule`, `.phosphor-scan`, `.rule-dashed`,
`GlyphMatrix`, `GlyphHeadline`, `RetentionField` or Martian Mono, it is stale — none of it exists any
more. Do not restore it.

Do not invent a second visual language for a new feature. If a surface genuinely needs something the
system does not have yet, extend it through the impeccable skill and record the addition in DESIGN.md
— never solve it locally and move on.

## The short version

- **Dark only.** There is no light mode and no appearance switch. Do not add a `dark:` variant, do not
  add a `prefers-color-scheme` rule, and do not reintroduce a theme toggle.
- **One green.** `--primary` (`#8cc93c`) means *this is the thing to act on*. One primary action per
  screen. If two things are green, one is wrong.
- **Red is for errors** — `--destructive`. It came back when `glyph-grid` was retired.
- **Colour never carries meaning alone.** Pair it with an icon, a word, or both. Red and green together
  is the pairing most colour-blind viewers cannot separate, and this palette uses both.
- **Contrast floor 4.5:1** for anything a person reads, measured against the ground it actually sits on
  — a card ground, not just the page. Measure new colours; do not eyeball them.
- Cards, icons, shadows and rounded corners are all correct here. The previous system banned them.

## Use the impeccable skill for design work

Any work that produces or changes what a user sees goes through the `impeccable` skill. That includes
new pages and components, layout and spacing, typography, color, motion, empty and error states, and
UX copy on a screen. Activate it *before* writing the markup, not after it looks wrong.

- New surface or flow: describe it and let the skill route it, or `/impeccable shape <surface>` for a
  brief without implementation.
- Existing surface needing work: the scoped command that matches (`polish`, `layout`, `clarify`,
  `harden`, `adapt`, `animate`, `a11y` via `audit`).
- The skill writes and updates DESIGN.md itself at the end of a build. Do not hand-edit DESIGN.md as
  part of feature work.

Content and copy are design work too. When writing labels, headings, errors, empty states or
notification bodies, stay inside the system's voice as DESIGN.md describes it and run it through the
skill rather than dropping in generic product copy.

## Tailwind

- Tailwind v4 utilities only. No component CSS files, no `@apply` outside the small base layer that
  already exists in `resources/css/app.css`.
- Design tokens live in `:root` in `resources/css/app.css` and are exposed through `@theme inline`.
  Reference them as utilities (`text-foreground`, `text-muted-foreground`, `bg-card`, `bg-primary`,
  `text-destructive`, `border-border`) — **never hardcode a hex or a Tailwind palette colour**
  (`text-green-600`, `bg-red-50`, `text-neutral-500`) in a template. Those were swept out once already.
- `@custom-variant dark (&:is(.dark *))` stays in `app.css` and `.dark` is never applied. It exists to
  keep the shadcn set's inherited `dark:` utilities inert. **Deleting it does not disable them** —
  Tailwind v4 then falls back to `@media (prefers-color-scheme: dark)`, and rules tuned for a different
  palette start firing on dark-preference machines only.

## Reference implementation

`resources/js/components/ui/` is the component library — the shadcn set the Laravel starter kit ships.
Check it before writing a new component; most things already exist. It is retinted purely through token
values, so **do not edit files under `ui/` to change colours** — change the token in `app.css` instead
and stay in step with upstream.

`@lucide/vue` is the icon set. One set only, `size-4` inline with text and `size-5` standalone.
