## Context

See `proposal.md` — Why, and `DESIGN.md` for the system itself. This document covers only the decisions
the rewrite forced.

Three facts shape the work. The `glyph-grid` tokens were **scoped** to `[data-world='glyph']`, so
deleting them cannot break an unmigrated surface — only `Welcome.vue` declares that attribute. The
shadcn token names (`--background`, `--card`, `--primary`, …) are consumed by roughly forty component
files, so **changing their values retint the entire product without editing a single component**. And
light mode is currently real: `:root` holds a full light palette, `.dark` overrides it, and
`AppearanceTabs` switches between them.

## Goals / Non-Goals

**Goals**

- One measured palette, applied through token values so no component is edited.
- Every trace of the character-grid system gone, not left dormant.
- No backend change beyond removing the appearance settings route.

**Non-Goals**

- Rebuilding the landing page. That is `redesign-welcome-page`, which depends on this.
- Redesigning any auth or settings screen. They retint for free; anything beyond that is
  `polish-auth-surfaces`.
- Designing the quiz-answering surfaces.

## Decisions

### D1 — Retint by token value, not by editing components

The palette lands as new values for the existing shadcn token names. Cards, buttons, inputs, dialogs
and the sidebar pick it up with no edits.

*Alternative considered.* Renaming the tokens to something product-specific would read better in
markup, but it means touching every component file in `ui/` for zero visual difference, and it puts the
project's components out of step with upstream shadcn for every future update.

### D2 — Light mode is removed, not merely defaulted off

`:root` carries the dark palette directly. The `.dark` class, the `@custom-variant dark` declaration,
`AppearanceTabs`, its composable and its settings route all go.

*Alternative considered.* Keeping the light palette and defaulting to dark is less deletion, but it
leaves an untested, unmaintained second theme that will drift and rot, plus a settings control that
implies a supported choice. `DESIGN.md` commits to dark only; a switch contradicting the design system
is worse than no switch.

*Consequence.* This is user-visible: anyone who had chosen light gets dark. There is no data to
migrate — the preference is client-side — but the settings page loses a control.

### D3 — Red comes back for errors

`--destructive` becomes `#ef5f63` (6.21:1 on the page, 5.43:1 on a card) and `InputError` uses the token
rather than `red-600`. The previous system's rule that severity is ink weight rather than hue is
abandoned; it made errors harder to spot and bought nothing once a second hue was affordable.

Red on a green-accented dark ground is the one pairing that fails for the most common colour blindness,
which is why the spec requires an icon and a message alongside — never hue alone.

### D4 — `Welcome.vue` is left visibly broken between this change and the next

This change deletes `glyph.ts` and `components/glyph/`, which `Welcome.vue` imports. It cannot compile
otherwise, so this change reduces it to a minimal placeholder — the same copy, standard components, no
imports from the deleted modules — and `redesign-welcome-page` does the real design work.

*Alternatives considered.* Doing both in one change removes the ugly intermediate state but couples a
mechanical token swap to a design exercise, and makes the diff impossible to review. Keeping `glyph.ts`
alive until the redesign lands leaves the retired system in the tree pretending to still be the system.

*Stated plainly:* between these two changes the landing page is plain and unfinished. That is the cost
of separating them, it is visible, and the two should land together.

### D5 — Martian Mono is dropped entirely rather than kept for numerals

A mono face for tabular figures is a real nicety. It is not worth a font load and a second family in a
system whose whole point is now "conventional". `tabular-nums` on Instrument Sans aligns columns.

## Risks / Trade-offs

- **Removing an appearance control is user-visible and irreversible in the same session.** → It is
  stated in the proposal as breaking, and `DESIGN.md` records dark-only as the commitment behind it.
- **Forty-odd shadcn components inherit a palette none of them were designed against.** → Task group 4
  walks the real surfaces — auth, settings, dashboard, sidebar, dialogs — rather than trusting the token
  swap. Card-on-page separation at 1.14:1 is the specific thing to look at.
- **The landing page is degraded until the next change lands.** → D4, accepted and bounded.
- **Deleting `glyph.ts` deletes `simulateField` and the retention ramp maths.** → It is preserved in
  `scratchpad/glyph-backup/` alongside the old `DESIGN.md`, and the real scheduling maths lives
  server-side in `ReviewScheduler`, not here. Nothing load-bearing is lost.

## Migration Plan

Frontend only. No schema, no data, no deployed contract. The appearance preference is client-side and
simply stops being read.

Rollback is a revert of this change plus restoring `DESIGN.md` from `scratchpad/glyph-backup/`.

## Open Questions

None. The palette is measured, the deletions are enumerated, and the one genuinely uncertain thing —
how the landing page should look — is the next change's whole subject.
