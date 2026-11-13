# Custom Auth & Profile Pages Design Spec

**Date:** 2026-04-17
**Status:** Approved
**Scope:** Custom-designed authentication, profile, and member pages for Concrete CMS — fully integrated with the Innovationsbox Design System and Storybook.

---

## Table of Contents

1. [Decisions](#1-decisions)
2. [Pages](#2-pages)
3. [Layout](#3-layout)
4. [New Components](#4-new-components)
5. [Template Override Mechanism](#5-template-override-mechanism)
6. [Forced Profile Completion](#6-forced-profile-completion)
7. [Navigation Auth States](#7-navigation-auth-states)
8. [Storybook Stories](#8-storybook-stories)
9. [Accessibility](#9-accessibility)
10. [Testing](#10-testing)
11. [Implementation Order](#11-implementation-order)
12. [File Structure](#12-file-structure)
13. [CMS Configuration Reference](#13-cms-configuration-reference)

---

## 1. Decisions

| Decision | Choice |
|---|---|
| Layout | Card on branded background (all auth pages) |
| Registration fields | Lightweight: username, email, password, confirm password, CAPTCHA |
| Profile completion | Forced after login — redirect to edit profile with info banner |
| Required profile fields | Dynamic from CMS — checks attributes marked "required on profile edit" |
| Navigation (logged in) | Avatar only + rich dropdown (name, email, edit profile, logout) |
| Navigation (logged out) | "Anmelden" + "Registrieren" buttons |
| Template location | `inno_frontend` package (`single_pages/` directory) |
| Account pages layout | Standard site layout (header + main + footer), not branded card |
| Members pages | Included — public profile + members directory |
| Profile completion page | Reuse edit profile with `?complete=1` banner |

---

## 2. Pages

### Auth Pages (card layout, 5 pages)

**Login** (`/login`)
- Fields: username/email, password
- "Angemeldet bleiben" (remember me) checkbox
- "Passwort vergessen?" link to `/login/forgot_password`
- Submit button with loading state
- Error alert for wrong credentials
- "Noch kein Konto? Registrieren" link to `/register`
- CMS variables used: `$form`, `$token`, `$authType`, `$activeAuths`, `$error`

**Register** (`/register`)
- Fields: username (if `display_username_field` enabled), email, password, confirm password
- CAPTCHA field (Concrete CMS captcha helper)
- Submit button with loading state
- Error alerts for validation failures
- Success states: registered / email verification sent / pending approval
- "Bereits registriert? Anmelden" link to `/login`
- CMS variables used: `$form`, `$token`, `$displayUserName`, `$attributeSets`, `$unassignedAttributes`, captcha helper

**Forgot Password** (`/login/forgot_password`)
- Field: email
- Submit button with loading state
- Success alert: "E-Mail wurde gesendet"
- Error alert: email not found
- "Zurueck zur Anmeldung" link
- CMS variables used: `$form`, `$token`, `$error`

**Reset Password** (`/login/change_password`)
- Fields: new password, confirm password (both using `password-field` molecule)
- Submit button
- Success: redirect to login with success message
- Error: expired/invalid token
- CMS variables used: `$form`, `$token`, `$error`

**Email Verification** (`/register/verify`)
- Waiting state: "Bitte pruefe dein E-Mail-Postfach"
- Success state: "E-Mail bestaetigt — du kannst dich jetzt anmelden"
- Resend option if applicable
- CMS variables used: `$token`, `$error`, success/failure states

### Account Pages (standard site layout, 2 pages)

**Edit Profile** (`/account/edit_profile`)
- Profile completion banner (info alert) when `?complete=1` query param present
- Fields: username (if configured), email, timezone (if configured), language (if locales available)
- Custom user attributes organized in attribute sets (rendered dynamically from CMS)
- Password change section: current password, new password, confirm new password
- Authentication type hooks (OAuth link/unlink)
- Save button with loading state
- Success/error alerts after save
- CMS variables used: `$form`, `$token`, `$profile`, `$profileFormRenderer`, `$attributeSets`, `$unassignedAttributes`, `$config`

**Avatar** (`/account/avatar`)
- Current avatar preview (or initials placeholder)
- Avatar cropper instance (CMS-provided `$avatarCropperInstance`)
- Delete avatar button
- CMS variables used: `$token`, `$profile`, `$avatarCropperInstance`

### Members Pages (standard site layout, 2 pages)

**Public Profile** (`/members/profile`)
- Avatar (large)
- Full name, member since date
- Role badge(s)
- Public user attributes
- "Profil bearbeiten" button if viewing own profile
- "Nachricht senden" button if private messages enabled
- CMS variables used: profile UserInfo object, public attributes

**Members Directory** (`/members/directory`)
- Search field (keyword search)
- Grid of `member-card` molecules
- Empty state when no results
- Pagination if many members
- CMS variables used: member list, search params, pagination

---

## 3. Layout

### Auth Pages: Card on Branded Background

All 5 auth pages share the `auth-card` molecule:

- **Background:** Subtle gradient using primary green tokens (`--color-primary-50` to `--color-primary-100`)
- **Card:** White, centered, max-width 440px, border-radius from tokens (`--radius-md`), shadow (`--shadow-md`)
- **Logo:** Innovationsbox logo centered above the form inside the card
- **Mobile:** Card fills viewport width with padding, no gradient visible (white background)
- **Respects `prefers-reduced-motion`:** No gradient animation

### Account & Members Pages: Standard Site Layout

Uses the existing theme layout: header → main content area → footer. Content in a standard container (`--container-small-width` for edit profile, `--container-medium-width` for members directory).

---

## 4. New Components

### Molecule: `password-field`

Location: `public/packages/inno_frontend/components/molecules/password-field/`

**Purpose:** Password input with show/hide toggle and strength indicator.

**Props:**
- `id`, `name` — field identifiers
- `label` — field label (default: "Passwort")
- `value` — current value
- `placeholder` — placeholder text
- `error` — error message
- `required` — required state
- `showStrength` — show/hide strength indicator bar (default: `'false'`)
- `hint` — helper text

**Behavior:**
- Wraps `form-field` molecule with `type: 'password'`
- Toggle button switches `type` between `password` and `text`
- Toggle has `aria-label`: "Passwort anzeigen" / "Passwort verbergen"
- Strength indicator: a bar below the input that fills from 0-100% with colors (red → orange → green) based on basic strength rules (length, mixed case, numbers, special chars)
- Strength calculation in TypeScript, compiled to `resources/js/single_pages/`

**Files:** `password-field.php`, `password-field.variants.php`, `password-field.stories.json`, `password-field.scss`

### Molecule: `auth-card`

Location: `public/packages/inno_frontend/components/molecules/auth-card/`

**Purpose:** Branded card wrapper for all auth pages.

**Props:**
- `title` — page heading (e.g. "Anmelden")
- `subtitle` — optional subheading
- `showLogo` — show Innovationsbox logo (default: `'true'`)

**Behavior:**
- Renders background gradient + centered white card
- Includes logo, heading, subtitle
- `$slot` content (the form) is yielded inside the card via PHP include structure
- Mobile: card fills width, minimal padding

**Files:** `auth-card.php`, `auth-card.variants.php`, `auth-card.stories.json`, `auth-card.scss`

### Molecule: `user-menu`

Location: `public/packages/inno_frontend/components/molecules/user-menu/`

**Purpose:** Avatar + dropdown for authenticated users in header.

**Props:**
- `userName` — full name
- `userEmail` — email address
- `userInitials` — initials for avatar fallback
- `avatarUrl` — uploaded avatar URL (null for initials-only)
- `editProfileUrl` — link to edit profile (default: `/account/edit_profile`)
- `logoutUrl` — logout URL with CSRF token
- `isOpen` — dropdown state (default: `'false'`)

**Behavior:**
- Trigger: avatar circle (initials or image)
- Click toggles dropdown
- Dropdown: user info block (avatar + name + email), divider, "Profil bearbeiten" link, divider, "Abmelden" link (red)
- `aria-expanded` on trigger, `aria-haspopup="true"`, Escape closes, focus trap while open
- Without JS: renders as a plain list of links (progressive enhancement)

**Files:** `user-menu.php`, `user-menu.variants.php`, `user-menu.stories.json`, `user-menu.scss`, `user-menu.ts`

### Molecule: `avatar-upload`

Location: `public/packages/inno_frontend/components/molecules/avatar-upload/`

**Purpose:** Avatar preview with upload/delete controls.

**Props:**
- `hasAvatar` — whether user has an uploaded avatar
- `avatarUrl` — current avatar URL
- `userInitials` — fallback initials
- `cropperHtml` — CMS avatar cropper HTML instance (passed through)

**Behavior:**
- Shows current avatar (or initials circle) at a large size
- "Bild hochladen" button triggers CMS cropper
- "Bild entfernen" button (danger variant) if avatar exists
- Wraps the CMS `$avatarCropperInstance` — doesn't replace it

**Files:** `avatar-upload.php`, `avatar-upload.variants.php`, `avatar-upload.stories.json`, `avatar-upload.scss`

### Molecule: `member-card`

Location: `public/packages/inno_frontend/components/molecules/member-card/`

**Purpose:** User card for members directory grid.

**Props:**
- `userName` — full name
- `userInitials` — initials
- `avatarUrl` — avatar URL (null for initials)
- `profileUrl` — link to public profile
- `memberSince` — joined date
- `role` — role badge text (optional)
- `attributes` — additional public attributes (optional)

**Behavior:**
- Card with avatar, name, joined date, optional role badge
- Clickable — links to public profile
- Uses existing `card` molecule pattern for consistency

**Files:** `member-card.php`, `member-card.variants.php`, `member-card.stories.json`, `member-card.scss`

---

## 5. Template Override Mechanism

Templates live in `public/packages/inno_frontend/single_pages/`. The `inno_frontend` package controller associates existing CMS Single Pages with the package so the CMS resolves views from the package directory.

### Package controller changes

In `inno_frontend/controller.php` `install()` and `upgrade()`:

```php
private function registerSinglePageOverrides(): void
{
    $pages = [
        '/login',
        '/register',
        '/account',
        '/account/edit_profile',
        '/account/avatar',
        '/members',
        '/members/directory',
        '/members/profile',
    ];

    foreach ($pages as $path) {
        $page = Page::getByPath($path);
        if ($page && !$page->isError()) {
            $page->update(['pkgID' => $this->pkg->getPackageID()]);
        }
    }
}
```

This tells the CMS: resolve Single Page views from `inno_frontend/single_pages/` instead of `concrete/single_pages/`.

### Template pattern

Each template:
1. Receives CMS variables from the core Single Page controller (unchanged)
2. Uses `DIR_COMPONENTS` to include design system molecules/atoms
3. Wraps CMS form fields in our `form-field` molecule
4. Preserves all field names, CSRF tokens, hidden fields
5. No custom PHP controllers — only view overrides

---

## 6. Forced Profile Completion

### Trigger

A listener registered in `inno_shared/controller.php` `on_start()`:

1. On `on_before_render`, check if user is authenticated
2. Skip if current path is `/account/edit_profile`, `/login`, `/logout`, or any `/ccm/` path
3. Query all user attributes where `isAttributeKeyEditableOnProfile() === true` and the attribute is marked required in the edit form
4. For each required attribute, check if the current user has a value
5. If any required attribute is empty, store the current URL in session and redirect to `/account/edit_profile?complete=1`

### On the edit profile page

When `?complete=1` query param is present:
- Show an `alert` (variant: `info`) at the top: "Bitte vervollstaendige dein Profil, um die Plattform nutzen zu koennen."
- After successful save: if all required attributes are filled, redirect to the stored URL from session (or homepage)
- If still incomplete: stay on edit profile with validation errors

### Allowed paths during incomplete profile

The user can access:
- `/account/edit_profile` (obviously)
- `/account/avatar` (to upload avatar)
- `/login` and `/logout`
- Any `/ccm/` system routes

All other paths redirect to profile completion.

---

## 7. Navigation Auth States

### Header organism update

The `organisms/header/` component receives a new prop: `authState` with values `'logged-out'` or `'logged-in'`.

**Logged out (`authState: 'logged-out'`):**
- Right side: "Anmelden" button (ghost/secondary variant) → `/login`
- "Registrieren" button (primary variant) → `/register`

**Logged in (`authState: 'logged-in'`):**
- Right side: `user-menu` molecule with user data from CMS session
- User data populated in the header element (`elements/header.php`) from `Concrete\Core\User\User` and `UserInfo`

### Logout URL

The "Abmelden" link in the dropdown must include a CSRF token:
```
/login/do_logout/{ccm_token}
```

The token is generated via `$token->generate('do_logout')` in the header element PHP.

### Mobile

In the navigation drawer:
- **Logged out:** "Anmelden" and "Registrieren" as nav items
- **Logged in:** User info block at the top of drawer, "Profil bearbeiten" and "Abmelden" as nav items

---

## 8. Storybook Stories

### New molecule stories

| Component | Stories |
|---|---|
| `password-field` | Default, With value, Error state, Strength: weak, Strength: medium, Strength: strong, Toggle visible |
| `auth-card` | Default (with sample form), With error alert, Without logo |
| `user-menu` | Closed, Open, With uploaded avatar, Initials only |
| `avatar-upload` | No avatar, With avatar |
| `member-card` | Default, With avatar, With role badge |

### Page stories

| Page | Stories |
|---|---|
| Login | Default, Error (wrong credentials), Loading |
| Register | Default, Validation errors, Success (verification sent) |
| Forgot Password | Default, Success (email sent), Error (not found) |
| Reset Password | Default, Error (mismatch), Token expired |
| Email Verification | Waiting, Success, Resend |
| Edit Profile | Default, Completion mode (info banner), Success alert, Validation errors |
| Avatar | No avatar, With avatar |
| Public Profile | Default with sample user |
| Members Directory | With results, Empty state, No search results |

### Updated stories

| Component | New variants |
|---|---|
| `header` | Logged out, Logged in |

### Fixtures

`_fixtures/auth-data.php`: shared sample data — test user (name, email, initials, avatar URL, roles), error messages, validation states. Reused across all auth page stories.

---

## 9. Accessibility

- All form fields use `form-field` molecule: `aria-describedby` for error/hint messages, `<label>` associated via `for` attribute
- `password-field` toggle: `aria-label` toggles between "Passwort anzeigen" / "Passwort verbergen"
- `user-menu` dropdown: `aria-expanded`, `aria-haspopup="true"`, Escape closes, focus trapped while open
- `auth-card`: `h1` for page title, `role="main"` landmark on card
- Error alerts: `role="alert"` (existing `alert` molecule behavior)
- Focus management: on form submission error, focus moves to first field with error
- Touch targets: all interactive elements min 44x44px via SCSS
- Color contrast: status colors from design tokens meet WCAG AA
- `prefers-reduced-motion`: all transitions/animations gated by media query
- `skip-link`: already exists in theme, targets `#main-content-area`

---

## 10. Testing

| Layer | What | How |
|---|---|---|
| axe-core | All new Storybook stories pass automated a11y check | Existing `tests/e2e/specs/storybook/axe.spec.ts` auto-discovers new stories |
| Unit (PHP) | Password strength calculation | Pest test if strength logic is in PHP; otherwise covered by Storybook play functions |
| Storybook play | Form fill, submit, error trigger, password toggle | Play functions on page stories |
| Integration | CMS resolves templates from `inno_frontend` package | `c5:exec` test: for each Single Page path, verify the view file resolves to `inno_frontend` |
| Integration | Profile completion redirect fires | `c5:exec` test: simulate user with missing required attribute, verify redirect |
| E2E | Full flow: register → verify → login → forced completion → dashboard | Playwright test against running CMS |

---

## 11. Implementation Order

```
Step 1: New molecules
  password-field, auth-card, user-menu, avatar-upload, member-card
  + Storybook stories for each
  + SCSS compiled via webpack
    ↓
Step 2: Auth page templates
  login, register, forgot password, reset password, email verification
  + Register views in inno_frontend controller
  + SCSS in resources/css/single_pages/
  + TS in resources/js/single_pages/ (password toggle, form loading)
  + Storybook page stories
    ↓
Step 3: Profile/Account pages
  edit profile, avatar
  + Profile completion redirect in inno_shared
  + Storybook page stories
    ↓
Step 4: Members pages
  public profile, members directory
  + Storybook page stories
    ↓
Step 5: Navigation integration
  header logged-in/logged-out states
  + user-menu wired into header organism
  + Updated header Storybook stories
  + Mobile navigation drawer updates
    ↓
Step 6: Testing & documentation
  axe-core on all stories
  Integration tests (template resolution, profile completion)
  E2E test (full auth flow)
  docs/auth-pages.md
  Typst technical spec update
```

---

## 12. File Structure

### New files in `inno_frontend`

```
public/packages/inno_frontend/
  single_pages/
    login.php                    — handles login, forgot password, change password (CMS controller switches view)
    register.php                 — handles register + email verification states
    account/
      view.php                   — account dashboard
      edit_profile.php
      avatar.php
    members/
      view.php                   — members landing
      directory.php
      profile.php

  components/
    molecules/
      password-field/
        password-field.php
        password-field.variants.php
        password-field.stories.json
        password-field.scss
      auth-card/
        auth-card.php
        auth-card.variants.php
        auth-card.stories.json
        auth-card.scss
      user-menu/
        user-menu.php
        user-menu.variants.php
        user-menu.stories.json
        user-menu.scss
        user-menu.ts
      avatar-upload/
        avatar-upload.php
        avatar-upload.variants.php
        avatar-upload.stories.json
        avatar-upload.scss
      member-card/
        member-card.php
        member-card.variants.php
        member-card.stories.json
        member-card.scss

    _fixtures/
      auth-data.php
```

### New files in resources

```
resources/
  css/single_pages/
    login.scss
    register.scss
    forgot-password.scss
    change-password.scss
    verify-email.scss
    edit-profile.scss
    avatar.scss
    members-directory.scss
    members-profile.scss
  js/single_pages/
    login.ts
    register.ts
    edit-profile.ts
```

### Modified files

```
public/packages/inno_frontend/controller.php          — registerSinglePageOverrides()
public/packages/inno_frontend/themes/innovationsbox/elements/header.php — auth state rendering
public/packages/inno_frontend/components/organisms/header/header.php    — authState prop
public/packages/inno_frontend/components/organisms/header/header.variants.php — new defaults
public/packages/inno_frontend/components/organisms/header/header.stories.json — logged-in/out variants
public/packages/inno_frontend/components/_storybook/components.scss     — import new molecule styles
public/packages/inno_frontend/components/_storybook/components.ts       — import user-menu.ts
public/packages/inno_shared/controller.php             — profile completion redirect listener
webpack.mix.js                                         — add password-field, user-menu to component mapping (if needed)
```

### New test files

```
tests/e2e/specs/auth-flow.spec.ts                     — E2E: register → verify → login → complete → dashboard
```

### New doc files

```
docs/auth-pages.md                                     — developer guide for auth templates
```

---

## 13. CMS Configuration Reference

Current CMS settings that affect auth page behavior:

| Config key | Value | Effect |
|---|---|---|
| `concrete.user.registration.enabled` | `true` | Register page is active |
| `concrete.user.registration.type` | `'validate_email'` | Email verification required after registration |
| `concrete.user.registration.validate_email` | `true` | Email verification enabled |
| `concrete.user.registration.display_username_field` | `true` | Username field shown on register |
| `concrete.user.registration.display_confirm_password_field` | `true` | Confirm password field shown |
| `concrete.user.registration.captcha` | `true` | CAPTCHA on register form |
| `concrete.user.registration.notification` | `true` | Admin notified on new registration |
| `concrete.user.password.minimum` | `5` | Minimum password length |
| `concrete.user.password.maximum` | `128` | Maximum password length |

Required profile attributes (dynamic — checked at runtime from CMS attribute configuration):
- `birthdate` (date_time) — currently the only required-on-profile attribute

Templates read these values from the CMS at render time and adapt accordingly. If registration is disabled or CAPTCHA is turned off, the template handles it gracefully.
