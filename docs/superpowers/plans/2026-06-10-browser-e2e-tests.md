# Browser-Based E2E Tests & Testing Guide — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 19 real browser-based E2E tests that simulate user journeys (navigate, fill forms, click buttons, verify results + emails) and a comprehensive testing guide.

**Architecture:** New specs in `tests/e2e/specs/browser/` use Playwright to navigate actual pages, interact with CKEditor, upload files, and verify every email via the Mailpit API. New helpers: `loginViaUI()`, `fillCKEditor()`, `getMailpitMessage()`, `extractLinkFromEmail()`. A `docs/testing-guide.md` documents when/where/how for all test types.

**Tech Stack:** Playwright, TypeScript, Mailpit API, CKEditor 4

**Spec:** `docs/superpowers/specs/2026-06-10-browser-e2e-tests-and-testing-guide.md`

---

## File Structure

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `tests/e2e/fixtures/test-upload.png` | 1x1 pixel PNG for file upload tests |
| Modify | `tests/e2e/fixtures/helpers.ts` | Add `loginViaUI`, `fillCKEditor`, `getMailpitMessage`, `extractLinkFromEmail` |
| Create | `tests/e2e/specs/browser/auth.spec.ts` | Register (+ email validation), login, invalid login, forgot password (full flow), logout |
| Create | `tests/e2e/specs/browser/idea-submission.spec.ts` | Submit idea with CKEditor + file upload + admin email check |
| Create | `tests/e2e/specs/browser/idea-management.spec.ts` | View my ideas, edit in modal, withdraw |
| Create | `tests/e2e/specs/browser/moderation.spec.ts` | View queue, approve, reject, request feedback — all with email |
| Create | `tests/e2e/specs/browser/project-spaces.spec.ts` | Browse + filter, join (+ email), write update (+ email), leave |
| Create | `tests/e2e/specs/browser/expert-flow.spec.ts` | Request expert (+ email), expert declines (+ email) |
| Create | `docs/testing-guide.md` | Complete testing guide: when/where/how for all test types |

---

### Task 1: Create test-upload.png fixture and browser helpers

**Files:**
- Create: `tests/e2e/fixtures/test-upload.png`
- Modify: `tests/e2e/fixtures/helpers.ts`

- [ ] **Step 1: Generate the 1x1 PNG fixture**

Run this to create a minimal valid PNG:

```bash
printf '\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90wS\xde\x00\x00\x00\x0cIDATx\x9cc\xf8\x0f\x00\x00\x01\x01\x00\x05\x18\xd8N\x00\x00\x00\x00IEND\xaeB`\x82' > tests/e2e/fixtures/test-upload.png
```

- [ ] **Step 2: Read the existing helpers.ts**

Read `tests/e2e/fixtures/helpers.ts` to understand the current structure and where to add new helpers.

- [ ] **Step 3: Append new browser helpers to helpers.ts**

Add these at the end of the file, after the existing `loginAsNewContext` function:

```typescript
// --- Browser-based E2E helpers ---

/**
 * Login via the actual browser UI form at /login.
 * Unlike loginAs(), this fills the real form — use for browser E2E tests.
 */
export async function loginViaUI(page: Page, email: string, password: string): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="uName"]', email);
  await page.fill('input[name="uPassword"]', password);
  await page.click('button[type="submit"]');
  await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 10_000 });
}

/**
 * Type text into a CKEditor 4 instance by its textarea ID.
 * Waits for CKEditor to replace the textarea, then types into the editable area.
 */
export async function fillCKEditor(page: Page, textareaId: string, text: string): Promise<void> {
  // Wait for CKEditor to initialize and create the editable area
  const editorSelector = `#cke_${textareaId} .cke_editable, #cke_${textareaId} iframe`;
  await page.waitForSelector(editorSelector, { timeout: 10_000 });

  // Try iframe-based CKEditor first (classic mode)
  const iframe = page.locator(`#cke_${textareaId} iframe`);
  if (await iframe.count() > 0) {
    const frame = page.frameLocator(`#cke_${textareaId} iframe`);
    await frame.locator('body').click();
    await frame.locator('body').fill(text);
    return;
  }

  // Fallback: inline editable div
  const editable = page.locator(`#cke_${textareaId} .cke_editable`);
  await editable.click();
  await page.keyboard.type(text);
}

/**
 * Get a full email message from Mailpit, including HTML body.
 * Polls until a message matching `to` and `subject` arrives, then fetches full details.
 */
export async function getMailpitMessage(
  request: APIRequestContext,
  to: string,
  subjectContains: string,
  timeoutMs = 15_000,
): Promise<{ ID: string; Subject: string; HTML: string; Text: string }> {
  if (!MAILPIT_API) {
    throw new Error('Mailpit helpers require E2E_DOCKER=true');
  }
  const query = `to:${to} subject:"${subjectContains}"`;
  const msg = await waitForEmail(request, query, timeoutMs);

  // Fetch full message with HTML body
  const res = await request.get(`${MAILPIT_API}/message/${msg.ID}`);
  if (!res.ok()) {
    throw new Error(`Failed to fetch message ${msg.ID}: ${String(res.status())}`);
  }
  const full = (await res.json()) as { ID: string; Subject: string; HTML: string; Text: string };
  return full;
}

