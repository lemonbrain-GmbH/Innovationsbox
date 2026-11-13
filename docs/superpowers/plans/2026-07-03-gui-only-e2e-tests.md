# GUI-Only E2E Tests Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace all API-based E2E tests with GUI-only browser tests that interact through forms, buttons, and navigation — exactly as a real user would.

**Architecture:** 7 serial workflow spec files replace 18 API-based specs. Each spec uses `test.describe.serial()` so later tests build on earlier ones. Email verification uses Mailpit API (infrastructure exception) plus visual navigation to the Mailpit web UI. No direct application API calls. All test data setup happens through the GUI.

**Tech Stack:** Playwright, TypeScript, Mailpit API (email verification only)

## Global Constraints

- No direct application API calls (`api.post()`, `api.get()`, etc.) in any test
- Mailpit API calls are the only exception — they verify email delivery (infrastructure, not app)
- Every email verification must also navigate to `http://localhost:8025/message/{ID}` for visual feedback in Playwright UI
- All data setup must happen through the GUI (forms, buttons, navigation)
- Tests use `test.describe.serial()` — later tests depend on earlier ones within each spec
- All tests must pass with `E2E_DOCKER=true --project=e2e --workers=1`
- Existing GUI-only specs (`projektraeume.spec.ts`, `projektraum.spec.ts`, `expert-pool.spec.ts`, `profile-access.spec.ts`, `auth-flow.spec.ts`) stay as-is
- Storybook tests are not affected
- Test timeout: 120_000ms for serial specs in Docker

## Key GUI Selectors Reference

These selectors come from the existing TypeScript source and working browser tests:

**Idea submission** (`/projektidee/einreichen`): `#idea-form`, `#idea-title`, `#idea-category`, `#idea-description` (CKEditor), `#idea-files`, `#idea-submit`, redirect to `/projektidee/meine`

**My ideas** (`/projektidee/meine`): `#ideas-container`, `.idea-card` or `[data-id]`, `[data-edit-idea]`, `[data-withdraw-idea]`, `#edit-idea-modal`, `#edit-idea-title`, `#edit-idea-category`, `#edit-idea-submit`, `#withdraw-modal`, `#withdraw-confirm`

**Moderation queue** (`/moderation`): `#reviews-container`, `a` links to detail pages

**Moderation detail** (`/moderation/detail?id=`): `#review-container`, `#btn-approve`, `#btn-reject`, `#btn-feedback`, `#reject-modal`, `#reject-reason`, `#reject-confirm`, `#feedback-notes`, `#feedback-deadline`

**Category management** (`/moderation/kategorien`): `#categories-table`, `#btn-add-category`, `#category-modal`, `#category-name`, `#category-slug`, `#category-sort`, `#category-save`, `[data-edit-category]`, `[data-delete-category]`, `#delete-modal`, `#delete-confirm`

**User management** (`/moderation/benutzer`): `#users-container`, `#user-search`, `[data-toggle-expert]`

**Project spaces list** (`/projektraeume`): `#project-spaces-grid`, `.project-card`, `#project-search`, `[data-value="active"]`

**Project space detail** (`/projektraum/view/{id}`): `#space-header`, `#space-title`, `#btn-join-request`, `#btn-write-update`, `#update-title`, `#update-body` (CKEditor), `#update-submit`, `[data-leave-space]`, `[data-remove-member]`, `#expert-select`, `#expert-message`, `#btn-request-expert`, `#btn-add-collaboard`, `#collaboard-title`, `#collaboard-url`, `#collaboard-save`, `[data-delete-collaboard]`, `[data-accept-request]`, `[data-reject-request]`, `#btn-activate`, `#btn-complete`, status change confirmation modals

**Expert requests** (`/expertanfragen`): `#requests-container`, `button[data-accept="true"]`, `button[data-accept="false"]`

**Expert pool** (`/members/directory`): `.member-card`, `h1` with "Expertinnen-Pool"

**Login** (`/login`): `input[name="uName"]`, `input[name="uPassword"]`, `button[type="submit"]`

**Register** (`/register`): `input[name="uEmail"]`, `input[name="uName"]`, `input[name="uPassword"]`, `input[name="uPasswordConfirm"]`, `button[type="submit"]`

---

### Task 1: Refactor helpers.ts — Remove API helpers, add showEmailInMailpit

**Files:**
- Modify: `tests/e2e/fixtures/helpers.ts`

**Interfaces:**
- Produces: `showEmailInMailpit(page, request, to, subjectContains, timeoutMs?) → Promise<{ID, Subject, HTML, Text}>` — used by all subsequent tasks
- Produces: `loginViaUI(page, email, password) → Promise<void>` — unchanged, used by all tasks
- Produces: `fillCKEditor(page, textareaId, text) → Promise<void>` — unchanged
- Produces: `clearMailpit(request) → Promise<void>` — unchanged
- Produces: `getMailpitMessage(request, to, subject, timeout?) → Promise<{ID, Subject, HTML, Text}>` — unchanged
- Produces: `extractLinkFromEmail(html, pattern) → string` — unchanged
- Removes: `AuthedApi` class, `loginAs()`, `submitIdea()`, `seedIdea()`, `getPendingReviewForIdea()`, `approveReview()`, `clearEmailLogs()`, `getEmailLogs()`, `emailLogsContain()`, `registerUser()` (returns AuthedApi — will be replaced by a void version)

- [ ] **Step 1: Rewrite helpers.ts**

Remove all API-related code. Keep browser and Mailpit helpers. Add `showEmailInMailpit()`. Replace `registerUser()` with a version that doesn't return `AuthedApi`.

