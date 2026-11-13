# E2E Docker Test Fixes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make all ~412 E2E tests pass when run with `E2E_DOCKER=true` against the Docker test container.

**Architecture:** Fix 6 root causes in test infrastructure (helpers, storybook URLs, notification spec, auth assertion, Docker setup, badge selector). No business logic changes unless a real bug is found.

**Tech Stack:** Playwright, TypeScript, PHP (storybook server + CMS), bash (Docker entrypoint)

## Global Constraints

- Tests define truth — they describe what a real user does
- `E2E_DOCKER=true` is the canonical way to run tests against the container
- All tests run via: `E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e`
- Docker stack: `docker compose -f docker-compose.test.yml up -d --wait`
- Do not change what tests verify — only fix how they interact with the app/infrastructure

---

### Task 1: Fix AuthedApi to send JSON instead of form data

**Files:**
- Modify: `tests/e2e/fixtures/helpers.ts:20-31` (post and put methods)

**Interfaces:**
- Consumes: Playwright `APIRequestContext`
- Produces: `AuthedApi.post()` and `AuthedApi.put()` send JSON with `Content-Type: application/json`

- [ ] **Step 1: Edit `post()` method to send JSON**

In `tests/e2e/fixtures/helpers.ts`, replace lines 20-25:

```typescript
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

- [ ] **Step 2: Edit `put()` method to send JSON**

Replace lines 27-31:

```typescript
async put(url: string, options?: { data?: unknown }) {
  return this.request.put(url, {
    data: options?.data,
    headers: {
      'X-CSRF-Token': this.csrfToken,
      'Content-Type': 'application/json',
    },
  });
}
```

- [ ] **Step 3: Run the API-level tests that were failing**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/02-ideas.spec.ts \
  tests/e2e/specs/03-moderation.spec.ts \
  tests/e2e/specs/04-project-spaces.spec.ts \
  tests/e2e/specs/moderation-flow.spec.ts \
  tests/e2e/specs/status-update.spec.ts \
  tests/e2e/specs/leave-remove-member.spec.ts \
  tests/e2e/specs/user-management.spec.ts \
  --reporter=line
```

Expected: All these tests pass (except `notification-emails.spec.ts` which is Task 3).

- [ ] **Step 4: Commit**

```bash
git add tests/e2e/fixtures/helpers.ts
git commit -m "fix(e2e): send JSON instead of form data in AuthedApi helpers

The moderation, project-space, and user-management API controllers parse
request body via json_decode(\$request->getContent()). The test helpers
were sending application/x-www-form-urlencoded which produced empty bodies."
```

---

### Task 2: Fix storybook test URLs with duplicate `id` parameter

**Files:**
- Modify: `tests/e2e/specs/storybook/atoms.spec.ts` (7 tests)
- Modify: `tests/e2e/specs/storybook/molecules-organisms.spec.ts` (8 tests)
- Modify: `storybook-server/index.php:38-41` (prop remapping)

**Interfaces:**
- Consumes: Storybook PHP server at `http://localhost:6007`
- Produces: Tests use `elementId` query param instead of duplicate `id`; server maps it back to `id` prop

- [ ] **Step 1: Update storybook server to remap `elementId` to `id` in props**

In `storybook-server/index.php`, after line 40 (`unset($props['id'], $props['component']);`), add:

```php
// Remap elementId → id for component props (avoids duplicate ?id= in query string)
if (isset($props['elementId'])) {
    $props['id'] = $props['elementId'];
    unset($props['elementId']);
}
```

- [ ] **Step 2: Fix atoms.spec.ts — replace duplicate `id` with `elementId`**

Replace the URLs in these tests:

Line 99: `?id=atoms/checkbox&id=test-checkbox&name=test` → `?id=atoms/checkbox&elementId=test-checkbox&name=test`

Line 137: `?id=atoms/input&type=text&id=email` → `?id=atoms/input&type=text&elementId=email`

Line 151: `?id=atoms/radio&id=test-radio&name=test` → `?id=atoms/radio&elementId=test-radio&name=test`

Line 159: `?id=atoms/select&id=country&name=country` → `?id=atoms/select&elementId=country&name=country`

Line 175: `?id=atoms/textarea&id=message&name=message` → `?id=atoms/textarea&elementId=message&name=message`

Line 183: `?id=atoms/toggle&id=toggle-demo&name=toggle` → `?id=atoms/toggle&elementId=toggle-demo&name=toggle`

The `email-link` test (line 121) does NOT have a duplicate `id` — it uses `?id=atoms/email-link&email=test@example.com`. Its failure may be a different issue. Investigate its actual error output during the run. If it passes after the server fix (because the server error was cascading), good. If not, check the component PHP file.

