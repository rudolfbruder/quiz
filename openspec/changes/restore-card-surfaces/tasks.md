## 1. The rule

- [x] 1.1 DESIGN.md: rewrite the Cards bullet so a card surface is the stated default and a
      fill-less bordered box is named as not being one.
- [x] 1.2 DESIGN.md: record the full-bleed-inside-a-card recipe.
- [x] 1.3 DESIGN.md: add "The cast" — the exact gradient utility, public surfaces only, banned in the
      signed-in application.

## 2. Landing page

- [x] 2.1 Move each answer onto a `Card`; keep every word of the existing copy.
- [x] 2.2 Long answers full width, short answers three-up, so the page is not five identical slabs.
- [x] 2.3 Facts and steps become inset `--muted` panels inside their cards.
- [x] 2.4 Hero keeps the page ground, gains the cast behind it and one display step; `rise-in` stays
      the page's only authored motion.
- [x] 2.5 Closing call to action moves onto a card.

## 3. Sources page

- [x] 3.1 Upload form, empty state and both tables become `Card`s.
- [x] 3.2 Tables run full bleed inside their cards; polling notice moves to `CardAction`.
- [x] 3.3 Replace the raw `<progress>` with a tokened bar keeping `role="progressbar"` and its value
      attributes.
- [x] 3.4 Row hover fill and `tabular-nums` on numeric columns.

## 4. Auth layout

- [x] 4.1 `AuthCardLayout` page ground `bg-muted` → `bg-background`.
- [x] 4.2 Logo `text-black dark:text-white` → `text-foreground`, plus an accessible name.
- [x] 4.3 Apply the cast.

## 5. Verification

- [x] 5.1 `npm run build` green.
- [x] 5.2 `npx vue-tsc --noEmit` clean.
- [x] 5.3 `npm run lint` reports nothing under `resources/js`.
- [x] 5.4 `vendor/bin/pest tests/Feature/Auth tests/Feature/Source` green.
- [ ] 5.5 Walk the landing page, the sources page and the seven auth screens in a browser at mobile
      and desktop width; confirm card separation, focus rings on the card ground, and that the cast
      never sits behind text that needs it to be legible.