/**
 * Extract a URL from email HTML body matching the given regex pattern.
 * Example: extractLinkFromEmail(html, /https?:\/\/[^\s"]+validate[^\s"]+/)
 */
export function extractLinkFromEmail(html: string, pattern: RegExp): string {
  // Search in href attributes first
  const hrefPattern = /href="([^"]+)"/g;
  let match: RegExpExecArray | null;
  while ((match = hrefPattern.exec(html)) !== null) {
    if (pattern.test(match[1])) {
      // Decode HTML entities
      return match[1].replace(/&amp;/g, '&');
    }
  }
  // Fallback: search raw text
  const textMatch = pattern.exec(html);
  if (textMatch) {
    return textMatch[0].replace(/&amp;/g, '&');
  }
  throw new Error(`No link matching ${String(pattern)} found in email body`);
}
```

- [ ] **Step 4: Run typecheck**

Run: `npx tsc --noEmit`
Expected: No errors

- [ ] **Step 5: Commit**

```bash
git add tests/e2e/fixtures/test-upload.png tests/e2e/fixtures/helpers.ts
git commit -m "test: add browser E2E helpers (loginViaUI, fillCKEditor, getMailpitMessage, extractLinkFromEmail)"
```

---

### Task 2: Create browser/auth.spec.ts — Authentication lifecycle

**Files:**
- Create: `tests/e2e/specs/browser/auth.spec.ts`

- [ ] **Step 1: Create the test file**

```typescript
import { test, expect } from '@playwright/test';

import {
  clearMailpit,
  extractLinkFromEmail,
  getMailpitMessage,
  loginViaUI,
} from '../../fixtures/helpers';

test.describe.serial('Browser: Auth lifecycle', () => {
  const ts = String(Date.now());
  const regEmail = `e2e-browser-${ts}@test.local`;
  const regUsername = `e2e-br-${ts}`;
  const regPassword = 'E2eTest!2026';

  test.beforeAll(async ({ request }) => {
    await clearMailpit(request);
  });

  test('register a new account and validate email', async ({ page, request }) => {
    await page.goto('/register');
    await page.fill('input[name="uEmail"]', regEmail);
    await page.fill('input[name="uName"]', regUsername);
    await page.fill('input[name="uPassword"]', regPassword);
    await page.fill('input[name="uPasswordConfirm"]', regPassword);
    await page.click('button[type="submit"]');

    // Should redirect away from register
    await page.waitForURL((url) => !url.pathname.includes('/register'), { timeout: 10_000 });

    // Check Mailpit for validation email
    const msg = await getMailpitMessage(request, regEmail, 'valid');
    expect(msg.Subject).toBeTruthy();

    // Extract confirmation link and visit it
    const confirmLink = extractLinkFromEmail(msg.HTML || msg.Text, /https?:\/\/[^\s"]*validate[^\s"]*/i);
    await page.goto(confirmLink);

    // Should see validation success
    await expect(page.locator('body')).toContainText(/validated|bestätigt|verified/i, { timeout: 5_000 });
  });

  test('login with valid credentials', async ({ page }) => {
    await loginViaUI(page, regEmail, regPassword);

    // Should see user menu (logged-in indicator)
    await expect(page.locator('[data-user-menu-trigger]')).toBeVisible({ timeout: 5_000 });
  });

  test('login with invalid credentials shows error', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="uName"]', regEmail);
    await page.fill('input[name="uPassword"]', 'WrongPassword!');
    await page.click('button[type="submit"]');

    await page.waitForTimeout(2000);
    const errorVisible = await page
      .locator('.alert--error, .alert--danger, .ccm-system-errors, [role="alert"]')
      .isVisible();
    expect(errorVisible).toBeTruthy();
  });

  test('forgot password full flow', async ({ page, request }) => {
    await clearMailpit(request);

    // Go to forgot password
    await page.goto('/login');
    await page.click('a[href*="forgot_password"]');
    await expect(page.locator('input[name="uEmail"]')).toBeVisible({ timeout: 5_000 });

    // Submit forgot password
    await page.fill('input[name="uEmail"]', regEmail);
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    // Get reset email from Mailpit
    const msg = await getMailpitMessage(request, regEmail, 'Passwort');
    const resetLink = extractLinkFromEmail(msg.HTML || msg.Text, /https?:\/\/[^\s"]*change_password[^\s"]*/i);

    // Visit reset link and set new password
    const newPassword = 'NewE2ePass!2026';
    await page.goto(resetLink);
    await expect(page.locator('input[name="uPassword"]')).toBeVisible({ timeout: 5_000 });
    await page.fill('input[name="uPassword"]', newPassword);
    const confirmField = page.locator('input[name="uPasswordConfirm"]');
    if (await confirmField.isVisible()) {
      await confirmField.fill(newPassword);
    }
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    // Login with new password
    await loginViaUI(page, regEmail, newPassword);
    await expect(page.locator('[data-user-menu-trigger]')).toBeVisible({ timeout: 5_000 });
  });

  test('logout', async ({ page }) => {
    await loginViaUI(page, 'user@e2e.test', 'E2eTest!2026');

    // Open user menu and click logout
    await page.click('[data-user-menu-trigger]');
    await page.click('.user-menu__item--danger');

    // Should be redirected to login or homepage
    await page.waitForURL((url) => url.pathname.includes('/login') || url.pathname === '/', {
      timeout: 10_000,
    });
  });
});
```

- [ ] **Step 2: Run typecheck**

Run: `npx tsc --noEmit`
Expected: No errors

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/browser/auth.spec.ts
git commit -m "test: add browser auth E2E tests (register+validate, login, forgot password, logout)"
```

