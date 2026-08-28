## 1. Palette

- [x] 1.1 Replace the `:root` block in `resources/css/app.css` with the `phosphor-dark` values from
      `DESIGN.md`: background `#050806`, card/popover `#131b13`, muted `#101710`,
      accent/secondary `#1b251a`, border `#212e1f`, input `#2a3a27`, foreground `#e8f0e4`,
      muted-foreground `#96a891`, primary `#8cc93c`, primary-foreground `#071008`,
      destructive `#ef5f63`, ring `#8cc93c`.
- [x] 1.2 Give the sidebar tokens values from the same palette rather than leaving the starter kit's
      neutrals.
- [x] 1.3 Delete the `.dark` block and the `@custom-variant dark` declaration.
- [x] 1.4 Keep `--radius: 0.5rem`.

## 2. Demolition

- [x] 2.1 Delete the `@theme` ink-ramp block, the `[data-world='glyph']` scope and every rule under it:
      `.glyph-cell`, `.rule-dashed`, `.rule-dashed-y`, `.phosphor-scan`, `.glyph-range` and the
      reduced-motion block scoped to the world.
- [x] 2.2 Delete the `html:has([data-world='glyph'])` rule.
- [x] 2.3 Add a global `prefers-reduced-motion: reduce` block to replace the world-scoped one that
      just went.
- [x] 2.4 Delete `resources/js/lib/glyph.ts` and `resources/js/components/glyph/`.
- [x] 2.5 Remove Martian Mono from `vite.config.ts`.
- [x] 2.6 Delete `resources/js/components/AppearanceTabs.vue`, its composable, and the appearance
      settings route and page. Remove the nav entry pointing at it.
- [x] 2.7 Grep for `data-world`, `ink-`, `glyph`, `phosphor`, `rule-dashed` and `dark:` across
      `resources/`, excluding `components/ui/*`. Dead `dark:` variants inside the shadcn set are left
      alone per `design.md` D1 — they fall back to token-driven base styles, and editing them puts the
      project out of step with upstream.

## 3. Error colour

- [x] 3.1 Change `resources/js/components/InputError.vue` to use `text-destructive` instead of
      `text-red-600 dark:text-red-500`.
- [x] 3.2 Confirm no other file hard-codes a colour outside the token set.

## 3b. Retint the surfaces the audit found (scope widened, approved mid-apply)

The 3.2 audit found hard-coded colours in nine files that neither this change nor the two planned ones
owned. `DeleteUser` was the worst — a `bg-red-50` panel reads near-white on a `#050806` ground. The
auth files are left to `polish-auth-surfaces`, which already owns them.

- [x] 3b.1 `DeleteUser.vue` — the red panel to `border-destructive/40 bg-destructive/10`, its copy to
      `text-destructive`.
- [x] 3b.2 `NavFooter.vue` — neutrals to `text-muted-foreground hover:text-foreground`.
- [x] 3b.3 `AppHeader.vue` — active nav pill to `bg-accent text-accent-foreground`, the active
      underline to `bg-primary`, the logo and avatar fallback and breadcrumb bar to tokens.
- [x] 3b.4 `AppLogo.vue` — icon to `text-sidebar-primary-foreground`, matching the tile it sits on.
- [x] 3b.5 `UserInfo.vue` — avatar fallback to `text-foreground`.
- [x] 3b.6 `PlaceholderPattern.vue` — to `stroke-muted-foreground/20`.
- [x] 3b.7 `Dashboard.vue` — drop the dead `dark:border-sidebar-border` pairs.
- [x] 3b.8 `settings/Profile.vue` — link decoration to `decoration-border`, the `text-green-600` status
      to `text-muted-foreground` so it does not compete with the brand green.
- [x] 3b.9 `TwoFactorSetupModal.vue` — the copied-confirmation check to `text-primary`.
- [x] 3b.10 `source/Index.vue` — drop the dead `dark:bg-input/30` on the inlined file input.

## 4. Landing page placeholder

- [x] 4.1 Reduce `resources/js/pages/Welcome.vue` to a compiling placeholder using standard components
      and no import from the deleted modules. Keep the copy. Design work is
      `redesign-welcome-page` — do not attempt it here.

## 5. Verification

- [x] 5.0 Keep `@custom-variant dark (&:is(.dark *))` and never apply `.dark`. Deleting it does not
      disable the shadcn set's ~120 `dark:` utilities — Tailwind v4 falls back to
      `@media (prefers-color-scheme: dark)`, which would fire grey-tuned rules over this palette on a
      dark-preference machine only. Verified: 0 live `prefers-color-scheme` styling in the build.

- [x] 5.1 `npm run build` clean, with no unresolved import from `@/lib/glyph` or `@/components/glyph`.
- [ ] 5.2 Walk every existing surface and check it against the new palette: all seven auth screens,
      both settings pages, the dashboard, the sidebar, the user menu, and any dialog. Look specifically
      at card-on-page separation, which is 1.14:1 and leans on border and shadow.
- [ ] 5.3 Confirm focus rings are visible on both the page ground and a card ground.
- [ ] 5.4 Confirm a validation error is red, legible, and accompanied by a message in words.
- [x] 5.5 Confirm the product cannot vary with the operating system preference. Verified structurally
      against the built stylesheet, which is stronger than one manual check: `.dark` is never applied to
      the document, and the only `prefers-color-scheme` rule left is an unused Lightning CSS
      `.scheme-light-dark` utility. `color-scheme: dark` is set so native controls follow.
- [ ] 5.6 Set `prefers-reduced-motion: reduce` and confirm every surface settles.
- [x] 5.7 Run `php artisan test --compact` green — the removed appearance route may be referenced by a
      settings test.

---

**Closed with unverified tasks, at the user's direction (2026-08-28).** The unchecked boxes above
were not completed: they require a browser, and the Chrome extension was not connectable during the
session that built this. They are left unchecked deliberately so the record is honest — do not read
this change as visually verified. If something is wrong at a breakpoint, in the focus order, or under
reduced motion, this is where it was not checked.