```typescript
import { APIRequestContext, Page, BrowserContext } from '@playwright/test';

// --- Browser login helpers ---

/**
 * Login via the browser UI form at /login.
 */
export async function loginViaUI(page: Page, email: string, password: string): Promise<void> {
  await page.goto('/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="uName"]', email);
  await page.fill('input[name="uPassword"]', password);
  await page.click('button[type="submit"]');
  const isDocker = process.env.E2E_DOCKER === 'true';
  await page.waitForURL((url) => {
    const path = url.pathname;
    return !path.startsWith('/login') && !path.includes('/login/');
  }, { timeout: isDocker ? 60_000 : 10_000, waitUntil: 'domcontentloaded' });
}

/**
 * Login as a specific user in a new browser context.
 * Useful for multi-user tests where you need separate sessions.
 */
export async function loginAsNewContext(
  browser: { newContext: (opts?: object) => Promise<BrowserContext> },
  email: string,
  password: string,
): Promise<{ page: Page; context: BrowserContext }> {
  const context = await browser.newContext();
  const page = await context.newPage();
  await loginViaUI(page, email, password);
  return { page, context };
}

/**
 * Register a new user via the browser registration form.
 */
export async function registerUser(
  page: Page,
  email: string,
  username: string,
  password: string,
): Promise<void> {
  await page.goto('/register');
  await page.fill('input[name="uEmail"]', email);
  await page.fill('input[name="uName"]', username);
  await page.fill('input[name="uPassword"]', password);
  await page.fill('input[name="uPasswordConfirm"]', password);
  await page.click('button[type="submit"]');
  await page.waitForURL((url) => {
    const path = url.pathname;
    return !path.endsWith('/register') && (path.includes('/register/') || path.includes('/account') || path === '/' || path.includes('/index.php'));
  }, { timeout: 10_000 });
}

// --- CKEditor helpers ---

/**
 * Type text into a CKEditor 4 instance by its textarea ID.
 */
export async function fillCKEditor(page: Page, textareaId: string, text: string): Promise<void> {
  const editorSelector = `#cke_${textareaId} .cke_editable, #cke_${textareaId} iframe`;
  await page.waitForSelector(editorSelector, { timeout: 10_000 });

  const iframe = page.locator(`#cke_${textareaId} iframe`);
  if (await iframe.count() > 0) {
    const frame = page.frameLocator(`#cke_${textareaId} iframe`);
    await frame.locator('body').click();
    await frame.locator('body').fill(text);
    return;
  }

  const editable = page.locator(`#cke_${textareaId} .cke_editable`);
  await editable.click();
  await page.keyboard.type(text);
}

// --- Mailpit API helpers (email verification infrastructure) ---

const MAILPIT_API = process.env.E2E_DOCKER === 'true'
  ? 'http://localhost:8025/api/v1'
  : null;

const MAILPIT_UI = 'http://localhost:8025';

export interface MailpitMessage {
  ID: string;
  From: { Name: string; Address: string };
  To: Array<{ Name: string; Address: string }>;
  Subject: string;
  Snippet: string;
  Created: string;
}

/**
 * Search Mailpit for messages matching a query.
 */
export async function searchEmails(
  request: APIRequestContext,
  query: string,
  limit = 50,
): Promise<MailpitMessage[]> {
  if (!MAILPIT_API) throw new Error('Mailpit helpers require E2E_DOCKER=true');
  const res = await request.get(
    `${MAILPIT_API}/search?query=${encodeURIComponent(query)}&limit=${String(limit)}`,
  );
  if (!res.ok()) throw new Error(`Mailpit search failed: ${String(res.status())}`);
  const body = (await res.json()) as { messages?: MailpitMessage[] };
  return body.messages ?? [];
}

/**
 * Wait for an email matching the query to arrive in Mailpit.
 */
export async function waitForEmail(
  request: APIRequestContext,
  query: string,
  timeoutMs = 10_000,
): Promise<MailpitMessage> {
  const start = Date.now();
  while (Date.now() - start < timeoutMs) {
    const messages = await searchEmails(request, query);
    if (messages.length > 0) return messages[0];
    await new Promise((r) => setTimeout(r, 500));
  }
  throw new Error(`Timed out waiting for email matching: ${query}`);
}

/**
 * Delete all messages in Mailpit.
 */
export async function clearMailpit(request: APIRequestContext): Promise<void> {
  if (!MAILPIT_API) throw new Error('Mailpit helpers require E2E_DOCKER=true');
  await request.delete(`${MAILPIT_API}/messages`);
}

/**
 * Get a full email message from Mailpit, including HTML body.
 */
export async function getMailpitMessage(
  request: APIRequestContext,
  to: string,
  subjectContains: string,
  timeoutMs = 15_000,
): Promise<{ ID: string; Subject: string; HTML: string; Text: string }> {
  if (!MAILPIT_API) throw new Error('Mailpit helpers require E2E_DOCKER=true');
  const query = to ? `to:${to} subject:"${subjectContains}"` : `subject:"${subjectContains}"`;
  const msg = await waitForEmail(request, query, timeoutMs);
  const res = await request.get(`${MAILPIT_API}/message/${msg.ID}`);
  if (!res.ok()) throw new Error(`Failed to fetch message ${msg.ID}: ${String(res.status())}`);
  return (await res.json()) as { ID: string; Subject: string; HTML: string; Text: string };
}

/**
 * Verify an email was sent and show it in the Mailpit web UI.
 * Returns the full message for assertions. The page navigates to Mailpit,
 * making the email visible in Playwright UI traces and screenshots.
 */
