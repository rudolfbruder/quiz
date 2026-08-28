## 1. Defects

- [x] 1.1 `ConfirmPassword.vue`: change `htmlFor="password"` to `for="password"` and confirm clicking
      the label focuses the input.
- [x] 1.2 `TwoFactorChallenge.vue`: replace `decoration-neutral-300` and its `dark:` counterpart on both
      mode-toggle buttons with token-based styling.
- [x] 1.3 `TextLink.vue`: same treatment — tokens, no hard-coded neutrals, no `dark:` variant.

## 2. Status messages

- [x] 2.1 Replace `text-green-600` in `Login.vue`, `ForgotPassword.vue` and `VerifyEmail.vue` with
      muted-foreground text plus a lucide check icon.
- [x] 2.2 Confirm the result is distinguishable from a validation error by its icon and wording, not
      only by colour.
- [x] 2.3 Confirm no status message competes visually with the screen's primary action.

## 3. Layout

- [x] 3.1 Point `AuthLayout.vue` at `AuthCardLayout`.
- [x] 3.2 Check `AuthCardLayout` against the new palette — it uses `bg-muted` for the page and a card on
      top, and card-on-page separation is 1.14:1, so the border and shadow must be carrying it.
- [x] 3.3 Confirm `setLayoutProps` still swaps the title and description on `TwoFactorChallenge.vue`
      through the card layout.
- [x] 3.4 Delete `AuthSplitLayout.vue` and confirm nothing imports it.

## 4. Verification

- [ ] 4.1 Walk all seven screens: login, register, forgot, reset, confirm, verify email, two-factor.
- [ ] 4.2 Trigger a validation error on each screen that has one; confirm it is red, legible on the
      card, and carries a message.
- [ ] 4.3 Trigger a status message on login, forgot-password and verify-email.
- [ ] 4.4 Tab each screen; confirm visible focus on the card ground.
- [ ] 4.5 Exercise the passkey path on login and confirm-password.
- [x] 4.6 Confirm no `dark:` variant and no hard-coded colour remains in any of the seven pages or the
      auth layouts.
- [x] 4.7 Run `vendor/bin/pest tests/Feature/Auth` green.
