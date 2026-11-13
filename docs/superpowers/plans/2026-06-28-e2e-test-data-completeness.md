# E2E Test Data Completeness — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eliminate all 9 test skips and 2 data-related failures so that all 45 browser E2E tests pass on a fresh Docker build.

**Architecture:** Three layers of fixes — (1) seed data: add a second expert user and fix expert request relationships, (2) CMS config: create missing `/members/*` pages in the Docker entrypoint, (3) tests: remove skip guards that are now unnecessary and fix incorrect test assumptions (collaboard visibility, expert user targeting).

**Tech Stack:** Bash (seed scripts), PHP (CMS page creation), TypeScript/Playwright (test fixes)

## Global Constraints

- Tests define truth — they describe what a real user does
- `E2E_DOCKER=true` is the canonical way to run tests against the container
- Docker stack: `docker compose -f docker-compose.test.yml up -d --wait`
- All tests run via: `E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e`
- Do not change what tests verify — only fix infrastructure, seed data, and incorrect skip guards
- Password for all E2E users: `E2eTest!2026`

---

### Task 1: Add expert2 user to seed script

**Files:**
- Modify: `scripts/seed-e2e-users.sh:40-48` (INSERT statement)
- Modify: `scripts/seed-e2e-users.sh:54-60` (ID resolution + echo)
- Modify: `scripts/seed-e2e-users.sh:82-85` (group assignment)

**Interfaces:**
- Consumes: `Experte` group (created by `inno_frontend` package install)
- Produces: `expert2@e2e.test` user (uName: `e2e-expert2`) in `Experte` group, resolvable via `EXPERT2_ID`

- [ ] **Step 1: Add expert2 to the INSERT statement**

In `scripts/seed-e2e-users.sh`, replace lines 40-48:

```bash
run_sql <<SQL
INSERT INTO Users (uName, uEmail, uPassword, uIsActive, uIsFullRecord, uIsValidated, uDateAdded, uLastPasswordChange, uHasAvatar, uIsPasswordReset, uTimezone)
VALUES
  ('e2e-user', 'user@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich'),
  ('e2e-moderator', 'moderator@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich'),
  ('e2e-expert', 'expert@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich'),
  ('e2e-expert2', 'expert2@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich'),
  ('e2e-admin', 'admin@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich'),
  ('e2e-initiator', 'initiator@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich')
ON DUPLICATE KEY UPDATE uPassword='$PASSWORD_HASH', uIsActive=1, uIsValidated=1;
SQL
```

- [ ] **Step 2: Add EXPERT2_ID resolution**

After line 57 (`EXPERT_ID=...`), add:

```bash
EXPERT2_ID=$(echo "SELECT uID FROM Users WHERE uEmail='expert2@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
```

Update the echo on line 60 to include expert2:

```bash
echo "  user=$USER_ID moderator=$MOD_ID expert=$EXPERT_ID expert2=$EXPERT2_ID admin=$ADMIN_ID initiator=$INIT_ID"
```

- [ ] **Step 3: Add expert2 to Experte group**

After the existing expert group assignment block (lines 82-85), add:

```bash
if [[ -n "$EXPERT_GID" && -n "$EXPERT2_ID" ]]; then
  echo "INSERT IGNORE INTO UserGroups (uID, gID, ugEntered) VALUES ($EXPERT2_ID, $EXPERT_GID, NOW());" | run_sql
  echo "  expert2@e2e.test → Experte"
fi
```

- [ ] **Step 4: Verify locally**

```bash
docker compose -f docker-compose.test.yml exec -T app bash -c "cd /var/www/html && bash scripts/seed-e2e-users.sh"
```

Expected: "expert2@e2e.test → Experte" in the output.

- [ ] **Step 5: Commit**

```bash
git add scripts/seed-e2e-users.sh
git commit -m "feat(seed): add expert2@e2e.test user for E2E test isolation

A second expert user ensures the expert-select dropdown has an available
expert (expert@e2e.test) while a separate user (expert2@e2e.test) has
the pending expert request for the decline test."
```

---

### Task 2: Fix seed data relationships