export async function showEmailInMailpit(
  page: Page,
  request: APIRequestContext,
  to: string,
  subjectContains: string,
  timeoutMs?: number,
): Promise<{ ID: string; Subject: string; HTML: string; Text: string }> {
  const timeout = timeoutMs ?? (process.env.E2E_DOCKER === 'true' ? 20_000 : 15_000);
  const msg = await getMailpitMessage(request, to, subjectContains, timeout);
  await page.goto(`${MAILPIT_UI}/message/${msg.ID}`, { waitUntil: 'domcontentloaded' });
  return msg;
}

/**
 * Extract a URL from email HTML body matching the given regex pattern.
 */
export function extractLinkFromEmail(html: string, pattern: RegExp): string {
  const hrefPattern = /href="([^"]+)"/g;
  let match: RegExpExecArray | null;
  while ((match = hrefPattern.exec(html)) !== null) {
    if (pattern.test(match[1])) {
      return rewriteUrlForDocker(match[1].replace(/&amp;/g, '&'));
    }
  }
  const textMatch = pattern.exec(html);
  if (textMatch) return rewriteUrlForDocker(textMatch[0].replace(/&amp;/g, '&'));
  throw new Error(`No link matching ${String(pattern)} found in email body`);
}

function rewriteUrlForDocker(url: string): string {
  if (process.env.E2E_DOCKER !== 'true') return url;
  return url.replace(/^https?:\/\/[^/]+/, 'http://localhost:8880');
}
```

- [ ] **Step 2: Verify no tests import removed symbols**

Run: `grep -r "AuthedApi\|loginAs\|submitIdea\|seedIdea\|getPendingReviewForIdea\|approveReview\|clearEmailLogs\|getEmailLogs\|emailLogsContain" tests/e2e/specs/ --include="*.ts" -l`

These files will be deleted in later tasks. Verify only the files scheduled for deletion reference the removed helpers. The kept specs (`projektraeume.spec.ts`, `projektraum.spec.ts`, etc.) should not import any removed symbols.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/fixtures/helpers.ts
git commit -m "refactor: remove API helpers from E2E fixtures, add showEmailInMailpit"
```

---

### Task 2: Rewrite 00-smoke.spec.ts — GUI-only smoke tests

**Files:**
- Modify: `tests/e2e/specs/00-smoke.spec.ts`

**Interfaces:**
- Consumes: `loginViaUI(page, email, password)` from helpers
- Produces: Confidence that the environment is healthy before workflow tests run

- [ ] **Step 1: Rewrite the smoke spec**

Replace the current spec (which uses `loginAs` and API calls) with GUI-only checks.

```typescript
import { test, expect } from '@playwright/test';

import { loginViaUI } from '../fixtures/helpers';

test.describe('Smoke: Docker environment health', () => {
  test('homepage loads with themed content', async ({ page }) => {
    await page.goto('/');
    expect(page.url()).not.toContain('/install');
    await expect(page.locator('body')).toBeVisible();
    // Innovationsbox theme should be active
    await expect(page.locator('header')).toBeVisible();
  });

  test('login page renders with innovationsbox theme', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('input[name="uName"]')).toBeVisible();
    await expect(page.locator('input[name="uPassword"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  const testUsers = [
    { email: 'user@e2e.test', label: 'registered user' },
    { email: 'moderator@e2e.test', label: 'moderator' },
    { email: 'expert@e2e.test', label: 'expert' },
    { email: 'initiator@e2e.test', label: 'initiator' },
  ];

  for (const { email, label } of testUsers) {
    test(`login as ${label} (${email})`, async ({ page }) => {
      await loginViaUI(page, email, 'E2eTest!2026');
      // After login, should be on a non-login page
      expect(page.url()).not.toContain('/login');
    });
  }

  test('idea submission form loads for logged-in user', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto('/projektidee/einreichen');
    await expect(page.locator('#idea-form, form')).toBeVisible({ timeout: 10_000 });
    await expect(page.locator('#idea-title')).toBeVisible();
  });

  test('moderation queue loads for moderator', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 15_000 });
  });

  test('project spaces page loads with cards', async ({ page }) => {
    await page.goto('/projektraeume');
    await page.waitForSelector('#project-spaces-grid', { timeout: 10_000 });
    const cards = page.locator('#project-spaces-grid .project-card, #project-spaces-grid article');
    await expect(cards.first()).toBeVisible({ timeout: 5_000 });
  });
});
```

- [ ] **Step 2: Run smoke tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1 tests/e2e/specs/00-smoke.spec.ts
```

Expected: All tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/00-smoke.spec.ts
git commit -m "refactor: rewrite smoke tests as GUI-only"
```

---

### Task 3: Rewrite 01-auth.spec.ts — Full auth lifecycle via GUI

**Files:**
- Modify: `tests/e2e/specs/01-auth.spec.ts`

**Interfaces:**
- Consumes: `loginViaUI`, `registerUser`, `clearMailpit`, `showEmailInMailpit`, `extractLinkFromEmail` from helpers

- [ ] **Step 1: Rewrite the auth spec**

Merge the current `01-auth.spec.ts` and `browser/auth.spec.ts` into one serial spec that covers the full auth lifecycle through the GUI: register → validate email → login → profile completion → forgot password → logout.

