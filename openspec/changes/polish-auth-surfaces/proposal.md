## Why

The seven auth screens inherit the `phosphor-dark` palette for free — they are built on the shadcn
components whose token values `retire-glyph-grid-theme` replaces, so they retint without being edited.
What they do not inherit is the handful of places where they bypass the token set or are simply wrong,
and those become more visible once the theme is green: a hard-coded `text-green-600` status message now
sits next to a green brand colour it does not match.

Three real defects were found reading these files, and none of them are theming.

## What Changes

- Replace the hard-coded `text-green-600` status messages in `Login.vue`, `ForgotPassword.vue` and
  `VerifyEmail.vue`. Green is now the brand colour and must not double as a success colour — a status
  message uses the muted foreground with a lucide check icon, so it reads as confirmation without
  competing with the primary action.
- Replace the hard-coded `decoration-neutral-300` / `dark:decoration-neutral-500` on both mode-toggle
  buttons in `TwoFactorChallenge.vue` with token-based styling. The `dark:` variant is dead after the
  theme change.
- Fix `htmlFor` on the password label in `ConfirmPassword.vue`. It is a React attribute that Vue does
  not bind, so the label is not associated with its input today — clicking it does nothing and screen
  readers do not connect the two.
- Switch `AuthLayout` from `AuthSimpleLayout` to `AuthCardLayout`, so the auth screens sit in a card as
  the rest of the product does. Delete `AuthSplitLayout`, which is unused.
- Give `TextLink` its styling from tokens rather than the neutral decoration colours it hard-codes.

## Capabilities

No spec-level behaviour changes. `visual-system` in `retire-glyph-grid-theme` already requires that
colour never carries meaning alone and that every input has an associated label; this change is two
defects against those requirements plus a layout choice. `.openspec.yaml` sets `skip_specs: true`.

## Impact

- **Modified code**: `Login.vue`, `ForgotPassword.vue`, `VerifyEmail.vue`, `TwoFactorChallenge.vue`,
  `ConfirmPassword.vue`, `AuthLayout.vue`, `TextLink.vue`.
- **Deleted code**: `resources/js/layouts/auth/AuthSplitLayout.vue`.
- **Depends on**: `retire-glyph-grid-theme`.
- **Untouched**: `Register.vue` and `ResetPassword.vue`, which have nothing hard-coded and retint for
  free; every route, controller and Fortify action.
- **Tests**: none added. `tests/Feature/Auth` has no DOM coupling — no `data-test` and no `assertSee`
  assertion — so a green run proves the behaviour these edits must not break.