**Files:**
- Modify: `scripts/seed-e2e-data.sh:31-44` (add EXPERT2_ID resolution)
- Modify: `scripts/seed-e2e-data.sh:99-112` (members — remove e2e_mem_active_pending)
- Modify: `scripts/seed-e2e-data.sh:127-131` (expert request — use EXPERT2_ID)
- Modify: `scripts/seed-e2e-data.sh:133-140` (summary echo)

**Interfaces:**
- Consumes: `expert2@e2e.test` user from Task 1
- Produces: `e2e_ereq_pending` with `expert_user_id = EXPERT2_ID`; `expert@e2e.test` has no membership or request in any space

- [ ] **Step 1: Add EXPERT2_ID resolution**

In `scripts/seed-e2e-data.sh`, after line 36 (`EXPERT_ID=...`), add:

```bash
EXPERT2_ID=$(echo "SELECT uID FROM Users WHERE uEmail='expert2@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
```

Update the validation check on line 38 to include EXPERT2_ID:

```bash
if [[ -z "$INITIATOR_ID" || -z "$USER_ID" || -z "$MODERATOR_ID" || -z "$EXPERT_ID" || -z "$EXPERT2_ID" ]]; then
  echo "ERROR: Could not resolve E2E user IDs. Run seed-e2e-users.sh first."
  echo "  initiator=$INITIATOR_ID user=$USER_ID moderator=$MODERATOR_ID expert=$EXPERT_ID expert2=$EXPERT2_ID"
  exit 1
fi
```

Update the echo on line 44:

```bash
echo "  initiator=$INITIATOR_ID user=$USER_ID moderator=$MODERATOR_ID expert=$EXPERT_ID expert2=$EXPERT2_ID"
```

- [ ] **Step 2: Remove expert membership from active space**

Replace the members INSERT (lines 99-112) — remove the `e2e_mem_active_pending` row:

```sql
INSERT INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at, decided_at) VALUES
-- Active space: initiator + 1 accepted member
('e2e_mem_active_init', 'e2e_space_active', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 50 DAY), NULL),
('e2e_mem_active_user', 'e2e_space_active', $USER_ID, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 39 DAY)),
-- Draft space: initiator only
('e2e_mem_draft_init', 'e2e_space_draft', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 5 DAY), NULL),
-- Completed space: initiator + member
('e2e_mem_compl_init', 'e2e_space_completed', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 200 DAY), NULL),
('e2e_mem_compl_user', 'e2e_space_completed', $USER_ID, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 190 DAY), DATE_SUB(NOW(), INTERVAL 189 DAY)),
-- Archived space: initiator only
('e2e_mem_arch_init', 'e2e_space_archived', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 365 DAY), NULL);
```

- [ ] **Step 3: Update expert request to use expert2**

Replace line 129-131 (expert request INSERT):

```sql
INSERT INTO ib_expert_requests (id, space_id, expert_user_id, requested_by_user_id, message, status, requested_at, decided_at) VALUES
('e2e_ereq_pending', 'e2e_space_active', $EXPERT2_ID, $INITIATOR_ID, 'Wir brauchen Expertise im Bereich Energie.', 'pending', DATE_SUB(NOW(), INTERVAL 3 DAY), NULL);
```

- [ ] **Step 4: Update summary echo**

Replace lines 135-139:

```bash
echo "Users: user@e2e.test (ID=$USER_ID), moderator@e2e.test (ID=$MODERATOR_ID), expert@e2e.test (ID=$EXPERT_ID), expert2@e2e.test (ID=$EXPERT2_ID), admin@e2e.test, initiator@e2e.test (ID=$INITIATOR_ID)"
echo "Categories: Energie, Mobilität, Biodiversität"
echo "Ideas: draft, submitted, under_review, approved, rejected, feedback_requested"
echo "Spaces: draft, active, completed, archived"
echo "Active space has: 1 accepted member, 1 pending expert request (expert2), 2 updates, 1 collaboard link"
```

- [ ] **Step 5: Commit**

```bash
git add scripts/seed-e2e-data.sh
git commit -m "fix(seed): use expert2 for expert request, remove conflicting membership

expert@e2e.test is now free of memberships and requests — available in
the expert-select dropdown. expert2@e2e.test owns the seeded pending
expert request for the decline-flow test."
```