```typescript
import { test, expect } from '@playwright/test';

import {
  loginViaUI,
  registerUser,
  clearMailpit,
  showEmailInMailpit,
  extractLinkFromEmail,
} from '../fixtures/helpers';

test.describe.serial('Auth lifecycle', () => {
  test.setTimeout(120_000);

  const ts = Date.now();
  const regEmail = `e2e.auth.${String(ts)}@test.local`;
  const regUsername = `e2e_auth_${String(ts)}`;
  const regPassword = 'E2eTest!2026';

  test('register a new account', async ({ page, request }) => {
    await clearMailpit(request);
    await registerUser(page, regEmail, regUsername, regPassword);
    // Should be on a success/validation page
    expect(page.url()).not.toMatch(/\/register$/);
  });

  test('validate email via link in Mailpit', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, regEmail, '', 30_000);
    expect(msg.Subject).toBeTruthy();

    // If email contains a validation link, visit it
    const emailBody = msg.HTML || msg.Text;
    try {
      const validateLink = extractLinkFromEmail(emailBody, /https?:\/\/[^\s"]*validate[^\s"]*/i);
      await page.goto(validateLink);
      await expect(page.locator('body')).toContainText(/validated|bestätigt|verified|approved/i, { timeout: 5_000 });
    } catch {
      // No validation link — registration was auto-approved
    }
  });

  test('login with valid credentials', async ({ page }) => {
    await loginViaUI(page, regEmail, regPassword);
    expect(page.url()).not.toContain('/login');
  });

  test('login with invalid credentials shows error', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="uName"]', regEmail);
    await page.fill('input[name="uPassword"]', 'WrongPassword!');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    const errorVisible = await page
      .locator('.alert--error, .alert--danger, .ccm-system-errors, [role="alert"]')
      .first()
      .isVisible();
    expect(errorVisible).toBeTruthy();
  });

  test('profile completion enforced for new user without display_name', async ({ page }) => {
    // Register a fresh user without display_name
    const freshEmail = `e2e.nodn.${String(Date.now())}@test.local`;
    const freshUser = `e2e_nodn_${String(Date.now())}`;
    await registerUser(page, freshEmail, freshUser, regPassword);

    // Login as the fresh user
    await loginViaUI(page, freshEmail, regPassword);

    // Navigate to a page — should be redirected to profile completion
    await page.goto('/projektraeume', { waitUntil: 'domcontentloaded' });
    await page.waitForURL(/edit_profile|complete/, { timeout: 10_000 });
  });

  test('forgot password form loads', async ({ page }) => {
    await page.goto('/login');
    await page.click('a[href*="forgot_password"]');
    await expect(page.locator('input[name="uEmail"]')).toBeVisible({ timeout: 5_000 });
  });

  test('forgot password full flow', async ({ page, request }) => {
    await clearMailpit(request);

    await page.goto('/login');
    await page.click('a[href*="forgot_password"]');
    await expect(page.locator('input[name="uEmail"]')).toBeVisible({ timeout: 5_000 });

    await page.fill('input[name="uEmail"]', regEmail);
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    // Show password reset email in Mailpit
    const msg = await showEmailInMailpit(page, request, regEmail, 'Password', 20_000);
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

    // Verify by logging in with new password
    await loginViaUI(page, regEmail, newPassword);
    expect(page.url()).not.toContain('/login');
  });

  test('logout', async ({ page }) => {
    await loginViaUI(page, 'user@e2e.test', 'E2eTest!2026');
    await page.goto('/login/do_logout');
    await page.waitForLoadState('load', { timeout: 10_000 });
    await expect(page.locator('[data-user-menu-trigger]')).toHaveCount(0, { timeout: 5_000 });
  });
});
```

- [ ] **Step 2: Run auth tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1 tests/e2e/specs/01-auth.spec.ts
```

Expected: All tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/01-auth.spec.ts
git commit -m "refactor: rewrite auth tests as GUI-only serial lifecycle"
```

---

### Task 4: Create 02-idea-to-space.spec.ts — Submit idea → approve → project space

**Files:**
- Create: `tests/e2e/specs/02-idea-to-space.spec.ts`

**Interfaces:**
- Consumes: `loginViaUI`, `loginAsNewContext`, `fillCKEditor`, `clearMailpit`, `showEmailInMailpit` from helpers
- Produces: A submitted and approved idea visible in project spaces list (used conceptually by later tests to confirm the full flow works)

- [ ] **Step 1: Create the idea-to-space spec**

```typescript
import path from 'path';

import { test, expect } from '@playwright/test';

import {
  loginViaUI,
  loginAsNewContext,
  fillCKEditor,
  clearMailpit,
  showEmailInMailpit,
} from '../fixtures/helpers';

test.describe.serial('Idea to Project Space', () => {
  test.setTimeout(120_000);

  const ideaTitle = `GUI E2E Idea ${String(Date.now())}`;

  test('initiator submits idea via form', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');

    await page.goto('/projektidee/einreichen');
    await expect(page.locator('#idea-form, form')).toBeVisible({ timeout: 10_000 });

    // Fill title
    await page.fill('#idea-title', ideaTitle);

    // Select category — wait for options to populate
    await page.waitForFunction(
      () => {
        const sel = document.getElementById('idea-category') as HTMLSelectElement | null;
        return sel && sel.options.length > 1;
      },
      { timeout: 10_000 },
    );
    const firstOption = await page.locator('#idea-category option:not([value=""])').first().getAttribute('value');
    if (firstOption) await page.selectOption('#idea-category', firstOption);

    // Fill CKEditor description
    await fillCKEditor(page, 'idea-description', 'A project idea submitted entirely through the browser GUI.');

    // Submit
    await page.click('#idea-submit');
    await page.waitForURL(/\/projektidee\/meine/, { timeout: 30_000 });

    // Verify idea appears in my ideas list
    await expect(page.locator('#ideas-container')).toContainText(ideaTitle, { timeout: 15_000 });
  });

  test('submission email sent to moderator', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'moderator@e2e.test', ideaTitle);
    expect(msg.Subject).toContain(ideaTitle);
  });

  test('idea appears in my ideas with submitted badge', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto('/projektidee/meine');
    await page.waitForSelector('#ideas-container', { timeout: 10_000 });
    await expect(page.locator('#ideas-container')).toContainText(ideaTitle, { timeout: 5_000 });
  });

  test('moderator sees idea in moderation queue', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });
    await expect(page.locator('#reviews-container')).toContainText(ideaTitle, { timeout: 5_000 });
  });

  test('moderator approves idea via UI', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });

    // Click the review link containing our idea title
    await page.locator(`#reviews-container a:has-text("${ideaTitle}")`).first().click();
    await page.waitForSelector('#btn-approve', { timeout: 10_000 });

    // Click approve
    await page.click('#btn-approve');
    await page.waitForURL(/\/moderation/, { timeout: 10_000 });
  });

  test('approval email sent to initiator', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'freigegeben');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  test('approved idea shows in my ideas with link to project space', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto('/projektidee/meine');
    await page.waitForSelector('#ideas-container', { timeout: 10_000 });

    // Find the approved idea card and verify it has the project space link
    const ideaCard = page.locator(`#ideas-container :has-text("${ideaTitle}")`).first();
    await expect(ideaCard).toBeVisible({ timeout: 5_000 });
    const spaceLink = ideaCard.locator('a[href*="/projektraum/"]');
    await expect(spaceLink).toBeVisible({ timeout: 5_000 });
  });

  test('project space appears in spaces list', async ({ page }) => {
    await page.goto('/projektraeume');
    await page.waitForSelector('#project-spaces-grid', { timeout: 10_000 });
    await expect(page.locator('#project-spaces-grid')).toContainText(ideaTitle, { timeout: 5_000 });
  });
});
```

- [ ] **Step 2: Run idea-to-space tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1 tests/e2e/specs/02-idea-to-space.spec.ts
```

