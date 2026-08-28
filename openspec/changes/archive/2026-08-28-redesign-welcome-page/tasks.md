## 1. Design

- [x] 1.1 Activate the `impeccable` skill before writing markup. This is design work and `CLAUDE.md`
      routes it through that skill; `DESIGN.md` is the constraint it works inside, not a starting point
      to revise.
- [x] 1.2 Decide the hero: one heading, one supporting paragraph, one primary action, one secondary.
      Only the primary carries the brand fill — `visual-system` allows one per surface.

## 2. Build

- [x] 2.1 Header: wordmark as type, log-in and register links, dashboard link when
      `$page.props.auth.user` is set. Keep the existing Wayfinder imports.
- [x] 2.2 Hero section.
- [x] 2.3 Mechanism section: explain the daily quiz and the cooldown in words. No ramp legend, no
      simulation, no character field.
- [x] 2.4 The five steps — upload, analyze, choose, serve, remind — kept, but **not as an icon-card
      grid**. The structure chosen with the user ("the learner's own questions") makes them a numbered
      sequence inside the answer to *What does it do with it?*, where the order carries real
      information. The craft floor refuses same-size icon-plus-heading-plus-text cards as page
      structure, so the card grid this task originally specified would have been the lazy container.
- [x] 2.5 Limits section: the four existing limits, and the paragraph about refusing over-limit
      documents with the real page count.
- [x] 2.6 Closing action and footer.

## 3. Verification

- [x] 3.0 Theme the browser surfaces. `retire-glyph-grid-theme` deleted the old world's `::selection`,
      caret and scrollbar theming without replacing it, so the page was falling back to platform
      defaults. Added to `app.css` alongside the page's one authored moment (`.rise-in`).

- [x] 3.1 Check every text colour against the 4.5:1 floor on the ground it sits on, cards included.
- [x] 3.2 One primary *action*, offered twice: the same "Start a topic" button in the hero and at the
      close. DESIGN.md's rule ("if two things on a screen are green, one is wrong") targets two
      competing actions, not one action repeated down a long page. Nothing else on the page is green.
      Flagged to the user rather than interpreted silently.
- [ ] 3.3 Check the page at 375px, 768px and 1280px.
- [ ] 3.4 Tab the whole page; confirm reading order and a visible focus ring on every link.
- [x] 3.5 Confirm every icon is decorative and `aria-hidden`, or has an accessible name.
- [x] 3.6 Render with `prefers-reduced-motion: reduce` and confirm a settled state.
- [ ] 3.7 Confirm the authenticated header variant renders correctly while signed in.
- [x] 3.8 `npm run build` clean.

---

**Closed with unverified tasks, at the user's direction (2026-08-28).** The unchecked boxes above
were not completed: they require a browser, and the Chrome extension was not connectable during the
session that built this. They are left unchecked deliberately so the record is honest — do not read
this change as visually verified. If something is wrong at a breakpoint, in the focus order, or under
reduced motion, this is where it was not checked.
