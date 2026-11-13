# User Account Deletion (Deactivation)

**Date:** 2026-07-06
**Status:** Approved

## Problem

Users currently have no way to delete their own account. Moderators and admins also cannot remove user accounts from the platform. This is needed for GDPR compliance and community moderation.

## Solution

Soft-delete (deactivate) user accounts via two entry points: self-deletion from the profile page, and moderator/admin deletion from the user management page. The user's content (ideas, spaces, updates) is preserved but they can no longer log in.

## Design Decisions

- **Soft delete, not hard delete**: Set `Users.uIsActive = 0`. Content stays attributed to the user. Reversible by an admin if needed.
- **Self-deletion requires password confirmation**: Prevents accidental or unauthorized deletion from an unlocked session.
- **Moderator deletion requires a reason**: The reason is included in the notification email and serves as an audit trail.
- **Admin access**: Admins use the moderator section directly (they already have or can be given the Moderator group).

## Entry Points

### 1. Self-Deletion (`/account/edit_profile`)

A danger-styled section at the bottom of the edit profile page, below the password change area:

- Heading: "Konto löschen"
- Warning text explaining the consequences (can't log in, content stays)
- Red "Konto löschen" button
- On click: modal with password input field + confirmation text
- On confirm: `DELETE /api/user/account` with `{ password }`
- On success: user is logged out, redirected to homepage
- Email notification sent confirming the deactivation

### 2. Moderator/Admin Deletion (`/moderation/benutzer`)

A new "Entfernen" button per user row in the user management table:

- Button styled as danger/text, next to the existing expert toggle
- On click: modal with mandatory reason textarea + confirmation text
- On confirm: `DELETE /api/moderation/user/{id}` with `{ reason }`
- On success: user row removed from the list or marked as inactive
- Email notification sent to the user including the reason

## What "Deactivated" Means

1. `Users.uIsActive` set to `0` — CMS blocks login
2. User is removed from all project space memberships (dispatches `MemberRemoved` events per space)
3. User's pending expert requests are cancelled
4. User's content remains: ideas, status updates, moderation reviews stay attributed to them
5. Display name continues to show on their content (not anonymized)

## Architecture

### New Event

`UserAccountDeactivated` in `inno_shared/src/Domain/Event/`

```php
class UserAccountDeactivated
{
    public const NAME = 'innovationsbox.user.account_deactivated';

    public function __construct(
        private readonly string $userId,
        private readonly string $userEmail,
        private readonly string $userName,
        private readonly string $reason,           // empty for self-deletion
        private readonly string $deactivatedByUserId,
    ) {}
    // getters...
}
```

### New Use Case (inno_moderation)

**Port/In:** `DeactivateUserPort` interface
- `deactivateUser(string $userId, string $reason = ''): void`
- `deactivateOwnAccount(string $password): void`

**Application:** `DeactivateUserHandler` implements `DeactivateUserPort`
- Auth check: self-delete verifies password, moderator checks `moderation.review` permission
- Sets `uIsActive = 0` via Concrete CMS `UserInfo::deactivate()`
- Dispatches `UserAccountDeactivated` event
- Logs the action

**Adapter/In/Http:** Two endpoints on `UserApiController`
- `DELETE /api/user/account` — self-deletion (requires authenticated user, body: `{ password }`)
- `DELETE /api/moderation/user/{id}` — moderator deletion (requires `moderation.review`, body: `{ reason }`)

### Event Listeners

**inno_project_space:** `OnUserAccountDeactivated`
- Finds all spaces where the user is a member
- Calls `space.removeMember(userId)` for each
- Dispatches `MemberRemoved` event per space (triggers existing member removal notifications)
- Cancels any pending expert requests for/by the user

**inno_notification:** `OnUserAccountDeactivated`
- Sends email to the deactivated user's email address
- Self-deletion: subject "Dein Konto wurde deaktiviert", body confirms the action
- Moderator deletion: same subject, body includes the reason

### Frontend

**edit_profile.php** — new section at the bottom:
```html
<section class="danger-zone">
  <h3>Konto löschen</h3>
  <p>Warning text about consequences</p>
  <button id="btn-delete-account" class="btn btn--danger">Konto löschen</button>
</section>

<dialog id="delete-account-modal">
  <p>Confirmation text</p>
  <input type="password" id="delete-account-password" />
  <button id="delete-account-confirm" class="btn btn--danger">Endgültig löschen</button>
</dialog>
```

**moderation-benutzer.ts** — new button per user row:
```html
<button class="btn btn--sm" data-delete-user="{userId}" style="color: var(--color-error)">
  Entfernen
</button>
```

Modal with reason textarea:
```html
<dialog id="delete-user-modal">
  <textarea id="delete-user-reason" required></textarea>
  <button id="delete-user-confirm" class="btn btn--danger">Benutzer entfernen</button>
</dialog>
```

## Email Templates

### Self-Deletion

- **Subject:** "Dein Konto wurde deaktiviert"
- **Body:**
  - Heading: "Konto deaktiviert"
  - Text: "Dein Konto bei der Innovationsbox wurde auf deinen Wunsch deaktiviert. Du kannst dich nicht mehr anmelden. Deine bisherigen Beiträge bleiben erhalten."

### Moderator Deletion

- **Subject:** "Dein Konto wurde deaktiviert"
- **Body:**
  - Heading: "Konto deaktiviert"
  - Text: "Dein Konto bei der Innovationsbox wurde von einem Moderator deaktiviert."
  - Reason: "Begründung: {reason}"

## E2E Tests

### In `01-auth.spec.ts` — Self-deletion test

1. Register a fresh user via GUI
2. Login as the fresh user
3. Navigate to `/account/edit_profile`
4. Scroll to danger zone, click "Konto löschen"
5. Modal appears — enter password, click confirm
6. Verify redirect to homepage (logged out)
7. Show deactivation email in Mailpit
8. Try to login again — verify login fails with error

### In `06-moderation-admin.spec.ts` — Moderator deletion test

1. Login as moderator
2. Navigate to `/moderation/benutzer`
3. Search for a test user (use a freshly registered throwaway user)
4. Click "Entfernen" button
5. Modal appears — enter reason, click confirm
6. Verify user disappears from the list
7. Show deactivation email with reason in Mailpit
8. Try to login as the deactivated user — verify login fails

## Files to Create/Modify

| File | Action | Purpose |
|------|--------|---------|
| `inno_shared/src/Domain/Event/UserAccountDeactivated.php` | Create | New event class |
| `inno_moderation/src/Port/In/DeactivateUserPort.php` | Create | Primary port interface |
| `inno_moderation/src/Application/UseCase/DeactivateUser/DeactivateUserHandler.php` | Create | Use case handler |
| `inno_moderation/src/Adapter/In/Http/UserApiController.php` | Modify | Add DELETE endpoints |
| `inno_moderation/controller.php` | Modify | Register DI bindings + event listener |
| `inno_project_space/src/Application/EventHandler/OnUserAccountDeactivated.php` | Create | Cleanup space memberships |
| `inno_project_space/controller.php` | Modify | Register event listener |
| `inno_notification/src/Application/EventHandler/OnUserAccountDeactivated.php` | Create | Send deactivation email |
| `inno_notification/controller.php` | Modify | Register event listener |
| `inno_frontend/single_pages/account/edit_profile.php` | Modify | Add danger zone section |
| `resources/js/single_pages/edit-profile.ts` | Create or modify | Delete account modal JS |
| `resources/js/single_pages/moderation-benutzer.ts` | Modify | Add delete button + modal |
| `inno_frontend/single_pages/moderation/benutzer.php` | Modify | Add delete user modal HTML |
| `tests/unit/inno_moderation/Application/DeactivateUserHandlerTest.php` | Create | Unit test |
| `tests/unit/inno_project_space/Application/OnUserAccountDeactivatedTest.php` | Create | Unit test |
| `tests/unit/inno_notification/Application/OnUserAccountDeactivatedTest.php` | Create | Unit test |
| `tests/e2e/specs/01-auth.spec.ts` | Modify | Add self-deletion E2E test |
| `tests/e2e/specs/06-moderation-admin.spec.ts` | Modify | Add moderator deletion E2E test |