Expected: All tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/02-idea-to-space.spec.ts
git commit -m "feat: add GUI-only idea-to-space E2E workflow"
```

---

### Task 5: Create 03-moderation-feedback.spec.ts — Feedback → resubmit → approve

**Files:**
- Create: `tests/e2e/specs/03-moderation-feedback.spec.ts`

**Interfaces:**
- Consumes: `loginViaUI`, `fillCKEditor`, `clearMailpit`, `showEmailInMailpit` from helpers

- [ ] **Step 1: Create the moderation feedback spec**

```typescript
import { test, expect } from '@playwright/test';

import {
  loginViaUI,
  fillCKEditor,
  clearMailpit,
  showEmailInMailpit,
} from '../fixtures/helpers';

test.describe.serial('Moderation feedback flow', () => {
  test.setTimeout(120_000);

  const ideaTitle = `Feedback Flow ${String(Date.now())}`;
  const feedbackNotes = 'Please add a cost estimation and timeline.';

  test('initiator submits idea', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');

    await page.goto('/projektidee/einreichen');
    await expect(page.locator('#idea-form, form')).toBeVisible({ timeout: 10_000 });

    await page.fill('#idea-title', ideaTitle);
    await page.waitForFunction(
      () => {
        const sel = document.getElementById('idea-category') as HTMLSelectElement | null;
        return sel && sel.options.length > 1;
      },
      { timeout: 10_000 },
    );
    const firstOption = await page.locator('#idea-category option:not([value=""])').first().getAttribute('value');
    if (firstOption) await page.selectOption('#idea-category', firstOption);

    await fillCKEditor(page, 'idea-description', 'An idea that will receive moderation feedback.');
    await page.click('#idea-submit');
    await page.waitForURL(/\/projektidee\/meine/, { timeout: 30_000 });
    await expect(page.locator('#ideas-container')).toContainText(ideaTitle, { timeout: 15_000 });
  });

  test('moderator requests feedback via UI', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });

    await page.locator(`#reviews-container a:has-text("${ideaTitle}")`).first().click();
    await page.waitForSelector('#btn-feedback', { timeout: 10_000 });

    await page.fill('#feedback-notes', feedbackNotes);
    await page.click('#btn-feedback');
    await page.waitForURL(/\/moderation/, { timeout: 10_000 });
  });

  test('feedback email sent to initiator', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'Rückmeldung');
    expect(msg.HTML || msg.Text).toContain(feedbackNotes);
  });

  test('initiator sees feedback on their idea and edits it', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto('/projektidee/meine');
    await page.waitForSelector('#ideas-container', { timeout: 10_000 });

    // The idea should show feedback_requested status with the feedback notes
    await expect(page.locator('#ideas-container')).toContainText(ideaTitle, { timeout: 5_000 });

    // Click edit button on the idea
    const editBtn = page.locator(`[data-edit-idea]`).first();
    await expect(editBtn).toBeVisible({ timeout: 5_000 });
    await editBtn.click();

    // Edit modal should appear
    await expect(page.locator('#edit-idea-modal')).toBeVisible({ timeout: 5_000 });

    // Update the title to indicate it was edited
    await page.fill('#edit-idea-title', `${ideaTitle} (updated)`);

    // Submit the edit (this resubmits the idea)
    await page.click('#edit-idea-submit');
    await page.waitForTimeout(3000);
  });

  test('moderator approves resubmitted idea', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });

    // The resubmitted idea should appear in the queue
    const reviewLink = page.locator('#reviews-container a').first();
    await expect(reviewLink).toBeVisible({ timeout: 5_000 });
    await reviewLink.click();
    await page.waitForSelector('#btn-approve', { timeout: 10_000 });

    await page.click('#btn-approve');
    await page.waitForURL(/\/moderation/, { timeout: 10_000 });
  });

  test('approval email sent after resubmission', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'freigegeben');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });
});
```

- [ ] **Step 2: Run feedback flow tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1 tests/e2e/specs/03-moderation-feedback.spec.ts
```

