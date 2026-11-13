# Auth & Profile Pages

Custom-designed authentication and profile pages for Concrete CMS, using the Innovationsbox Design System.

## Overview

All auth pages (login, register, forgot/reset password, email verification) use the `auth-card` molecule — a branded card on a gradient background. Account pages (edit profile, avatar) and members pages (directory, profile) use the standard site layout.

## Pages

| Page | Path | Template Location |
|---|---|---|
| Login | `/login` | `inno_frontend/single_pages/login.php` |
| Login Form | (rendered by auth type) | `inno_frontend/authentication/concrete/form.php` |
| Register | `/register` | `inno_frontend/single_pages/register.php` |
| Forgot Password | (rendered by auth type) | `inno_frontend/authentication/concrete/forgot_password.php` |
| Reset Password | (rendered by auth type) | `inno_frontend/authentication/concrete/change_password.php` |
| Email Verified | (rendered by auth type) | `inno_frontend/authentication/concrete/email_validated.php` |
| Edit Profile | `/account/edit_profile` | `inno_frontend/single_pages/account/edit_profile.php` |
| Avatar | `/account/avatar` | `inno_frontend/single_pages/account/avatar.php` |
| Members Directory | `/members/directory` | `inno_frontend/single_pages/members/directory.php` |
| Public Profile | `/members/profile` | `inno_frontend/single_pages/members/profile.php` (login required) |

## Template Override Mechanism

Single page templates live in `public/packages/inno_frontend/single_pages/`. The `inno_frontend` package controller registers these overrides in `install()` and `upgrade()` by updating each CMS page's `pkgID` to point to the `inno_frontend` package.

Authentication type view templates live in `public/packages/inno_frontend/authentication/concrete/`. The package controller creates a symlink from `public/application/authentication/concrete` → `../../packages/inno_frontend/authentication/concrete` on install/upgrade. The CMS requires auth type views at the `application/` path (moving them directly into the package namespace breaks controller resolution), so the symlink satisfies both constraints: source of truth in the package, CMS resolves from `application/`.

## Forced Profile Completion

After login, if any user attributes marked as "required on profile edit" have empty values, the user is redirected to `/account/edit_profile?complete=1`. An info banner prompts them to complete their profile. The redirect is registered as an `on_before_render` listener in `inno_shared/controller.php`.

Allowed paths during incomplete profile: `/account/*`, `/login`, `/logout`, `/ccm/*`, `/api/*`.

## Navigation Auth States

The header organism renders differently based on auth state:

- **Logged out:** "Anmelden" (ghost button) + "Registrieren" (primary button)
- **Logged in:** User menu dropdown (avatar + name/email + edit profile + logout)

Auth state is resolved from the CMS `User` class in `elements/header.php`. The logout URL includes a CSRF token.

## New Design System Components

| Component | Type | Purpose |
|---|---|---|
| `email-link` | Atom | Obfuscated email link using lemonbrain EmailAddress helper (ROT47 + JS deobfuscation) |
| `password-field` | Molecule | Password input with show/hide toggle and strength indicator |
| `auth-card` | Molecule | Branded card wrapper for auth pages (gradient background + centered card, logo hidden by default) |
| `user-menu` | Molecule | Avatar trigger + rich dropdown for authenticated users |
| `avatar-upload` | Molecule | Avatar preview + upload/delete controls |
| `member-card` | Molecule | User card for members directory grid |

## Required CMS Permissions

### File Manager — Avatar Upload

Non-admin users need the "Add File" permission to use the avatar cropper. Set this in the CMS Dashboard:

1. Go to *System & Settings → Files → File Manager → Permissions*
2. Enable "Add File" for the group "Registered Users"

Without this permission, the Vue-based avatar cropper component will not render. The avatar controller validates uploads server-side (JPEG/PNG/WebP only, max 5 MB) regardless of this setting.

## User Attributes