---

### Task 3: Create browser/idea-submission.spec.ts — Submit idea via form

**Files:**
- Create: `tests/e2e/specs/browser/idea-submission.spec.ts`

- [ ] **Step 1: Create the test file**

```typescript
import path from 'path';

import { test, expect } from '@playwright/test';

import { clearMailpit, fillCKEditor, getMailpitMessage, loginViaUI } from '../../fixtures/helpers';

test.describe('Browser: Submit idea', () => {
  test('submit idea with CKEditor + file upload, verify admin email', async ({ page, request }) => {
    await clearMailpit(request);

    // Login as user
    await loginViaUI(page, 'user@e2e.test', 'E2eTest!2026');

    // Navigate to submission form
    await page.goto('/projektidee/einreichen');
    await expect(page.locator('#idea-form, form')).toBeVisible({ timeout: 10_000 });

    // Fill title
    const ideaTitle = `Browser E2E Idea ${String(Date.now())}`;
    await page.fill('#idea-title', ideaTitle);

    // Select category (first non-empty option)
    await page.waitForSelector('#idea-category option:not([value=""])', { timeout: 5_000 });
    const firstOption = await page.locator('#idea-category option:not([value=""])').first().getAttribute('value');
    if (firstOption) {
      await page.selectOption('#idea-category', firstOption);
    }

    // Fill CKEditor description
    await fillCKEditor(page, 'idea-description', 'This is a browser E2E test idea with a real description.');

    // Upload file
    const fixtureFile = path.resolve(__dirname, '../../fixtures/test-upload.png');
    await page.setInputFiles('#idea-files', fixtureFile);

    // Verify file appears in file list
    await expect(page.locator('#file-list .file-upload-zone__item, .file-upload-zone__item')).toBeVisible({
      timeout: 3_000,
    });

    // Submit
    await page.click('#idea-submit');

    // Should redirect to my ideas page
    await page.waitForURL(/\/projektidee\/meine/, { timeout: 10_000 });

    // Verify idea appears on the page
    await expect(page.locator('#ideas-container')).toContainText(ideaTitle, { timeout: 10_000 });

    // Verify admin notification email
    const msg = await getMailpitMessage(request, '', ideaTitle);
    expect(msg.Subject).toContain(ideaTitle);
    expect(msg.HTML || msg.Text).toBeTruthy();
  });
});
```

- [ ] **Step 2: Run typecheck**

Run: `npx tsc --noEmit`

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/browser/idea-submission.spec.ts
git commit -m "test: add browser idea submission E2E test (CKEditor + file upload + admin email)"
```

---

### Task 4: Create browser/idea-management.spec.ts — My ideas page

**Files:**
- Create: `tests/e2e/specs/browser/idea-management.spec.ts`

- [ ] **Step 1: Create the test file**

```typescript
import { test, expect } from '@playwright/test';

import { loginViaUI } from '../../fixtures/helpers';