Expected: All tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/03-moderation-feedback.spec.ts
git commit -m "feat: add GUI-only moderation feedback E2E workflow"
```

---

### Task 6: Create 04-project-space.spec.ts — Join, updates, collaboard, leave

**Files:**
- Create: `tests/e2e/specs/04-project-space.spec.ts`

**Interfaces:**
- Consumes: `loginViaUI`, `loginAsNewContext`, `fillCKEditor`, `clearMailpit`, `showEmailInMailpit` from helpers

- [ ] **Step 1: Create the project space spec**

This spec uses the seeded active space (`e2e_space_active`) for testing space interactions. The `user@e2e.test` is already a member of this space (seeded), so we use `expert@e2e.test` (not a member) for join request tests.

```typescript
import { test, expect } from '@playwright/test';

import {
  loginViaUI,
  loginAsNewContext,
  fillCKEditor,
  clearMailpit,
  showEmailInMailpit,
} from '../fixtures/helpers';

test.describe.serial('Project Space collaboration', () => {
  test.setTimeout(120_000);

  const SPACE_ID = 'e2e_space_active';
  const SPACE_URL = `/projektraum/view/${SPACE_ID}`;

  // --- Join request flow ---

  test('user requests to join space via button', async ({ page, request }) => {
    await clearMailpit(request);

    // Login as expert (not a member of the active space)
    await loginViaUI(page, 'expert@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    // Click join button
    const joinBtn = page.locator('#btn-join-request');
    await expect(joinBtn).toBeVisible({ timeout: 5_000 });
    await joinBtn.click();
    await page.waitForTimeout(2000);
  });

  test('join request email sent to initiator', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'Beitrittsanfrage');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  test('initiator accepts join request via sidebar', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    // Find accept button in join requests section
    const acceptBtn = page.locator('[data-accept-request]').first();
    await expect(acceptBtn).toBeVisible({ timeout: 10_000 });
    await acceptBtn.click();
    await page.waitForTimeout(2000);
  });

  test('join accepted email sent to requester', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'expert@e2e.test', 'angenommen');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  // --- Status update CRUD ---

  test('initiator writes status update', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-title', { timeout: 10_000 });

    const writeBtn = page.locator('#btn-write-update');
    await expect(writeBtn).toBeVisible({ timeout: 10_000 });
    await writeBtn.click();

    await expect(page.locator('#update-title')).toBeVisible({ timeout: 5_000 });
    const updateTitle = `GUI Update ${String(Date.now())}`;
    await page.fill('#update-title', updateTitle);

    await page.waitForTimeout(1000);
    await fillCKEditor(page, 'update-body', 'Status update written through the browser.');

    // Wait for CKEditor content
    await page.waitForFunction(
      () => {
        const cke = (window as any).CKEDITOR;
        if (!cke) return false;
        const inst = cke.instances?.['update-body'];
        return inst && inst.getData().length > 10;
      },
      { timeout: 5_000 },
    );

    const [saveResponse] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes('/status-update') && resp.request().method() === 'POST',
        { timeout: 15_000 },
      ),
      page.click('#update-submit'),
    ]);
    expect(saveResponse.ok()).toBeTruthy();
  });

  test('status update email sent to members', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, '', 'Status');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  // --- Collaboard CRUD ---

  test('initiator adds collaboard link', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    const addBtn = page.locator('#btn-add-collaboard');
    await expect(addBtn).toBeVisible({ timeout: 10_000 });
    await addBtn.click();

    await expect(page.locator('#collaboard-title')).toBeVisible({ timeout: 5_000 });
    await page.fill('#collaboard-title', 'E2E Test Board');
    await page.fill('#collaboard-url', 'https://web.collaboard.app/board/e2e-test');
    await page.click('#collaboard-save');
    await page.waitForTimeout(2000);

    // Verify collaboard appears on page
    await page.reload();
    await page.waitForSelector('#space-header', { timeout: 10_000 });
    await expect(page.locator('body')).toContainText('E2E Test Board', { timeout: 5_000 });
  });

  test('initiator removes collaboard link', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    const deleteBtn = page.locator('[data-delete-collaboard]').first();
    if (await deleteBtn.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await deleteBtn.click();
      await page.waitForTimeout(2000);
      await page.reload();
      await page.waitForSelector('#space-header', { timeout: 10_000 });
    }
  });

  // --- Member leave ---

  test('expert leaves space via button', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'expert@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    const leaveBtn = page.locator('[data-leave-space]');
    await expect(leaveBtn).toBeVisible({ timeout: 5_000 });
    await leaveBtn.click();

    // Confirm leave dialog
    const confirmBtn = page.locator('#confirm-leave, .modal button.btn--danger, button:has-text("Verlassen")').first();
    if (await confirmBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await confirmBtn.click();
    }
    await page.waitForTimeout(3000);
  });

  test('member left email sent to initiator', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'verlassen');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });
});
```

- [ ] **Step 2: Run project space tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1 tests/e2e/specs/04-project-space.spec.ts
```

Expected: All tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/04-project-space.spec.ts
git commit -m "feat: add GUI-only project space E2E workflow"
```

---

### Task 7: Create 05-expert-flow.spec.ts — Request, accept, decline experts

**Files:**
- Create: `tests/e2e/specs/05-expert-flow.spec.ts`

**Interfaces:**
- Consumes: `loginViaUI`, `clearMailpit`, `showEmailInMailpit` from helpers

- [ ] **Step 1: Create the expert flow spec**

```typescript
import { test, expect } from '@playwright/test';

import {
  loginViaUI,
  clearMailpit,
  showEmailInMailpit,
} from '../fixtures/helpers';