---

### Task 3: Create CMS member pages in Docker entrypoint

**Files:**
- Create: `scripts/create-member-pages.php`
- Modify: `docker/test-entrypoint.sh:86-87` (add page creation step after SMTP config)

**Interfaces:**
- Consumes: CMS installed with packages
- Produces: `/members`, `/members/directory`, `/members/profile` CMS single pages

- [ ] **Step 1: Create the PHP script**

Create `scripts/create-member-pages.php`:

```php
<?php

use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single as SinglePage;

$pages = ['/members', '/members/directory', '/members/profile'];
foreach ($pages as $path) {
    $p = Page::getByPath($path);
    if (!$p || $p->isError()) {
        SinglePage::add($path);
        echo "Created: $path\n";
    } else {
        echo "Exists: $path\n";
    }
}
```

- [ ] **Step 2: Call the script from test-entrypoint.sh**

In `docker/test-entrypoint.sh`, after the captcha disable line (around line 88) and before "Creating E2E test users", add:

```bash
  echo "=== Creating CMS member pages ==="
  /var/www/html/public/concrete/bin/concrete c5:exec /var/www/html/scripts/create-member-pages.php
```

- [ ] **Step 3: Verify locally**

```bash
docker compose -f docker-compose.test.yml down -v
docker compose -f docker-compose.test.yml up -d --build --wait
```

Then check:

```bash
docker compose -f docker-compose.test.yml exec -T app bash -c "cd /var/www/html && ./vendor/bin/concrete c5:exec scripts/create-member-pages.php"
```

Expected: "Exists: /members", "Exists: /members/directory", "Exists: /members/profile"

- [ ] **Step 4: Commit**

```bash
git add scripts/create-member-pages.php docker/test-entrypoint.sh
git commit -m "feat(docker): create /members/* CMS pages in test entrypoint

The atomik_blank starting point doesn't create member pages. The
profile-access and expert-pool E2E tests need them."
```

---

### Task 4: Remove skip guards from profile-access and expert-pool tests

**Files:**
- Modify: `tests/e2e/specs/profile-access.spec.ts:14-15,27-28,37-38,52-53`
- Modify: `tests/e2e/specs/expert-pool.spec.ts:7-8,31-32`

**Interfaces:**
- Consumes: `/members/profile` and `/members/directory` pages from Task 3
- Produces: Tests that fail (not skip) if pages are missing

- [ ] **Step 1: Remove skip guards from profile-access.spec.ts**

Remove the `res` + `test.skip` pair from all 4 tests. In each test, delete the two lines:

```typescript
    const res = await page.request.get('/members/profile');
    test.skip(res.status() === 404, 'Members profile not available in this environment');
```

The tests at lines 11, 24, 36, 51 each have this pattern. Remove all 4 occurrences (8 lines total).

- [ ] **Step 2: Remove skip guards from expert-pool.spec.ts**

Remove the `res` + `test.skip` pair from both tests. In each test, delete:

```typescript
    const res = await page.request.get('/members/directory');
    test.skip(res.status() === 404, 'Members directory not available in this environment');
```

Lines 7-8 and 31-32. Remove both occurrences (4 lines total).

- [ ] **Step 3: Run the affected tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/profile-access.spec.ts \
  tests/e2e/specs/expert-pool.spec.ts \
  --reporter=line --retries=0
```

Expected: 6 passed, 0 skipped, 0 failed.

- [ ] **Step 4: Commit**

```bash
git add tests/e2e/specs/profile-access.spec.ts tests/e2e/specs/expert-pool.spec.ts
git commit -m "fix(e2e): remove 404 skip guards from profile-access and expert-pool