- [ ] **Step 3: Fix molecules-organisms.spec.ts — replace duplicate `id` with `elementId`**

Line 88: `?id=molecules/collaboard-item&title=Board&id=link_123` → `?id=molecules/collaboard-item&title=Board&elementId=link_123`

Line 96: `?id=molecules/dropdown&triggerLabel=Actions&id=demo-dropdown` → `?id=molecules/dropdown&triggerLabel=Actions&elementId=demo-dropdown`

Line 112: `?id=molecules/form-field&label=Email&id=email&type=email` → `?id=molecules/form-field&label=Email&elementId=email&type=email`

Line 135: `?id=molecules/modal&id=modal-demo&title=Confirm` → `?id=molecules/modal&elementId=modal-demo&title=Confirm`

Line 149: `?id=molecules/password-field&label=Password&id=password` → `?id=molecules/password-field&label=Password&elementId=password`

Line 157: `?id=molecules/project-card&title=Solar+Project&id=space_1` → `?id=molecules/project-card&title=Solar+Project&elementId=space_1`

Line 165: `?id=molecules/search-bar&id=search&name=q` → `?id=molecules/search-bar&elementId=search&name=q`

Line 186: `?id=molecules/tabs&id=demo-tabs` → `?id=molecules/tabs&elementId=demo-tabs`

- [ ] **Step 4: Run storybook tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/storybook/atoms.spec.ts \
  tests/e2e/specs/storybook/molecules-organisms.spec.ts \
  --reporter=line
```

Expected: All 61 storybook tests pass (15 were failing, 46 were passing).

- [ ] **Step 5: Commit**

```bash
git add storybook-server/index.php tests/e2e/specs/storybook/atoms.spec.ts tests/e2e/specs/storybook/molecules-organisms.spec.ts
git commit -m "fix(e2e): resolve duplicate id query parameter in storybook tests

PHP overwrites \$_GET['id'] with the last value when the same key appears
twice. Use elementId for the HTML element id prop; the storybook server
remaps it back to id in the component props array."
```

---

### Task 3: Convert notification-emails.spec.ts from port-6008 to Mailpit

**Files:**
- Modify: `tests/e2e/specs/notification-emails.spec.ts` (full file)

**Interfaces:**
- Consumes: `clearMailpit`, `searchEmails` from `helpers.ts` (already exist)
- Produces: Tests use Mailpit API at port 8025 instead of email-log-server at port 6008

- [ ] **Step 1: Replace imports**

Change lines 1-11 from:

```typescript
import {
  type AuthedApi,
  submitIdea,
  getPendingReviewForIdea,
  loginAs,
  getEmailLogs,
  emailLogsContain,
  clearEmailLogs,
} from '../fixtures/helpers';
```

To:

```typescript
import {
  type AuthedApi,
  submitIdea,
  getPendingReviewForIdea,
  loginAs,
  clearMailpit,
  searchEmails,
} from '../fixtures/helpers';
```

- [ ] **Step 2: Replace `clearEmailLogs` calls with `clearMailpit`**

Every call to `clearEmailLogs(someApi)` becomes `await clearMailpit(someApi.getRawRequest())`.

Add a `clearMailpit` call in `beforeAll` after logging in both users:

```typescript
test.beforeAll(async ({ browser }) => {
  const userCtx = await browser.newContext();
  const userPage = await userCtx.newPage();
  userApi = await loginAs(userPage, 'user@e2e.test', 'E2eTest!2026');

  const modCtx = await browser.newContext();
  const modPage = await modCtx.newPage();
  modApi = await loginAs(modPage, 'moderator@e2e.test', 'E2eTest!2026');

  await clearMailpit(userPage.context().request);
});
```

- [ ] **Step 3: Replace `getEmailLogs` + `emailLogsContain` pattern with `searchEmails`**

The old pattern:
```typescript
const logs = await getEmailLogs(someApi, since);
expect(emailLogsContain(logs, 'Neue Projektidee eingereicht')).toBe(true);
```

Becomes:
```typescript
// Poll for up to 10s for the email to arrive
let found = false;
for (let i = 0; i < 20 && !found; i++) {
  const msgs = await searchEmails(someApi.getRawRequest(), 'subject:"Neue Projektidee eingereicht"');
  if (msgs.length > 0) { found = true; break; }
  await new Promise(r => setTimeout(r, 500));
}
expect(found, 'Expected admin notification email for submitted idea').toBe(true);
```

Apply this pattern to all 12 test cases in the file. Each test searches for its specific email subject substring. Use `clearMailpit` before each test's action to avoid matching old emails.

The subject search strings for each test:
1. `'Neue Projektidee eingereicht'` (admin email)
2. `'freigegeben'` (approval)
3. `'nicht freigegeben'` (rejection)
4. `'deiner Projektidee'` (feedback)
5. `'Beitrittsanfrage'` (join request)
6. `'angenommen'` (join accepted)
7. `'abgelehnt'` (join rejected)
8. `'eingeladen'` (member invited)
9. `'Expertise'` (expert requested)
10. `'abgelehnt'` (expert declined)
11. `'angenommen'` (expert accepted)
12. `'update'` (status update — search body, not just subject)

**Important:** The `test_space_1` and `test_space_4` IDs used in this spec must match what the seed data creates. Verify by checking `scripts/seed-e2e-data.sh`.

- [ ] **Step 4: Run notification tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/notification-emails.spec.ts \
  --reporter=line
```