test.describe.serial('Expert request flow', () => {
  test.setTimeout(120_000);

  const SPACE_URL = '/projektraum/view/e2e_space_active';

  test('initiator requests expert from space sidebar', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    const expertSelect = page.locator('#expert-select');
    await expect(expertSelect).toBeVisible({ timeout: 10_000 });

    // Wait for expert options to load
    const hasExperts = await page.waitForFunction(
      () => {
        const sel = document.getElementById('expert-select') as HTMLSelectElement | null;
        return sel && sel.options.length > 1;
      },
      { timeout: 10_000 },
    ).then(() => true).catch(() => false);
    test.skip(!hasExperts, 'No experts available');

    const firstExpert = await page
      .locator('#expert-select option:not([value=""])')
      .first()
      .getAttribute('value');
    expect(firstExpert).toBeTruthy();
    await page.selectOption('#expert-select', firstExpert!);

    const messageField = page.locator('#expert-message');
    if (await messageField.isVisible()) {
      await messageField.fill('We need your energy expertise for this project.');
    }

    await page.click('#btn-request-expert');
    await page.waitForTimeout(2000);
  });

  test('expert request email sent', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, '', 'Expertise', 30_000);
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  test('expert declines request via expert requests page', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'expert2@e2e.test', 'E2eTest!2026');
    await page.goto('/expertanfragen');
    await page.waitForSelector('#requests-container', { timeout: 15_000 });

    const declineBtn = page.locator('button[data-accept="false"]').first();
    const hasPending = await declineBtn.isVisible({ timeout: 10_000 }).catch(() => false);
    test.skip(!hasPending, 'No pending expert requests to decline');

    await declineBtn.click();
    await page.waitForTimeout(3000);

    // Verify UI updated
    await page.reload();
    await page.waitForSelector('#requests-container', { timeout: 15_000 });
  });

  test('decline email sent to initiator', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'abgelehnt');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });
});
```

- [ ] **Step 2: Run expert flow tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1 tests/e2e/specs/05-expert-flow.spec.ts
```

Expected: All tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/05-expert-flow.spec.ts
git commit -m "feat: add GUI-only expert flow E2E workflow"
```

---

### Task 8: Create 06-moderation-admin.spec.ts — Categories, users, reject

**Files:**
- Create: `tests/e2e/specs/06-moderation-admin.spec.ts`

**Interfaces:**
- Consumes: `loginViaUI`, `fillCKEditor`, `clearMailpit`, `showEmailInMailpit` from helpers

- [ ] **Step 1: Create the moderation admin spec**

```typescript
import { test, expect } from '@playwright/test';

import {
  loginViaUI,
  fillCKEditor,
  clearMailpit,
  showEmailInMailpit,
} from '../fixtures/helpers';

test.describe.serial('Moderation admin', () => {
  test.setTimeout(120_000);

  // --- Category CRUD ---

  test('moderator creates a category', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation/kategorien');
    await page.waitForSelector('#categories-table', { timeout: 10_000 });

    await page.click('#btn-add-category');
    await expect(page.locator('#category-modal')).toBeVisible({ timeout: 5_000 });

    await page.fill('#category-name', 'E2E Test Kategorie');
    await page.click('#category-save');
    await page.waitForTimeout(2000);

    // Verify category appears in table
    await page.reload();
    await page.waitForSelector('#categories-table', { timeout: 10_000 });
    await expect(page.locator('#categories-table')).toContainText('E2E Test Kategorie', { timeout: 5_000 });
  });

  test('moderator edits category', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation/kategorien');
    await page.waitForSelector('#categories-table', { timeout: 10_000 });

    // Find and click edit button for our category
    const row = page.locator('#categories-table tr:has-text("E2E Test Kategorie"), #categories-table [data-name="E2E Test Kategorie"]').first();
    const editBtn = row.locator('[data-edit-category]');
    await editBtn.click();

    await expect(page.locator('#category-modal')).toBeVisible({ timeout: 5_000 });
    await page.fill('#category-name', 'E2E Kategorie Updated');
    await page.click('#category-save');
    await page.waitForTimeout(2000);

    await page.reload();
    await page.waitForSelector('#categories-table', { timeout: 10_000 });
    await expect(page.locator('#categories-table')).toContainText('E2E Kategorie Updated', { timeout: 5_000 });
  });

  test('moderator deletes category', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation/kategorien');
    await page.waitForSelector('#categories-table', { timeout: 10_000 });

    const row = page.locator('#categories-table tr:has-text("E2E Kategorie Updated"), #categories-table [data-name="E2E Kategorie Updated"]').first();
    const deleteBtn = row.locator('[data-delete-category]');
    await deleteBtn.click();

    await expect(page.locator('#delete-modal')).toBeVisible({ timeout: 5_000 });
    await page.click('#delete-confirm');
    await page.waitForTimeout(2000);

    await page.reload();
    await page.waitForSelector('#categories-table', { timeout: 10_000 });
    await expect(page.locator('#categories-table')).not.toContainText('E2E Kategorie Updated', { timeout: 5_000 });
  });

  // --- User management: expert flag ---

  test('moderator toggles expert flag on user', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation/benutzer');
    await page.waitForSelector('#users-container', { timeout: 10_000 });

    // Search for user
    const searchInput = page.locator('#user-search');
    if (await searchInput.isVisible()) {
      await searchInput.fill('user@e2e.test');
      await page.waitForTimeout(500);
    }

    // Find expert toggle button for user@e2e.test
    const toggleBtn = page.locator('[data-toggle-expert]').first();
    await expect(toggleBtn).toBeVisible({ timeout: 5_000 });
    await toggleBtn.click();
    await page.waitForTimeout(2000);
  });

  test('expert flag email sent to user', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'user@e2e.test', 'Expert');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  test('moderator toggles expert flag off', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation/benutzer');
    await page.waitForSelector('#users-container', { timeout: 10_000 });

    const searchInput = page.locator('#user-search');
    if (await searchInput.isVisible()) {
      await searchInput.fill('user@e2e.test');
      await page.waitForTimeout(500);
    }

    const toggleBtn = page.locator('[data-toggle-expert]').first();
    await expect(toggleBtn).toBeVisible({ timeout: 5_000 });
    await toggleBtn.click();
    await page.waitForTimeout(2000);
  });

  // --- Reject flow ---

  test('moderator rejects an idea via UI', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });

    // Click first pending review (if any)
    const reviewLink = page.locator('#reviews-container a').first();
    const hasPending = await reviewLink.isVisible({ timeout: 5_000 }).catch(() => false);
    test.skip(!hasPending, 'No pending reviews to reject');

    await reviewLink.click();
    await page.waitForSelector('#btn-reject', { timeout: 10_000 });

    await page.click('#btn-reject');
    await expect(page.locator('#reject-modal')).toBeVisible({ timeout: 3_000 });

    const reason = 'Not aligned with current project priorities.';
    await page.fill('#reject-reason', reason);
    await page.click('#reject-confirm');
    await page.waitForURL(/\/moderation/, { timeout: 10_000 });
  });

  test('rejection email sent with reason', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, '', 'nicht freigegeben');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });
});
```

- [ ] **Step 2: Run moderation admin tests**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1 tests/e2e/specs/06-moderation-admin.spec.ts
```