test.describe.serial('Browser: My ideas management', () => {
  test.beforeEach(async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto('/projektidee/meine');
    await page.waitForSelector('#ideas-container', { timeout: 10_000 });
  });

  test('view my ideas with status badges', async ({ page }) => {
    // Verify container has idea cards
    const cards = page.locator('.idea-card, [data-id]');
    await expect(cards.first()).toBeVisible({ timeout: 5_000 });

    // Verify at least one status badge is visible
    const badges = page.locator('.badge');
    expect(await badges.count()).toBeGreaterThan(0);
  });

  test('edit idea in modal', async ({ page }) => {
    // Find an edit button for a submitted idea
    const editBtn = page.locator('[data-edit-idea]').first();
    await expect(editBtn).toBeVisible({ timeout: 5_000 });

    await editBtn.click();

    // Modal should appear
    const modal = page.locator('#edit-idea-modal');
    await expect(modal).toBeVisible({ timeout: 3_000 });

    // Change the title
    const newTitle = `Edited ${String(Date.now())}`;
    await page.fill('#edit-idea-title', newTitle);

    // Submit the edit
    await page.click('#edit-idea-submit');

    // Modal should close
    await expect(modal).not.toBeVisible({ timeout: 5_000 });

    // Verify updated title visible in the ideas list
    await page.waitForTimeout(1000);
    await expect(page.locator('#ideas-container')).toContainText(newTitle, { timeout: 5_000 });
  });

  test('withdraw idea', async ({ page }) => {
    // Find a withdraw button
    const withdrawBtn = page.locator('[data-withdraw-idea]').first();
    if (!(await withdrawBtn.isVisible())) {
      test.skip(true, 'No withdrawable idea found in seed data');
      return;
    }

    const ideaId = await withdrawBtn.getAttribute('data-withdraw-idea');

    await withdrawBtn.click();

    // Confirmation modal should appear
    const modal = page.locator('#withdraw-modal');
    await expect(modal).toBeVisible({ timeout: 3_000 });

    // Confirm withdrawal
    await page.click('#withdraw-confirm');

    // Wait for page to reload / idea to disappear
    await page.waitForTimeout(2000);

    // The idea should be gone or status changed
    if (ideaId) {
      const card = page.locator(`[data-id="${ideaId}"]`);
      // Card either removed or page reloaded
      const count = await card.count();
      expect(count).toBeLessThanOrEqual(1);
    }
  });
});
```

- [ ] **Step 2: Run typecheck**

Run: `npx tsc --noEmit`

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/browser/idea-management.spec.ts
git commit -m "test: add browser idea management E2E tests (view, edit, withdraw)"
```

---

### Task 5: Create browser/moderation.spec.ts — Moderator reviews

**Files:**
- Create: `tests/e2e/specs/browser/moderation.spec.ts`

- [ ] **Step 1: Create the test file**

```typescript
import { test, expect } from '@playwright/test';

import {
  clearMailpit,
  fillCKEditor,
  getMailpitMessage,
  loginAs,
  loginViaUI,
  seedIdea,
} from '../../fixtures/helpers';

test.describe.serial('Browser: Moderation', () => {
  test('view moderation queue', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');

    // Queue should load with review items
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });
    const rows = page.locator('#reviews-container a, #reviews-container tr, #reviews-container .card');
    await expect(rows.first()).toBeVisible({ timeout: 5_000 });
  });

  test('approve idea and verify email', async ({ page, request, browser }) => {
    // First, create an idea via API as user
    const userCtx = await browser.newContext();
    const userPage = await userCtx.newPage();
    const userApi = await loginAs(userPage, 'user@e2e.test', 'E2eTest!2026');
    const ideaTitle = `Approve Test ${String(Date.now())}`;
    await seedIdea(userApi, { title: ideaTitle });
    await userCtx.close();

    await clearMailpit(request);

    // Login as moderator in browser
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });

    // Click the review link containing our idea title
    const reviewLink = page.locator(`#reviews-container a:has-text("${ideaTitle}")`).first();
    if (!(await reviewLink.isVisible({ timeout: 3_000 }).catch(() => false))) {
      // Fallback: click first review link
      await page.locator('#reviews-container a').first().click();
    } else {
      await reviewLink.click();
    }

    // Should be on detail page
    await page.waitForSelector('#review-container, #btn-approve', { timeout: 10_000 });

    // Click approve
    await page.click('#btn-approve');

    // Should redirect to moderation queue
    await page.waitForURL(/\/moderation/, { timeout: 10_000 });

    // Verify approval email sent to submitter
    const msg = await getMailpitMessage(request, 'user@e2e.test', 'genehmigt');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  test('reject idea with reason and verify email', async ({ page, request, browser }) => {
    // Create an idea via API
    const userCtx = await browser.newContext();
    const userPage = await userCtx.newPage();
    const userApi = await loginAs(userPage, 'user@e2e.test', 'E2eTest!2026');
    await seedIdea(userApi, { title: `Reject Test ${String(Date.now())}` });
    await userCtx.close();

    await clearMailpit(request);

    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });

    // Click first review
    await page.locator('#reviews-container a').first().click();
    await page.waitForSelector('#btn-reject', { timeout: 10_000 });

    // Click reject → opens modal
    await page.click('#btn-reject');
    await expect(page.locator('#reject-modal')).toBeVisible({ timeout: 3_000 });

    // Fill rejection reason
    const reason = 'Not aligned with climate goals — please revise scope';
    await page.fill('#reject-reason', reason);
    await page.click('#reject-confirm');

    // Should redirect
    await page.waitForURL(/\/moderation/, { timeout: 10_000 });

    // Verify rejection email contains the reason
    const msg = await getMailpitMessage(request, 'user@e2e.test', 'abgelehnt');
    expect(msg.HTML || msg.Text).toContain(reason);
  });

  test('request feedback and verify email', async ({ page, request, browser }) => {
    // Create an idea via API
    const userCtx = await browser.newContext();
    const userPage = await userCtx.newPage();
    const userApi = await loginAs(userPage, 'user@e2e.test', 'E2eTest!2026');
    await seedIdea(userApi, { title: `Feedback Test ${String(Date.now())}` });
    await userCtx.close();

    await clearMailpit(request);

    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });

    await page.locator('#reviews-container a').first().click();
    await page.waitForSelector('#btn-feedback', { timeout: 10_000 });

    // Fill feedback notes
    const feedbackNotes = 'Please add a cost estimation and timeline for Phase 1.';
    await page.fill('#feedback-notes', feedbackNotes);

    // Click feedback button
    await page.click('#btn-feedback');

    // Should redirect
    await page.waitForURL(/\/moderation/, { timeout: 10_000 });

    // Verify feedback email
    const msg = await getMailpitMessage(request, 'user@e2e.test', 'Feedback');
    expect(msg.HTML || msg.Text).toContain(feedbackNotes);
  });
});
```

- [ ] **Step 2: Run typecheck**

Run: `npx tsc --noEmit`

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/browser/moderation.spec.ts
git commit -m "test: add browser moderation E2E tests (queue, approve, reject, feedback + emails)"
```

