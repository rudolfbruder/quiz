# Design

<!-- impeccable:design-system 2 -->

**Design ID: `phosphor-dark`.** Supersedes `glyph-grid`, which was retired in `retire-glyph-grid-theme`
after it proved too austere to build a product in. Product truth lives in [PRODUCT.md](PRODUCT.md).

## The thesis

A dark room, one green light.

QUIZ AI is used in short sessions at the edges of a day — before bed, on a train, waiting for coffee.
The interface is a dark, quiet ground with a single bright green that only ever means *this is the
thing to act on*. Everything else recedes. There is no second brand colour and no decorative
illustration; the green is the whole identity and it is spent sparingly.

Beyond that, this is a conventional modern web application, and deliberately so. Cards, icons, rounded
corners, shadows and standard form controls are all correct here. A person should be able to use this
app without learning anything about it.

### What this replaces

The previous system, `glyph-grid`, drew the entire interface in monospace characters on a strict grid:
no cards, no icons, no shadows, no radius, no second hue, and a seven-character ramp (`. : - = + * @`)
that encoded memory retention as glyph density. It was internally coherent and it is preserved at
`scratchpad/glyph-backup/DESIGN.glyph-grid.md` if the reasoning is ever wanted again.

It was retired because its refusals were not affordable. Banning cards, icons and shadows meant every
ordinary product surface — a settings row, a file upload, a validation error, a six-digit code field —
had to be reinvented before it could be built, and each reinvention was less legible than the standard
component it replaced. **The colour and the darkness were the parts worth keeping.** They are what this
system keeps.

## Colour

**Dark only.** There is no light mode. The use scene is short evening sessions, the ground is the
product's whole atmosphere, and maintaining a second palette buys nothing. Anything that offers the
viewer a light/dark choice is removed rather than left as a control that does nothing.

All ratios below are measured, not estimated.

### Ground and structure

| Token | Hex | Use | Contrast |
|---|---|---|---|
| `--background` | `#050806` | The page. Near-black with a green cast, never pure black. | — |
| `--card` / `--popover` | `#131b13` | Any raised surface: cards, dialogs, dropdowns, sheets. | 1.14:1 vs page |
| `--muted` | `#101710` | Inset areas — table stripes, code blocks, disabled fills. | — |
| `--accent` / `--secondary` | `#1b251a` | Hover fills, secondary buttons, selected rows. | — |
| `--border` | `#212e1f` | Every border and divider. | 1.24:1 vs card |
| `--input` | `#2a3a27` | Form control borders, so a field reads as a field at rest. | — |

A card separates from the page by **border and shadow, not by luminance** — 1.14:1 is deliberately
subtle, which is normal for dark interfaces and is why the border and the shadow are doing real work
here rather than being decoration. Do not try to solve card separation by lightening the card; lighten
the border instead.

### Ink

| Token | Hex | Use | On page | On card |
|---|---|---|---|---|
| `--foreground` | `#e8f0e4` | Body copy, headings, values. | 17.26:1 | 15.09:1 |
| `--muted-foreground` | `#96a891` | Labels, helper text, timestamps, secondary copy. | 7.96:1 | 6.96:1 |

**Contrast floor: 4.5:1 for any text a person must read.** Both inks clear it with room on both grounds.
Nothing dimmer than `--muted-foreground` may carry words — if a third, dimmer text level is ever wanted,
it needs measuring first, not eyeballing.

### The green

| Token | Hex | Use | Contrast |
|---|---|---|---|
| `--primary` | `#8cc93c` | The one action to take. Primary buttons, active nav, focus rings, links. | 10.09:1 on page, 8.82:1 on card |
| `--primary-foreground` | `#071008` | Text on a green fill. | 9.69:1 on primary |
| `--ring` | `#8cc93c` | Focus. Always visible, never removed. | — |

**Spend the green sparingly.** One primary action per screen. If two things on a screen are green, one
of them is wrong. The green carried over from `glyph-grid` unchanged — it is the one thing that system
got unambiguously right.