Expected: All tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/06-moderation-admin.spec.ts
git commit -m "feat: add GUI-only moderation admin E2E workflow"
```

---

### Task 9: Delete old API-based specs + browser/ directory

**Files:**
- Delete: `tests/e2e/specs/02-ideas.spec.ts`
- Delete: `tests/e2e/specs/03-moderation.spec.ts`
- Delete: `tests/e2e/specs/04-project-spaces.spec.ts`
- Delete: `tests/e2e/specs/04b-experts.spec.ts`
- Delete: `tests/e2e/specs/submit-idea.spec.ts`
- Delete: `tests/e2e/specs/category-management.spec.ts`
- Delete: `tests/e2e/specs/expert-request.spec.ts`
- Delete: `tests/e2e/specs/leave-remove-member.spec.ts`
- Delete: `tests/e2e/specs/moderation-flow.spec.ts`
- Delete: `tests/e2e/specs/notification-emails.spec.ts`
- Delete: `tests/e2e/specs/status-update.spec.ts`
- Delete: `tests/e2e/specs/user-management.spec.ts`
- Delete: `tests/e2e/specs/browser/auth.spec.ts`
- Delete: `tests/e2e/specs/browser/expert-flow.spec.ts`
- Delete: `tests/e2e/specs/browser/idea-management.spec.ts`
- Delete: `tests/e2e/specs/browser/idea-submission.spec.ts`
- Delete: `tests/e2e/specs/browser/moderation.spec.ts`
- Delete: `tests/e2e/specs/browser/project-spaces.spec.ts`

**Interfaces:**
- Consumes: All new specs from Tasks 2-8 must be passing before this task

- [ ] **Step 1: Delete all API-based and merged specs**

```bash
# API-based specs (replaced by new workflow specs)
rm tests/e2e/specs/02-ideas.spec.ts
rm tests/e2e/specs/03-moderation.spec.ts
rm tests/e2e/specs/04-project-spaces.spec.ts
rm tests/e2e/specs/04b-experts.spec.ts
rm tests/e2e/specs/submit-idea.spec.ts
rm tests/e2e/specs/category-management.spec.ts
rm tests/e2e/specs/expert-request.spec.ts
rm tests/e2e/specs/leave-remove-member.spec.ts
rm tests/e2e/specs/moderation-flow.spec.ts
rm tests/e2e/specs/notification-emails.spec.ts
rm tests/e2e/specs/status-update.spec.ts
rm tests/e2e/specs/user-management.spec.ts

# Browser specs (merged into new workflow specs)
rm tests/e2e/specs/browser/auth.spec.ts
rm tests/e2e/specs/browser/expert-flow.spec.ts
rm tests/e2e/specs/browser/idea-management.spec.ts
rm tests/e2e/specs/browser/idea-submission.spec.ts
rm tests/e2e/specs/browser/moderation.spec.ts
rm tests/e2e/specs/browser/project-spaces.spec.ts

# Remove empty browser directory
rmdir tests/e2e/specs/browser/
```

- [ ] **Step 2: Run the full E2E suite to verify everything passes**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1
```

Expected: All tests pass — the new workflow specs (00-06) plus the kept GUI-only specs (auth-flow, expert-pool, profile-access, projektraeume, projektraum).

- [ ] **Step 3: Commit**

```bash
git add -A tests/e2e/specs/
git commit -m "chore: remove API-based and merged browser E2E specs"
```

---

### Task 10: Final verification — full suite, twice

**Files:**
- None (verification only)

- [ ] **Step 1: Rebuild Docker container with fresh data**

```bash
docker compose -f docker-compose.test.yml down -v
docker compose -f docker-compose.test.yml up -d --wait
```

- [ ] **Step 2: Run full E2E suite**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1
```

Expected: All tests pass, 0 failures.

- [ ] **Step 3: Run suite a second time to check for flakiness**

```bash
docker compose -f docker-compose.test.yml down -v
docker compose -f docker-compose.test.yml up -d --wait
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1
```

Expected: All tests pass again, 0 flaky.

- [ ] **Step 4: Fix any failures or flakiness**

If any tests fail, diagnose and fix:
- Selector not found → check the actual page HTML, update selector
- Timeout → increase specific wait or add `waitForSelector`
- Flaky → add more robust waits, remove `waitForTimeout` in favor of `waitForSelector` or `waitForResponse`

- [ ] **Step 5: Commit fixes if any**

```bash
git add tests/e2e/
git commit -m "fix: stabilize GUI-only E2E tests"
```
