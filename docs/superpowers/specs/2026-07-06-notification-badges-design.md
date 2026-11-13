# Notification Badge Counts

**Date:** 2026-07-06
**Status:** Approved

## Problem

Users have no visual indication of pending actions (moderation reviews, expert requests, join requests). They must navigate to each page manually to discover work waiting for them.

## Solution

Show notification badge counts on the user menu trigger (avatar) and next to relevant menu items. Counts are rendered server-side on page load for instant display. A new API endpoint provides the counts for potential future client-side refresh.

## Counts

| Count | Source Table | Who Sees It | Menu Item |
|---|---|---|---|
| Pending moderation reviews | `ib_moderation_reviews` WHERE `decision = 'pending'` | Moderators, admins | "Moderation" |
| Pending expert requests | `ib_expert_requests` WHERE `status = 'pending'` AND `expert_user_id = {userId}` | Experts | "Meine Anfragen" |
| Pending join requests | `ib_project_space_members` WHERE `request_status = 'pending'` AND space initiator = {userId} | Initiators | "Meine Projekträume" (new) |

## New API Endpoint

`GET /api/user/counts` in `inno_moderation` package.

**Auth:** Requires authenticated user. Returns 401 for anonymous.

**Response:**
```json
{
  "moderationReviews": 3,
  "expertRequests": 1,
  "joinRequests": 2,
  "total": 6
}
```

Each count is 0 if the user doesn't have the relevant role. The endpoint runs lightweight COUNT queries directly — no domain objects loaded.

## Avatar Badge

A red notification bubble overlaid on the top-right of the user menu trigger button (avatar/initials). Shows the `total` count. Hidden when total is 0.

**HTML:** `<span class="user-menu__badge" aria-label="N offene Aufgaben">N</span>` inside `.user-menu__trigger`, next to the avatar atom.

**CSS:** Absolute positioned, red background, white text, circular pill shape, font-size small. `display: none` when empty or 0.

## Menu Item Badges

Inline badge pills next to menu item text in the user-menu dropdown:

```
Profil bearbeiten
Profilbild ändern
Meine Projektideen
Meine Projekträume (2)    ← new item, join request count
Moderation (3)            ← review count
Meine Anfragen (1)        ← expert request count
───────────────────
Abmelden
```

**New menu item:** "Meine Projekträume" linking to `/projektraeume`. Shown for all logged-in users. Badge count is pending join requests (initiators only, 0 for others).

**Badge HTML:** `<span class="user-menu__item-badge">N</span>` after the link text. Hidden when count is 0.

## Navigation Drawer (Mobile)

No separate implementation needed. The existing JS in `navigation-drawer.ts` moves the entire `[data-header-auth]` content (including user-menu with badges) into the nav drawer. Badges travel with the DOM.

## Data Flow

**Server-side rendering (no flash):**

1. `elements/header.php` queries the counts using the same SQL the API endpoint uses (via a shared service or direct queries)
2. Passes counts as props to the `user-menu` component
3. `user-menu.php` renders the badge spans with the counts, hidden when 0
4. No JavaScript needed for initial display — badges are in the HTML

**API endpoint (for future use):**

The `GET /api/user/counts` endpoint exists for potential client-side refresh (e.g. polling, or after actions that change counts). Not used in the initial implementation — server-side rendering is sufficient.

## Files to Create/Modify

| File | Action | Purpose |
|---|---|---|
| `inno_moderation/src/Adapter/In/Http/UserApiController.php` | Modify | Add `counts()` endpoint |
| `inno_moderation/controller.php` | Modify | Register `/api/user/counts` route |
| `inno_frontend/themes/innovationsbox/elements/header.php` | Modify | Query counts, pass to user-menu |
| `inno_frontend/components/molecules/user-menu/user-menu.php` | Modify | Render badge spans with counts, add "Meine Projekträume" item |
| `inno_frontend/components/molecules/user-menu/user-menu.scss` | Modify | Badge bubble + item badge styles |
| `inno_frontend/components/molecules/user-menu/user-menu.ts` | Modify | Read counts from rendered HTML, inject total on avatar |
| `tests/unit/inno_moderation/Adapter/UserCountsTest.php` | Create | Unit test for counts endpoint |
| `tests/e2e/specs/00-smoke.spec.ts` | Modify | Verify badge visible for moderator |

## E2E Tests

- Smoke test: login as moderator → verify badge count is visible on avatar (seeded data has pending reviews)
- Smoke test: login as expert → verify "Meine Anfragen" has a count (seeded pending request)
- Smoke test: login as initiator → verify "Meine Projekträume" item exists