Expected: All 12 tests pass (or skip if seed data doesn't have the right space).

- [ ] **Step 5: Commit**

```bash
git add tests/e2e/specs/notification-emails.spec.ts
git commit -m "fix(e2e): convert notification tests from email-log-server to Mailpit

The email-log-server (port 6008) does not run in Docker. Use the Mailpit
API (port 8025) via existing clearMailpit/searchEmails helpers."
```

---

### Task 4: Fix auth registration assertion

**Files:**
- Modify: `tests/e2e/specs/01-auth.spec.ts:23-27`

**Interfaces:**
- Consumes: CMS registration flow (redirects to `/register/do_register` or `/register/register_success`)
- Produces: Test assertion matches actual CMS redirect behavior

- [ ] **Step 1: Read the current test to understand the exact assertion**

File `tests/e2e/specs/01-auth.spec.ts`, lines 6-28. The `waitForURL` at line 23 allows `/register/` sub-paths, but the assertion at line 27 rejects any URL containing `/register`.

- [ ] **Step 2: Fix the assertion**

Replace lines 22-27:

```typescript
// CMS redirects to /register/do_register or /register/register_success_validate
await page.waitForURL((url) => {
  const path = url.pathname;
  return path.includes('/register/') && !path.endsWith('/register');
}, { timeout: 10_000 });
// Verify we're on a success/completion sub-path (not stuck on /register form)
const finalPath = new URL(page.url()).pathname;
expect(finalPath).toMatch(/\/register\/(do_register|register_success|register_pending|register_success_validate)/);
```

- [ ] **Step 3: Run auth tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/01-auth.spec.ts \
  --reporter=line
```

Expected: All 4 auth tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/e2e/specs/01-auth.spec.ts
git commit -m "fix(e2e): align register test assertion with CMS redirect behavior

CMS redirects to /register/do_register after form submission. The old
assertion rejected any URL containing /register, which contradicted
the waitForURL that allowed /register/* sub-paths."
```

---

### Task 5: Fix projektraum.spec.ts badge selector strict mode

**Files:**
- Modify: `tests/e2e/specs/projektraum.spec.ts:12`

**Interfaces:**
- Consumes: Projektraum detail page renders two badges inside `#space-status-badge` (status + category)
- Produces: Selector targets the first badge specifically

- [ ] **Step 1: Fix the badge selector**

The JS at `resources/js/single_pages/projektraum.ts:280-283` intentionally renders two `<span class="badge">` elements inside `#space-status-badge` — one for status, one for category. This is correct behavior.

In `tests/e2e/specs/projektraum.spec.ts`, line 12, change:

```typescript
await expect(page.locator('#space-status-badge .badge')).toBeVisible();
```

To:

```typescript
await expect(page.locator('#space-status-badge .badge').first()).toBeVisible();
```

- [ ] **Step 2: Fix the sidebar selector**

Check the sidebar test at line 31-36. The error was `locator('.project-space-sidebar__member-row')` — if the CSS class name differs in the rendered HTML, adjust. Run the test first to see if it passes after the container is properly set up.

- [ ] **Step 3: Run projektraum tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/projektraum.spec.ts \
  --reporter=line
```

Expected: All 6 tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/e2e/specs/projektraum.spec.ts
git commit -m "fix(e2e): use .first() for badge selector in projektraum detail

The space detail page renders two badges (status + category) inside
#space-status-badge. Using .first() avoids Playwright strict mode
violation."
```

---

### Task 6: Fix browser E2E tests and Docker environment

**Files:**
- Modify: `tests/e2e/specs/browser/auth.spec.ts` (if needed after investigation)
- Modify: `tests/e2e/specs/browser/idea-submission.spec.ts` (if needed)
- Modify: `tests/e2e/specs/browser/idea-management.spec.ts` (if needed)
- Modify: `tests/e2e/specs/browser/moderation.spec.ts` (if needed)
- Modify: `tests/e2e/specs/browser/project-spaces.spec.ts` (if needed)
- Modify: `tests/e2e/specs/auth-flow.spec.ts` (if needed)
- Possibly modify: `docker/test-entrypoint.sh`, `scripts/seed-e2e-users.sh`

**Interfaces:**
- Consumes: Docker CMS environment with installed single pages, theme, user attributes
- Produces: All browser-based E2E tests pass

This task requires investigation against the running container because the browser tests interact with the full CMS UI. The approach:

- [ ] **Step 1: Verify CMS pages exist in the container**

```bash
docker compose -f docker-compose.test.yml exec app php /var/www/html/public/concrete/bin/concrete c5:info --allow-as-root
```

Check if single pages are created:
```bash
docker compose -f docker-compose.test.yml exec app php -r "
  require '/var/www/html/public/concrete/dispatcher.php';
  \$pages = ['/projektraeume', '/projektraum', '/projektidee/einreichen', '/projektidee/meine', '/moderation', '/moderation/detail'];
  foreach (\$pages as \$path) {
    \$p = \Concrete\Core\Page\Page::getByPath(\$path);
    echo \$path . ': ' . (\$p && !\$p->isError() ? 'EXISTS (theme=' . (\$p->getCollectionThemeObject()?->getThemeHandle() ?? 'none') . ')' : 'MISSING') . PHP_EOL;
  }
"
```

- [ ] **Step 2: Run browser auth test with debug output**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/browser/auth.spec.ts \
  --reporter=line --retries=0
```

Examine the error. The `register a new account and validate email` test:
- Fills registration form, waits for redirect
- Fetches validation email from Mailpit
- Extracts confirmation link from email HTML
- Visits the link

If the confirmation link contains the container's internal hostname (e.g., `http://localhost`) but Playwright accesses `http://localhost:8880`, the link won't work. Fix by rewriting the host in the extracted URL if needed.

- [ ] **Step 3: Run auth-flow test with debug**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/auth-flow.spec.ts \
  --reporter=line --retries=0
```

The `login, visit profile edit, and logout` test fails looking for `input[name*="display_name"]`. The seed script at `scripts/seed-e2e-users.sh:99` skips setting display_name attributes. If the profile page doesn't show the field, it could be because:
- The `display_name` attribute exists but the CMS profile form doesn't include it by default
- The page uses the wrong theme (atomik instead of innovationsbox)

Investigate and fix accordingly.

- [ ] **Step 4: Run browser moderation test with debug**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/browser/moderation.spec.ts \
  --reporter=line --retries=0
```

This test navigates to `/moderation`, looks for `#reviews-container`, then clicks approve. The page must be served with the innovationsbox theme and the JS must load to render the reviews container.

- [ ] **Step 5: Run browser idea-submission test with debug**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/browser/idea-submission.spec.ts \
  --reporter=line --retries=0
```

Needs: `/projektidee/einreichen` page with `#idea-form`, `#idea-title`, `#idea-category`, CKEditor on `#idea-description`, `#idea-files`, `#idea-submit`. Also needs a test upload fixture file.

Check if fixture exists:
```bash
ls tests/e2e/fixtures/test-upload.png
```

If missing, create a small PNG file.

- [ ] **Step 6: Run remaining browser tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/browser/idea-management.spec.ts \
  tests/e2e/specs/browser/project-spaces.spec.ts \
  --reporter=line --retries=0
```

- [ ] **Step 7: Fix any issues found in steps 1-6**

Apply fixes based on investigation. Common patterns:
- Add missing pages to `test-entrypoint.sh`
- Fix URL host mismatches in email links
- Add missing user attributes to seed script
- Adjust selectors to match actual rendered HTML

- [ ] **Step 8: Run full test suite**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  --reporter=line
```

Expected: 0 failed, ~400+ passed, minimal skips.

- [ ] **Step 9: Commit all fixes**

```bash
git add -A
git commit -m "fix(e2e): fix browser E2E tests and Docker environment setup

Ensure CMS pages, theme, user attributes, and email link URLs work
correctly in the Docker test container."
```

---

### Task 7: Final verification run

**Files:** None (verification only)

- [ ] **Step 1: Rebuild Docker container from scratch**

```bash
docker compose -f docker-compose.test.yml down -v
docker compose -f docker-compose.test.yml up -d --build --wait
```

- [ ] **Step 2: Run full test suite**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  --reporter=line
```

Expected output: `0 failed`, `X passed`, minimal conditional skips.

- [ ] **Step 3: If any failures remain, investigate and fix**

For each failure:
1. Read the error message
2. Trace to root cause (don't guess)
3. Fix the minimal thing
4. Re-run that specific test
5. Commit the fix

- [ ] **Step 4: Final commit if needed**

Only if step 3 produced fixes.