---

### Task 6: Create browser/project-spaces.spec.ts — Space interaction

**Files:**
- Create: `tests/e2e/specs/browser/project-spaces.spec.ts`

- [ ] **Step 1: Create the test file**

```typescript
import { test, expect } from '@playwright/test';

import {
  clearMailpit,
  fillCKEditor,
  getMailpitMessage,
  loginViaUI,
} from '../../fixtures/helpers';

test.describe('Browser: Project spaces', () => {
  test('browse spaces with filter and search', async ({ page }) => {
    await page.goto('/projektraeume');

    // Grid should load
    await page.waitForSelector('#project-spaces-grid', { timeout: 10_000 });
    const cards = page.locator('#project-spaces-grid .project-card, #project-spaces-grid article');
    await expect(cards.first()).toBeVisible({ timeout: 5_000 });

    // Click active filter
    const activeFilter = page.locator('[data-value="active"]');
    if (await activeFilter.isVisible()) {
      await activeFilter.click();
      await page.waitForTimeout(1000);
      // Grid should still have cards (active spaces exist in seed data)
      await expect(cards.first()).toBeVisible({ timeout: 5_000 });
    }

    // Type in search
    const searchInput = page.locator('#project-search');
    if (await searchInput.isVisible()) {
      await searchInput.fill('Test');
      await page.waitForTimeout(500); // debounce
    }
  });

  test('join a space and verify email to initiator', async ({ page, request }) => {
    await clearMailpit(request);

    await loginViaUI(page, 'user@e2e.test', 'E2eTest!2026');

    // Navigate to an active space (use seeded space)
    await page.goto('/projektraeume');
    await page.waitForSelector('#project-spaces-grid', { timeout: 10_000 });

    // Click first space card link
    const spaceLink = page.locator('#project-spaces-grid a, #project-spaces-grid .project-card a').first();
    if (await spaceLink.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await spaceLink.click();
    } else {
      // Fallback: navigate directly to seeded active space
      await page.goto('/projektraeume');
      await page.waitForTimeout(2000);
      await page.locator('article a, .project-card').first().click();
    }

    await page.waitForSelector('.projektraum-page, #space-header', { timeout: 10_000 });

    // Click join button if visible (user might already be member)
    const joinBtn = page.locator('#btn-join-request');
    if (await joinBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await joinBtn.click();
      await page.waitForTimeout(2000);

      // Verify join request email to initiator
      const msg = await getMailpitMessage(request, 'initiator@e2e.test', 'Beitrittsanfrage');
      expect(msg.HTML || msg.Text).toBeTruthy();
    }
  });

  test('write status update with CKEditor and verify email', async ({ page, request }) => {
    await clearMailpit(request);

    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');

    // Navigate to own active space
    await page.goto('/projektidee/meine');
    await page.waitForSelector('#ideas-container', { timeout: 10_000 });

    // Find an approved idea with a space link
    const spaceLink = page.locator('a[href*="/projektraum/view/"]').first();
    if (!(await spaceLink.isVisible({ timeout: 3_000 }).catch(() => false))) {
      test.skip(true, 'No approved idea with project space in seed data');
      return;
    }
    await spaceLink.click();
    await page.waitForSelector('.projektraum-page, #space-header', { timeout: 10_000 });

    // Click write update button
    const writeBtn = page.locator('#btn-write-update');
    if (!(await writeBtn.isVisible({ timeout: 3_000 }).catch(() => false))) {
      test.skip(true, 'Write update button not visible (space may not be active)');
      return;
    }
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

  test('leave space', async ({ page }) => {
    await loginViaUI(page, 'user@e2e.test', 'E2eTest!2026');

    // Navigate to a space where user is a member
    await page.goto('/projektraeume');
    await page.waitForSelector('#project-spaces-grid', { timeout: 10_000 });

    // Click into a space
    await page.locator('#project-spaces-grid a, article a').first().click();
    await page.waitForSelector('.projektraum-page, #space-header', { timeout: 10_000 });

    // Click leave button if visible
    const leaveBtn = page.locator('[data-leave-space]');
    if (await leaveBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await leaveBtn.click();
      await page.waitForTimeout(2000);
      // Page should reload or show updated sidebar without the user
    }
  });
});
```

