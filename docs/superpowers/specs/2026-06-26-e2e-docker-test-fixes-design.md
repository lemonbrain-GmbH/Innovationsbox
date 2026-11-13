# E2E Docker Test Fixes — Design Spec

**Date:** 2026-06-26
**Branch:** `feature/ideas_moderation`
**Goal:** All E2E tests pass in the Docker container (`docker-compose.test.yml`), simulating real user journeys end-to-end.

## Context

The QA infrastructure (Phases 1–4) produced ~412 E2E tests running against a Docker container with CMS, MySQL, and Mailpit. Currently **35+ tests fail** due to 6 root causes in test infrastructure, storybook URL construction, and Docker environment setup. No business logic changes are needed unless an actual bug is discovered.

**Principle:** Tests define the truth — they describe what a real user does. If the app doesn't match, we fix the app. If the test infrastructure doesn't match the container, we fix that.

## Root Causes and Fixes

### RC1: `AuthedApi` sends form data, controllers expect JSON

**Problem:** `helpers.ts` `post()` and `put()` use Playwright's `form` option, sending `application/x-www-form-urlencoded`. The moderation, project-space, and user-management controllers all parse the body via `json_decode($request->getContent())` — they only read JSON. The project-idea controller has dual handling (checks Content-Type) but it's the exception.

The real frontend sends JSON to these endpoints. The test helper should match.

**Affected tests (~12):**
- `03-moderation.spec.ts` — request feedback with deadline
- `04-project-spaces.spec.ts` — add collaboard link, change space status
- `moderation-flow.spec.ts` — full approve flow
- `status-update.spec.ts` — edit/delete status updates, verify in detail
- `leave-remove-member.spec.ts` — member leaves space
- `user-management.spec.ts` — toggle expert flag

**Fix in `tests/e2e/fixtures/helpers.ts`:**
```typescript
// Before (sends form-encoded):
async post(url: string, options?: { data?: unknown }) {
  return this.request.post(url, {
    form: options?.data as Record<string, string | number>,
    headers: { 'X-CSRF-Token': this.csrfToken },
  });
}

// After (sends JSON):
async post(url: string, options?: { data?: unknown }) {
  return this.request.post(url, {
    data: options?.data,
    headers: {
      'X-CSRF-Token': this.csrfToken,
      'Content-Type': 'application/json',
    },
  });
}
```
Same change for `put()`. The project-idea store endpoint already handles both formats, so this is backward-compatible.

### RC2: Storybook tests use duplicate `id` query parameter

**Problem:** Tests construct URLs like `?id=atoms/checkbox&id=test-checkbox&name=test`. PHP's `$_GET['id']` takes the last value (`test-checkbox`), losing the component path (`atoms/checkbox`). The storybook server then tries to render component `test-checkbox` which doesn't exist.

**Affected tests (15):**
- atoms: checkbox, email-link, input, radio, select, textarea, toggle
- molecules: collaboard-item, dropdown, form-field, modal, password-field, project-card, search-bar, tabs

**Fix:** In each affected test URL, rename the HTML element `id` prop to `elementId`. Update `storybook-server/index.php` to remap `elementId` → `id` in the props array after extracting the component path:
```php
$props = $_GET;
unset($props['id'], $props['component']);
// Remap elementId → id for component props
if (isset($props['elementId'])) {
    $props['id'] = $props['elementId'];
    unset($props['elementId']);
}
```

### RC3: `notification-emails.spec.ts` uses port-6008 email logger

**Problem:** This spec imports `getEmailLogs`/`clearEmailLogs`/`emailLogsContain` which hit `http://localhost:6008` (custom email-log-server). In Docker, only Mailpit runs at port 8025. The playwright config already skips the port-6008 server when `E2E_DOCKER=true`, but the test code still calls it, producing `ECONNREFUSED ::1:6008`.

**Fix:** Convert `notification-emails.spec.ts` to use the existing Mailpit helpers (`clearMailpit`, `searchEmails`, `getMailpitMessage`). The test subjects and assertions stay the same — only the email retrieval mechanism changes.

### RC4: Auth registration assertion contradicts waitForURL

**Problem:** In `01-auth.spec.ts` line 27, after registration CMS redirects to `/register/do_register`. The `waitForURL` predicate at lines 23-25 correctly allows `/register/*` sub-paths, but then `expect(page.url()).not.toContain('/register')` fails because the URL still contains `/register`.

**Fix:** Adjust the assertion to match CMS's actual registration flow. The success indicator is that the URL moved to a sub-path like `/register/do_register` or `/register/register_success` — not that `/register` disappeared from the URL entirely. Change the assertion to verify the URL is on a success/completion sub-path.

### RC5: Browser E2E tests need proper CMS page setup in Docker

**Problem:** Browser tests navigate to CMS pages (`/moderation`, `/projektraeume`, `/projektraum/view/...`, `/account/edit_profile`) and look for specific UI elements. These pages may not be properly created or themed during Docker setup.

**Affected tests (~6):**
- `browser/auth.spec.ts` — register + validate email flow
- `browser/idea-submission.spec.ts` — submit with CKEditor
- `browser/idea-management.spec.ts` — edit idea in modal
- `browser/moderation.spec.ts` — approve idea via UI
- `browser/project-spaces.spec.ts` — write status update via UI
- `auth-flow.spec.ts` — login, profile edit, logout

**Fix:** Update `docker/test-entrypoint.sh` to:
1. Create required CMS single pages (`/moderation`, `/projektraeume`, `/projektraum`) with the correct page types
2. Ensure the innovationsbox theme is active for these pages
3. Verify the `display_name` user attribute exists (needed for profile page)
4. Investigate actual rendered HTML and adjust test selectors only if the rendered markup legitimately differs from what the test expects

### RC6: `projektraum.spec.ts` strict mode violation on badge selector

**Problem:** `locator('#space-status-badge .badge')` resolves to 2 elements, causing Playwright strict mode to fail.

**Fix:** Investigate the rendered page to understand why two badges appear. If it's a UI bug (duplicate rendering), fix the template. If the markup is correct (e.g. mobile + desktop variants), make the selector more specific with `.first()` or a more targeted locator.

## Environment Setup

All tests run with:
```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e
```

The Docker compose stack provides:
- `app` — CMS at port 8880 (Playwright's `baseURL`)
- `db` — MySQL 8.0
- `mailpit` — Email capture at port 8025 (API) / 1025 (SMTP)

## Success Criteria

- All ~412 E2E tests pass with `E2E_DOCKER=true` against the Docker container
- Zero test skips that aren't explicitly conditional on seed data
- Tests simulate real user behavior (browser navigation, form submission, email verification)
- No changes to what the tests verify — only how they interact with the infrastructure

## Implementation Order

1. RC1 (AuthedApi JSON) — highest impact, unblocks 12+ tests
2. RC2 (Storybook URLs) — mechanical fix, unblocks 15 tests
3. RC3 (Notification emails → Mailpit) — unblocks 6 tests
4. RC4 (Auth register assertion) — 1 test
5. RC5 (Docker CMS page setup) — browser tests, may require investigation
6. RC6 (Badge selector) — 1 test, needs investigation