| Handle | Label | Type | Required on Register | Required on Profile | Public (Member List) | Editable on Profile |
|---|---|---|---|---|---|---|
| `display_name` | Anzeigename | Text | no | yes | yes | yes |
| `expertise_area` | Fachgebiet | Text | no | no | yes | yes |
| `profile_private_messages_enabled` | Private Messages | Boolean | no | no | no | yes |

The `display_name` attribute triggers forced profile completion — users with an empty display name are redirected to `/account/edit_profile?complete=1` on every page view.

Display name is shown with a fallback: `getAttribute('display_name') ?: getUserName()`. This is used in the header user menu, member profile, and expert pool. The username field is hidden from users (rendered as a hidden input on the edit profile page) — it serves as an internal identifier visible only to admins in the dashboard.

## Roles & Permissions

### CMS Groups → Application Roles

Defined in `inno_shared/.../ConcreteCmsAuth.php` (`GROUP_TO_ROLE`):

| CMS Group | App Role |
|---|---|
| Mitglied | member |
| Entwickler | developer |
| Initiator | initiator |
| Moderator | moderator |
| Administrator | administrator |
| Experte | expert |

### Permission Map

Defined in `ConcreteCmsAuth.php` (`PERMISSION_MAP`):

| Permission | Allowed Roles |
|---|---|
| `idea.submit` | initiator, administrator |
| `idea.view` | all roles + anonymous |
| `idea.vote` | member, developer, initiator, moderator, administrator |
| `moderation.review` | moderator, administrator |
| `space.join` | developer, initiator, administrator |
| `space.manage` | administrator |
| `expert.manage` | moderator, administrator |

Anonymous users can only access `idea.view` (hardcoded in `hasPermission()`).

## Expert Pool (Expertinnen-Pool)

The members directory at `/members/directory` is filtered to show only users in the CMS group "Experte".

**How it works:**
- `Directory` controller (`controllers/single_page/members/directory.php`) calls `$this->userList->filterByGroup('Experte')` — filtering happens at DB level
- Template receives `$isLoggedIn` from the controller
- Anonymous visitors see: avatar, display name, expertise area — no email, no profile links (directory only; profile pages require login)
- Logged-in users see: all of the above + email (obfuscated via `email-link` atom) + clickable profile links

**Profile access control:** The profile page (`/members/profile/view/:id`) requires authentication. Anonymous users are redirected to `/login`. This is enforced in the overridden controller (`controllers/single_page/members/profile.php`).

**Setting expert status:** Moderators assign users to the "Experte" group via CMS Dashboard (*Members → Search Users → [User] → Groups*).

## Forced Profile Completion

Registered users with empty required attributes are redirected to `/account/edit_profile?complete=1` on every page view.

**Mechanism:** `ProfileCompletionChecker` (in `inno_frontend/src/Service/`) checks all attributes from `UserKey::getRegistrationList()` where `isAttributeKeyRequiredOnRegister()` or `isAttributeKeyRequiredOnProfile()` is true.

**Allowed paths (no redirect):** `/account/*`, `/login`, `/register`, `/dashboard/*`

**Currently triggers for:** `display_name` (required on profile).

## How to Add or Modify an Auth Page

1. Find the template in `inno_frontend/single_pages/` or `inno_frontend/authentication/concrete/`
2. Templates use Design System components via `DIR_COMPONENTS`
3. Preserve all CMS form handling (field names, CSRF tokens, form actions)
4. Add SCSS in `resources/css/single_pages/` — webpack auto-discovers and compiles
5. Run `npm run development` to build

## Testing Auth Flows Locally

1. Start the Docker environment
2. Open `http://localhost:8080/login` to see the custom login page
3. Register at `/register` — check email verification flow
4. After login, if birthdate (or other required profile attributes) is empty, you'll be redirected to edit profile
5. Test navigation: logged-out shows login/register buttons, logged-in shows user menu dropdown
