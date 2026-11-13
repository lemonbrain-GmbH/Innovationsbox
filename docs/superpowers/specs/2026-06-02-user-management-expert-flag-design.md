# User Management Page + Expert Flag + Notifications — Design Spec

**Date:** 2026-06-02
**Goal:** Add a moderation subpage for managing users (list + toggle expert flag), notify moderators on new registrations, and notify users when their expert status changes.

---

## Page: `/moderation/benutzer`

New single page following the `kategorien` pattern. Lists all CMS users with expert toggle.

### API Endpoints (in `inno_moderation`)

| Method | Endpoint | Permission | Description |
|---|---|---|---|
| `GET /api/moderation/user` | `moderation.review` | List users: `{ data: [{ id, name, email, isExpert }] }` |
| `PUT /api/moderation/user/{id}/expert` | `moderation.review` | Toggle: `{ expert: true }` or `{ expert: false }` → returns `{ status: 'updated', isExpert: bool }` |

### API Implementation

**GET /api/moderation/user:**
- Query CMS `UserList` for all active users
- For each user, check membership in "Experte" group
- Return `{ id, name, email, isExpert }`

**PUT /api/moderation/user/{id}/expert:**
- Read `{ expert: bool }` from request body
- If `expert === true`: add user to "Experte" group via `User::enterGroup()`
- If `expert === false`: remove user from "Experte" group via `User::exitGroup()`
- Dispatch `UserFlaggedAsExpert` or `UserUnflaggedAsExpert` event
- Return `{ status: 'updated', isExpert: bool }`

### Controller

New `UserApiController` in `inno_moderation/src/Adapter/In/Http/` — follows `CategoryApiController` pattern. No use case handler needed — this is a thin adapter that directly manages CMS groups (no domain logic, just CMS admin operation).

### Frontend

**PHP page:** `public/packages/inno_frontend/single_pages/moderation/benutzer.php` — same shell as `kategorien.php`: back link, h1 "Benutzer verwalten", content container `#users-container`.

**TypeScript:** `resources/js/single_pages/moderation-benutzer.ts` — fetches user list, renders table with columns: Name, Email, Expert (toggle button). Toggle calls PUT endpoint, reloads on success.

**CSS:** `resources/css/single_pages/moderation-benutzer.scss` — follows kategorien pattern.

**Navigation:** Add link to benutzer page from moderation view.php, alongside the existing "Kategorien" link.

### Assets & Registration

- Register single page `/moderation/benutzer` in `inno_frontend/controller.php`
- Register JS/CSS asset group `inno_frontend/moderation-benutzer`
- Add entry to `webpack.mix.js` single_pages auto-discovery (already auto-discovered)

---

## Events

### New in `inno_shared`

- `UserFlaggedAsExpert` — `userId: string, flaggedByUserId: string`
- `UserUnflaggedAsExpert` — `userId: string, unflaggedByUserId: string`

No event needed for new user registration — we listen to the CMS built-in `on_user_add`.

---

## Notifications

### 1. New User Registered → All Moderators

- **Trigger:** CMS `on_user_add` event (built-in, fired on registration)
- **Handler:** `OnUserRegistered` in `inno_notification`
- **Recipients:** All users in "Moderator" group (loop, resolve email for each)
- **Subject:** "Neue Registrierung auf der Innovationsbox"
- **Body:** "{userName} ({userEmail}) hat sich auf der Plattform registriert."

### 2. User Flagged as Expert → The User

- **Trigger:** `UserFlaggedAsExpert` event
- **Handler:** `OnUserFlaggedAsExpert` in `inno_notification`
- **Recipient:** The flagged user
- **Subject:** "Du wurdest als Expert·in markiert"
- **Body:** "Du wurdest als Expert·in auf der Innovationsbox markiert. Du kannst jetzt von Projekträumen als Expert·in angefragt werden."

### 3. User Unflagged as Expert → The User

- **Trigger:** `UserUnflaggedAsExpert` event
- **Handler:** `OnUserUnflaggedAsExpert` in `inno_notification`
- **Recipient:** The unflagged user
- **Subject:** "Dein Expert·in-Status wurde entfernt"
- **Body:** "Dein Expert·in-Status auf der Innovationsbox wurde entfernt."

---

## Files to Create/Modify

### New in `inno_shared`
- `Domain/Event/UserFlaggedAsExpert.php`
- `Domain/Event/UserUnflaggedAsExpert.php`

### New in `inno_moderation`
- `Adapter/In/Http/UserApiController.php`

### New in `inno_notification`
- `Application/EventHandler/OnUserRegistered.php`
- `Application/EventHandler/OnUserFlaggedAsExpert.php`
- `Application/EventHandler/OnUserUnflaggedAsExpert.php`

### New in `inno_frontend`
- `single_pages/moderation/benutzer.php`

### New resources
- `resources/js/single_pages/moderation-benutzer.ts`
- `resources/css/single_pages/moderation-benutzer.scss`

### Modify
- `inno_moderation/controller.php` — register routes + asset group
- `inno_notification/controller.php` — register 3 new listeners
- `inno_frontend/controller.php` — register single page + asset group
- `inno_frontend/single_pages/moderation/view.php` — add nav link to benutzer
- `webpack.mix.js` — may need entry if not auto-discovered

### Tests
- Unit: `UserApiController` (or handler if extracted), `OnUserRegistered`, `OnUserFlaggedAsExpert`, `OnUserUnflaggedAsExpert`
- E2E: list users, toggle expert flag, verify notification