The /members/* pages are now created in the Docker test entrypoint."
```

---

### Task 5: Fix projektraum collaboard test to login as initiator

**Files:**
- Modify: `tests/e2e/specs/projektraum.spec.ts:44-53`

**Interfaces:**
- Consumes: `e2e_space_active` with `e2e_cb_1` collaboard link, `initiator@e2e.test` as space initiator
- Produces: Test that always runs (no skip)

- [ ] **Step 1: Replace the collaboard test**

Replace lines 44-53 in `tests/e2e/specs/projektraum.spec.ts`:

```typescript
  test('collaboard section is present', async ({ page }) => {
    // Collaboard links are only visible to members/initiators of the space
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(DETAIL_URL);
    const panel = page.locator('.collaboard-panel');
    await expect(panel).toBeVisible({ timeout: 10_000 });
    // Verify it contains the seeded collaboard link
    await expect(panel).toContainText('Brainstorming Board');
  });
```

- [ ] **Step 2: Run the projektraum tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/projektraum.spec.ts \
  --reporter=line --retries=0
```

Expected: 7 passed, 0 skipped, 0 failed.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/projektraum.spec.ts
git commit -m "fix(e2e): login as initiator for collaboard panel test

Collaboard links are only rendered for space members/initiators. The
test was running as anonymous, which meant the panel was never visible."
```

---

### Task 6: Fix expert-flow tests to use correct expert users

**Files:**
- Modify: `tests/e2e/specs/browser/expert-flow.spec.ts` (full file)

**Interfaces:**
- Consumes: `expert@e2e.test` (available expert, no membership/request), `expert2@e2e.test` (has pending request), `initiator@e2e.test` (space owner)
- Produces: Both expert-flow tests run without skipping

- [ ] **Step 1: Rewrite the expert-flow spec**

Replace the entire file `tests/e2e/specs/browser/expert-flow.spec.ts`:

```typescript
import { test, expect } from '@playwright/test';

import { clearMailpit, getMailpitMessage, loginViaUI } from '../../fixtures/helpers';

test.describe.serial('Browser: Expert flow', () => {
  test('request expert from space and verify email', async ({ page, request }) => {
    await clearMailpit(request);

    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');

    // Navigate to own active space
    await page.goto('/projektraum/view/e2e_space_active');
    await page.waitForSelector('#space-header, #space-title', { timeout: 10_000 });

    // Expert select should be visible for the initiator
    const expertSelect = page.locator('#expert-select');
    await expect(expertSelect).toBeVisible({ timeout: 10_000 });

    // Wait for options to load from API
    await page.waitForFunction(
      () => {
        const sel = document.getElementById('expert-select') as HTMLSelectElement | null;
        return sel && sel.options.length > 1;
      },
      { timeout: 10_000 },
    );

    // Select the first available expert (expert@e2e.test)
    const firstExpert = await page
      .locator('#expert-select option:not([value=""])')
      .first()
      .getAttribute('value');
    expect(firstExpert).toBeTruthy();
    await page.selectOption('#expert-select', firstExpert!);

    // Fill optional message
    const messageField = page.locator('#expert-message');
    if (await messageField.isVisible()) {
      await messageField.fill('We need your expertise on energy efficiency.');
    }

    // Submit request
    await page.click('#btn-request-expert');
    await page.waitForTimeout(2000);

    // Verify email to expert
    const msg = await getMailpitMessage(request, 'expert@e2e.test', 'Expertise');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  test('expert declines request and verify email', async ({ page, request }) => {
    await clearMailpit(request);

    // Login as expert2 who has the seeded pending request
    await loginViaUI(page, 'expert2@e2e.test', 'E2eTest!2026');
    await page.goto('/expertanfragen');

    // Wait for requests to load
    await page.waitForSelector('#requests-container', { timeout: 10_000 });

    // Find the decline button for the pending request
    const declineBtn = page.locator('button[data-accept="false"]').first();
    await expect(declineBtn).toBeVisible({ timeout: 10_000 });

    await declineBtn.click();
    await page.waitForTimeout(2000);

    // Verify the badge shows declined status after page reload
    await page.waitForSelector('#requests-container', { timeout: 10_000 });
    const declinedBadge = page.locator('.badge:has-text("Abgelehnt")');
    await expect(declinedBadge.first()).toBeVisible({ timeout: 10_000 });

    // Verify email to initiator
    const msg = await getMailpitMessage(request, 'initiator@e2e.test', 'abgelehnt');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });
});
```

- [ ] **Step 2: Run the expert-flow tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/browser/expert-flow.spec.ts \
  --reporter=line --retries=0
```

Expected: 2 passed, 0 skipped, 0 failed.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/browser/expert-flow.spec.ts
git commit -m "fix(e2e): use correct expert users, remove skip guards

Test 1 requests expert@e2e.test (available, not a member).
Test 2 declines as expert2@e2e.test (has seeded pending request).
Both tests are now independent and always run."
```

---

### Task 7: Fix project-spaces write-update test

**Files:**
- Modify: `tests/e2e/specs/browser/project-spaces.spec.ts:65-108`

**Interfaces:**
- Consumes: `e2e_space_active` (status=active), `initiator@e2e.test` (space initiator), `e2e_idea_approved` (has project_space_id)
- Produces: Write-update test runs without skipping

- [ ] **Step 1: Rewrite the write-update test**

Replace lines 65-108 in `tests/e2e/specs/browser/project-spaces.spec.ts`:

```typescript
  test('write status update with CKEditor and verify email', async ({ page, request }) => {
    await clearMailpit(request);

    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');

    // Navigate directly to the active space (seed data guarantees it exists)
    await page.goto('/projektraum/view/e2e_space_active');
    await page.waitForSelector('#space-header, #space-title', { timeout: 10_000 });

    // Write update button should be visible for the initiator on an active space
    const writeBtn = page.locator('#btn-write-update');
    await expect(writeBtn).toBeVisible({ timeout: 10_000 });
    await writeBtn.click();

    // Fill update modal
    const updateTitle = `Browser Update ${String(Date.now())}`;
    await page.fill('#update-title', updateTitle);
    await fillCKEditor(page, 'update-body', 'This is a status update written by a browser E2E test.');

    // Save
    await page.click('#update-modal button[type="submit"], #update-modal .btn--primary');
    await page.waitForTimeout(2000);

    // Verify update appears in timeline
    await expect(page.locator('#status-update-timeline-container, .status-update-timeline')).toContainText(
      updateTitle,
      { timeout: 5_000 },
    );

    // Verify email to space members
    const msg = await getMailpitMessage(request, '', updateTitle);
    expect(msg.Subject).toContain(updateTitle);
  });
```

- [ ] **Step 2: Ensure fillCKEditor is imported**

Check that the import at the top of the file includes `fillCKEditor`. The current imports (line 4-8) are:

```typescript
import {
  clearMailpit,
  fillCKEditor,
  getMailpitMessage,
  loginViaUI,
} from '../../fixtures/helpers';
```

If `fillCKEditor` is already imported, no change needed. If not, add it.

- [ ] **Step 3: Run the project-spaces tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/browser/project-spaces.spec.ts \
  --reporter=line --retries=0
```

Expected: 4 passed, 0 skipped, 0 failed.

- [ ] **Step 4: Commit**

```bash
git add tests/e2e/specs/browser/project-spaces.spec.ts
git commit -m "fix(e2e): navigate directly to space for write-update test

Instead of navigating through /projektidee/meine and finding a link,
go directly to /projektraum/view/e2e_space_active. The seed data
guarantees this space exists and is active."
```

---

### Task 8: Full test suite verification

**Files:** None (verification only)

- [ ] **Step 1: Rebuild Docker from scratch**

```bash
docker compose -f docker-compose.test.yml down -v
docker compose -f docker-compose.test.yml up -d --build --wait
```

- [ ] **Step 2: Run all browser E2E tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  tests/e2e/specs/browser/ \
  tests/e2e/specs/auth-flow.spec.ts \
  tests/e2e/specs/projektraeume.spec.ts \
  tests/e2e/specs/projektraum.spec.ts \
  tests/e2e/specs/profile-access.spec.ts \
  tests/e2e/specs/expert-pool.spec.ts \
  tests/e2e/specs/01-auth.spec.ts \
  --reporter=list --retries=0
```

Expected: 45 passed, 0 skipped, 0 failed.

- [ ] **Step 3: If any failures, investigate and fix**

For each failure:
1. Read the error message
2. Trace to root cause
3. Fix the minimal thing
4. Re-run that specific test
5. Commit the fix

- [ ] **Step 4: Run the full E2E suite (including API tests)**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e \
  --reporter=list --retries=0
```

Verify no regressions in API-level tests from the seed data changes.