### Destructive

| Token | Hex | Use | Contrast |
|---|---|---|---|
| `--destructive` | `#ef5f63` | Validation errors, delete actions, failure states. | 6.21:1 on page, 5.43:1 on card |

Red is allowed here and is the correct choice. The previous system's rule that an error must be
expressed as ink weight rather than hue is **gone** — it made errors harder to spot for no benefit once
a second hue was affordable.

Red and green next to each other is the one combination that fails for the most common form of colour
blindness, so **severity never rests on hue alone**: an error carries an icon and a message, a success
carries an icon and a message, and neither is identifiable by its colour alone.

## Type

**Instrument Sans**, already loaded, `ui-sans-serif` fallback. One face for everything.

Martian Mono is removed along with the rest of the `glyph-grid` machinery. Numerals that need to align
in a column use `tabular-nums` on the sans face, which is enough.

| Role | Size | Treatment |
|---|---|---|
| Page title | `text-2xl` | `font-semibold`, `--foreground` |
| Section heading | `text-lg` | `font-medium`, `--foreground` |
| Card title | `text-base` | `font-medium`, `--foreground` |
| Body | `text-sm` | `leading-relaxed`, `--foreground`, measure capped near 70ch |
| Secondary / helper | `text-sm` | `--muted-foreground` |
| Label | `text-sm` | `font-medium`, `--foreground` |
| Meta / timestamp | `text-xs` | `--muted-foreground` |
| Numerals in a column | any | `tabular-nums` |

Sentence case throughout. The previous system set everything uppercase with wide tracking; that is not
this system and should not survive anywhere.

## Components

`resources/js/components/ui/*` — the shadcn set the Laravel starter kit ships — is the component
library. **Use it. Do not rebuild what it already provides.** Check for an existing component before
writing a new one.

- **Radius** — `--radius: 0.5rem`, already set. Square corners are not a thing here.
- **Shadows** — Tailwind's scale. Used for genuine elevation: cards off the page, dropdowns and dialogs
  off the cards. Not for decoration, not as a glow.
- **Icons** — `@lucide/vue`. One icon set, `size-4` inline with text and `size-5` standalone, at the
  library's own stroke weight. Do not mix in a second set and do not substitute emoji.
- **Cards** — the default container for a group of related things. One level deep; a card inside a card
  means the grouping is wrong.
- **Focus** — the green ring on every interactive element. Never removed, never replaced by a colour
  change alone.

## Motion

Sparing and short. `duration-150` to `duration-200` for hover, focus and press; `duration-300` for
something entering or leaving. `prefers-reduced-motion: reduce` is honoured — anything that animates
must render a complete, settled state when motion is off.

No autoplaying animation, no parallax, no scroll-driven effects.

## Accessibility

- Contrast floor 4.5:1 for text, held by the palette above. Re-measure anything new.
- Colour never carries meaning alone — always paired with an icon, a word, or both.
- Every input has a real, associated `<label>`. A placeholder is not a label.
- Every icon-only control has an accessible name.
- Full keyboard operation, visible focus, sensible reading order.
- Status and error messages are announced (`aria-live="polite"`).

## Where this system lives

- `resources/css/app.css` — the tokens above, dark only.
- `resources/js/components/ui/*` — the component library.
- `@lucide/vue` — icons.

Everything on the `glyph-grid` list is gone: `resources/js/lib/glyph.ts`,
`resources/js/components/glyph/`, the Martian Mono font, the `[data-world='glyph']` scope, and the
`.phosphor-scan`, `.rule-dashed*` and `.glyph-range` utilities.

## Open

- No logo or wordmark exists. QUIZ AI currently sets as type.
- The quiz-answering surfaces — question, options, result, empty state — have no components yet. They
  are the first real test of this system. With a red available, correctness is straightforward: a
  correct option is green-marked and a wrong one is red-marked, each with an icon and a word.
- A backlog is never displayed as a raw overdue count. The readout says how many questions are waiting
  today. That rule survived the system change because it is a product decision, not a visual one.
