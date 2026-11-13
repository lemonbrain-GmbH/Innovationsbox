import { APIRequestContext, Page, BrowserContext } from '@playwright/test';

const loginTimeout = process.env.E2E_DOCKER === 'true' ? 30_000 : 10_000;

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
  }, { timeout: loginTimeout });

  // Navigate to a themed page to get the CSRF token (homepage uses atomik theme, not innovationsbox)
  let csrfMeta = page.locator('meta[name="csrf-token"]');
  if ((await csrfMeta.count()) === 0) {
    await page.goto('/innovationen');
    csrfMeta = page.locator('meta[name="csrf-token"]');
  }
  // CSRF token is now available on the page for apiFetch to pick up
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