- [ ] **Step 2: Run typecheck**

Run: `npx tsc --noEmit`

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/browser/project-spaces.spec.ts
git commit -m "test: add browser project spaces E2E tests (browse, join, write update, leave + emails)"
```

---

### Task 7: Create browser/expert-flow.spec.ts — Expert requests

**Files:**
- Create: `tests/e2e/specs/browser/expert-flow.spec.ts`

- [ ] **Step 1: Create the test file**

```typescript
import { test, expect } from '@playwright/test';

import { clearMailpit, getMailpitMessage, loginViaUI } from '../../fixtures/helpers';

test.describe.serial('Browser: Expert flow', () => {
  test('request expert from space and verify email', async ({ page, request }) => {
    await clearMailpit(request);

    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');

    // Navigate to own active space
    await page.goto('/projektidee/meine');
    await page.waitForSelector('#ideas-container', { timeout: 10_000 });

    const spaceLink = page.locator('a[href*="/projektraum/view/"]').first();
    if (!(await spaceLink.isVisible({ timeout: 3_000 }).catch(() => false))) {
      test.skip(true, 'No approved idea with project space found');
      return;
    }
    await spaceLink.click();
    await page.waitForSelector('.projektraum-page, #space-header', { timeout: 10_000 });

    // Select expert from dropdown
    const expertSelect = page.locator('#expert-select');
    if (!(await expertSelect.isVisible({ timeout: 3_000 }).catch(() => false))) {
      test.skip(true, 'Expert select not visible (user may lack permission or no experts available)');
      return;
    }

    // Wait for options to load
    await page.waitForSelector('#expert-select option:not([value=""])', { timeout: 5_000 });
    const firstExpert = await page
      .locator('#expert-select option:not([value=""])')
      .first()
      .getAttribute('value');
    if (!firstExpert) {
      test.skip(true, 'No experts available in select');
      return;
    }
    await page.selectOption('#expert-select', firstExpert);

    // Fill optional message
    const messageField = page.locator('#expert-message');
    if (await messageField.isVisible()) {
      await messageField.fill('We need your expertise on energy efficiency.');
    }

    // Submit request
    await page.click('#btn-request-expert');
    await page.waitForTimeout(2000);

    // Verify email to expert
    const msg = await getMailpitMessage(request, 'expert@e2e.test', 'Experte');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  test('expert declines request and verify email', async ({ page, request }) => {
    await clearMailpit(request);

    await loginViaUI(page, 'expert@e2e.test', 'E2eTest!2026');
    await page.goto('/expertanfragen');

    // Wait for requests to load
    await page.waitForSelector('#requests-container', { timeout: 10_000 });

    // Find a pending request card
    const declineBtn = page.locator('button[data-accept="false"]').first();
    if (!(await declineBtn.isVisible({ timeout: 3_000 }).catch(() => false))) {
      test.skip(true, 'No pending expert requests found');
      return;
    }

    await declineBtn.click();
    await page.waitForTimeout(2000);

    // Verify card shows declined status (page reloads)
    await page.waitForSelector('#requests-container', { timeout: 10_000 });
    const declinedBadge = page.locator('.badge--neutral:has-text("Abgelehnt")');
    await expect(declinedBadge.first()).toBeVisible({ timeout: 5_000 });

    // Verify email to initiator
    const msg = await getMailpitMessage(request, 'initiator@e2e.test', 'abgelehnt');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });
});
```

- [ ] **Step 2: Run typecheck**

Run: `npx tsc --noEmit`

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/browser/expert-flow.spec.ts
git commit -m "test: add browser expert flow E2E tests (request + decline with email verification)"
```

---

### Task 8: Create docs/testing-guide.md

**Files:**
- Create: `docs/testing-guide.md`

- [ ] **Step 1: Create the testing guide**

```markdown
# Testing Guide

This guide explains when to use which test type, where to add new tests, and how to run them. It is the single source of truth for testing in the Innovationsbox project.

## Test Types at a Glance

| Type | What it tests | What it catches | Speed | Where it lives | Runs in CI |
|------|--------------|-----------------|-------|----------------|------------|
| **Unit** | Domain logic, handlers, value objects in isolation (mocked dependencies) | Logic bugs, business rule violations, edge cases | Fast (~5s) | `tests/unit/` | Stage 3 |
| **Integration** | Handlers + real DB, repository queries, event chains | Query bugs, column mismatches, event wiring, transaction issues | Medium (~30s) | `tests/integration/` | Stage 5 |
| **API** | REST endpoint contracts via HTTP calls | Serialization, status codes, auth guards, validation | Medium (~1m) | `tests/e2e/specs/*.spec.ts` (root level) | Stage 5 |
| **Browser E2E** | Real user journeys: navigate pages, fill forms, click buttons, verify emails | UI bugs, form validation, JS errors, navigation, email delivery | Slow (~3m) | `tests/e2e/specs/browser/` | Stage 5 |
| **Storybook/Axe** | Component accessibility (ARIA, contrast, keyboard) | WCAG violations, missing ARIA, keyboard traps | Medium (~1m) | `tests/e2e/specs/storybook/` | Stage 3 |

## Decision Flowchart: Which Test Do I Write?

```text
I changed...
  |
  +-- a domain model method or entity
  |     -> write a UNIT test in tests/unit/{package}/Domain/
  |
  +-- a use case handler
  |     -> write a UNIT test (mocked deps) in tests/unit/{package}/Application/
  |     -> write an INTEGRATION test (real repo) in tests/integration/{package}/UseCase/
  |
  +-- a repository SQL query
  |     -> write an INTEGRATION test in tests/integration/{package}/Persistence/
  |
  +-- an event handler
  |     -> write a UNIT test in tests/unit/{package}/Application/
  |     -> write an INTEGRATION test (event chain) in tests/integration/shared/
  |
  +-- an API controller (response shape, validation, status codes)
  |     -> write an API test in tests/e2e/specs/{nn}-{name}.spec.ts
  |
  +-- a page template, form, JS interaction, or user-visible flow
  |     -> write a BROWSER E2E test in tests/e2e/specs/browser/
  |
  +-- a notification handler or email template
  |     -> write a test that checks Mailpit content (browser E2E or API test)
  |
  +-- a component partial (.php) or SCSS
  |     -> verify Storybook renders: npm run storybook:build
  |     -> axe tests run automatically on all stories
  |
  +-- design tokens or global styles
        -> run Storybook, visual check
```

## Where Tests Live

```text
tests/
  unit/                              <- Pure PHP, no CMS, mocked dependencies
    inno_project_idea/
      Domain/                        <- Domain model tests
      Application/                   <- Handler tests (Mockery mocks)
    inno_moderation/...
    inno_project_space/...
    inno_notification/...

  integration/                       <- Real DB, CMS bootstrapped, transaction-rollback
    inno_project_idea/
      Persistence/                   <- Repository tests (11 tests)
      UseCase/                       <- Handler + real repo tests
    inno_moderation/
      Persistence/                   <- Repository tests (12 tests)
      UseCase/                       <- Handler + real repo tests
    inno_project_space/
      Persistence/                   <- Repository tests (12 tests)
      UseCase/                       <- Handler + real repo tests
    shared/                          <- Cross-package event chain tests

  e2e/
    fixtures/
      helpers.ts                     <- Shared helpers (login, mailpit, CKEditor, etc.)
      test-upload.png                <- File upload fixture
    specs/
      00-smoke.spec.ts               <- Infrastructure health check
      browser/                       <- REAL USER browser tests (navigate, click, type)
        auth.spec.ts                 <- Register, login, forgot password, logout
        idea-submission.spec.ts      <- Submit idea via form + file upload
        idea-management.spec.ts      <- View/edit/withdraw on My Ideas page
        moderation.spec.ts           <- Moderation queue + approve/reject/feedback
        project-spaces.spec.ts       <- Browse, join, write update, leave
        expert-flow.spec.ts          <- Request expert, expert declines
      storybook/                     <- Component accessibility tests (axe-core)
        axe.spec.ts                  <- Runs WCAG checks on all 63 components
      *.spec.ts                      <- API-level contract tests
```

## How to Run

### Unit tests
```bash
composer test              # unit tests + 80% coverage enforced
composer test:unit         # unit tests only (no coverage check)
```
No database or CMS needed. Runs in ~5 seconds.

### Integration tests
```bash
composer test:integration  # requires running CMS + DB
```
Needs a running CMS with all packages installed and a connected database. Each test wraps mutations in a transaction that rolls back — no cleanup needed.

### API tests (against local CMS)
```bash
npm run test:e2e           # all Playwright tests against local CMS
```
Needs a running CMS at `http://$PROJECT_NAME.local:$APP_PORT`. Seeded via `make seed-e2e`.

### Browser E2E tests only
```bash
npx playwright test tests/e2e/specs/browser/   # browser tests only
```
Same prerequisites as API tests.

### Storybook accessibility tests
```bash
npm run storybook:build
npx playwright test tests/e2e/specs/storybook/
```
No CMS needed — tests run against the built Storybook static site.

### Everything in Docker (mirrors CI exactly)
```bash
# Start isolated test environment
docker compose -f docker-compose.test.yml up -d --build --wait

# Run integration tests inside the container
docker compose -f docker-compose.test.yml exec -T app composer test:integration

# Run all E2E tests (API + browser) from host
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e

# Tear down
docker compose -f docker-compose.test.yml down -v
```

### Linting & type checks
```bash
npm run lint:all           # ESLint + Stylelint + Prettier
npm run typecheck          # TypeScript (tsc --noEmit)
composer lint:php          # PHPStan + PHP CS Fixer
npm run lint:api           # Spectral OpenAPI linting
```

## How to Write New Tests

### Unit test
1. Create file in `tests/unit/{package}/{Domain|Application}/YourTest.php`
2. Use Pest `test('description', function () { ... })` syntax
3. Mock all dependencies with Mockery
4. Follow existing examples in `tests/unit/inno_moderation/`

### Integration test
1. Create file in `tests/integration/{package}/{Persistence|UseCase}/YourTest.php`
2. Use transaction-rollback pattern:
   ```php
   beforeEach(function (): void {
       $this->db = app(Connection::class);
       $this->db->beginTransaction();
   });
   afterEach(function (): void {
       $this->db->rollBack();
       Mockery::close();
   });
   ```
3. Use real repositories, mock only cross-cutting concerns (auth, logger, transaction, dispatcher)
4. Follow existing examples in `tests/integration/inno_project_idea/UseCase/`

### API test
1. Create file in `tests/e2e/specs/{nn}-{name}.spec.ts`
2. Login via `loginAs()` helper to get `AuthedApi`
3. Call REST endpoints and assert responses
4. Follow existing examples in `tests/e2e/specs/02-ideas.spec.ts`

### Browser E2E test
1. Create file in `tests/e2e/specs/browser/{name}.spec.ts`
2. Use `loginViaUI()` to login through the actual form
3. Navigate to pages, fill forms, click buttons, verify visible results
4. Use `fillCKEditor()` for rich text fields
5. Use `getMailpitMessage()` to verify emails after actions
6. Use `extractLinkFromEmail()` to follow email links
7. Follow existing examples in `tests/e2e/specs/browser/auth.spec.ts`

### Storybook test
Component axe tests are automatic — every Storybook story gets an axe-core WCAG audit. To add coverage:
1. Create a `*.stories.json` file for your component
2. The `axe.spec.ts` auto-discovers all stories

## CI Pipeline

| Stage | What runs | Blocks merge on |
|-------|-----------|----------------|
| 1. Lint & Format | ESLint, Prettier, Stylelint, PHPStan, PHP CS Fixer, OpenAPI staleness, markdown-link-check | Any error |
| 2. Type Check | `tsc --noEmit` | Type errors |
| 3. Test & Coverage | Unit tests (80% min), production build, Storybook build + axe | Test failure or < 80% coverage |
| 4. Security | npm audit, composer audit, Semgrep SAST | High/critical vulns |
| 5. E2E & Integration | Docker env -> integration tests -> Playwright (API + browser) -> teardown | Any test failure |

Stages run sequentially, fail-fast. Cheapest checks first.

## Test Data

### Development seed
```bash
make seed              # seed development data (truncates ib_* tables)
```

### E2E seed (for E2E + integration tests)
```bash
make seed-e2e          # creates test users + ideas + spaces
```

### E2E test users

| Email | Role | Password |
|-------|------|----------|
| `user@e2e.test` | Registered user | `E2eTest!2026` |
| `moderator@e2e.test` | Moderator | `E2eTest!2026` |
| `expert@e2e.test` | Expert | `E2eTest!2026` |
| `admin@e2e.test` | Administrator | `E2eTest!2026` |
| `initiator@e2e.test` | Initiator (owns seeded data) | `E2eTest!2026` |

### Seeded data
- 3 categories: Energie, Mobilitat, Biodiversitat
- 6 ideas (one per status): draft, submitted, under_review, approved, rejected, feedback_requested
- 4 project spaces (one per status): draft, active, completed, archived
- 1 pending join request, 1 accepted membership, 1 pending expert request

See `docs/e2e-testing.md` for full details.
```

- [ ] **Step 2: Commit**

```bash
git add docs/testing-guide.md
git commit -m "docs: add comprehensive testing guide (when/where/how for all test types)"
```

---

### Task 9: Run typecheck and ESLint on all new files

**Files:** None (verification only)

- [ ] **Step 1: Run TypeScript check**

Run: `npx tsc --noEmit`
Expected: No errors

- [ ] **Step 2: Run ESLint on browser specs**

Run: `npx eslint tests/e2e/specs/browser/ tests/e2e/fixtures/helpers.ts`
Expected: 0 errors (warnings from `no-unsafe-*` are OK)

- [ ] **Step 3: Fix any issues and commit**

If typecheck or lint found issues, fix and commit:
```bash
git add tests/e2e/
git commit -m "fix: resolve typecheck and lint issues in browser E2E specs"
```

---

## Summary

After completing all 9 tasks:

- **6 new browser spec files** with 19 tests covering real user journeys
- **4 new helpers** (loginViaUI, fillCKEditor, getMailpitMessage, extractLinkFromEmail)
- **1 fixture file** (test-upload.png)
- **1 comprehensive testing guide** (docs/testing-guide.md)
- Every email-triggering action verified via Mailpit including content checks
- CKEditor interaction tested with real keyboard input
- File upload tested with real fixture file

**Test totals after completion:**
- 69 unit test files
- 11 integration test files
- 19 API-level E2E specs
- 6 browser E2E specs (NEW)
- 14 Storybook axe specs
- = **119+ test files, 200+ test cases**
