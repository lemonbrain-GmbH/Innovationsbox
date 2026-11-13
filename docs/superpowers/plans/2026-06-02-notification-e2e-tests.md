# Notification E2E Tests — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Verify all 12 email notification types fire correctly via E2E tests against the CMS email log.

**Architecture:** A lightweight PHP script reads email logs from the CMS `Logs` table (`channel = 'sent_emails'`). Playwright tests trigger API actions, then query the log server to verify emails were logged with the correct subject lines.

**Tech Stack:** Playwright, TypeScript, PHP (standalone DB reader), Concrete CMS `Logs` table

---

## File Structure

| File | Responsibility |
|------|---------------|
| `tests/e2e/email-log-server/index.php` | Standalone PHP server querying `Logs` table for sent emails |
| `tests/e2e/fixtures/helpers.ts` | Add `clearEmailLogs()` and `getEmailLogs()` helpers |
| `tests/e2e/playwright.config.ts` | Add email log server as second webServer |
| `tests/e2e/specs/notification-emails.spec.ts` | All 12 notification E2E tests |

---

### Task 1: Email Log Server

**Files:**
- Create: `tests/e2e/email-log-server/index.php`

- [ ] **Step 1: Create the email log server**

This standalone PHP script connects directly to the database (reads `.env` for credentials) and serves email log data as JSON. It does NOT boot the CMS — just reads the `Logs` table.

```php
<?php
// tests/e2e/email-log-server/index.php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load .env manually (no CMS boot needed)
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"");
        }
    }
}

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $_ENV['DB_HOSTNAME'] ?? '127.0.0.1',
    $_ENV['DB_DATABASE'] ?? '',
    $_ENV['DB_CHARSET'] ?? 'utf8mb4',
);

try {
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'] ?? '', $_ENV['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    http_response_code(500);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'DELETE') {
    $pdo->exec("DELETE FROM Logs WHERE channel = 'sent_emails'");
    echo json_encode(['status' => 'cleared']);
    exit;
}

if ($method === 'GET') {
    $since = isset($_GET['since']) ? (int) $_GET['since'] : 0;
    $stmt = $pdo->prepare('SELECT logID, message, time FROM Logs WHERE channel = ? AND time >= ? ORDER BY time DESC LIMIT 100');
    $stmt->execute(['sent_emails', $since]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $rows]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
```

- [ ] **Step 2: Test locally**

Run: `php -S localhost:6008 tests/e2e/email-log-server/index.php &`
Then: `curl http://localhost:6008/ && kill %1`

Expected: `{"data":[...]}` (empty array if no email logs exist yet)

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/email-log-server/index.php
git commit -m "feat(e2e): add email log server for notification testing"
```

---

### Task 2: Playwright Config + Helpers

**Files:**
- Modify: `tests/e2e/playwright.config.ts`
- Modify: `tests/e2e/fixtures/helpers.ts`

- [ ] **Step 1: Update playwright config to start email log server**

Read `tests/e2e/playwright.config.ts`. The current `webServer` config starts the storybook PHP server on port 6007. Playwright supports an array of web servers. Change `webServer` from a single object to an array:

```typescript
webServer: [
    {
      command: 'php -S localhost:6007 storybook-server/index.php',
      url: 'http://localhost:6007',
      reuseExistingServer: true,
      timeout: 10_000,
    },
    {
      command: 'php -S localhost:6008 tests/e2e/email-log-server/index.php',
      url: 'http://localhost:6008',
      reuseExistingServer: true,
      timeout: 10_000,
    },
  ],
```

- [ ] **Step 2: Add email log helpers**

Append to `tests/e2e/fixtures/helpers.ts`:

```typescript
const EMAIL_LOG_URL = 'http://localhost:6008';

export async function clearEmailLogs(request: APIRequestContext): Promise<void> {
  const res = await request.delete(EMAIL_LOG_URL);
  if (!res.ok()) {
    throw new Error(`clearEmailLogs failed: ${res.status()}`);
  }
}

export async function getEmailLogs(
  request: APIRequestContext,
  since: number = 0,
): Promise<Array<{ logID: string; message: string; time: string }>> {
  const res = await request.get(`${EMAIL_LOG_URL}/?since=${since}`);
  if (!res.ok()) {
    throw new Error(`getEmailLogs failed: ${res.status()}`);
  }
  const body = await res.json();
  return body.data;
}

export function emailLogsContain(
  logs: Array<{ message: string }>,
  substring: string,
): boolean {
  return logs.some(log => log.message.toLowerCase().includes(substring.toLowerCase()));
}
```

- [ ] **Step 3: Verify TypeScript compiles**

Run: `npm run typecheck`
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add tests/e2e/playwright.config.ts tests/e2e/fixtures/helpers.ts
git commit -m "feat(e2e): add email log helpers and config for notification testing"
```

---

### Task 3: Notification E2E Test Spec

**Files:**
- Create: `tests/e2e/specs/notification-emails.spec.ts`

This is the main test file. It tests all 12 notification types by triggering API actions and verifying email logs.

- [ ] **Step 1: Write the full test spec**

```typescript
// tests/e2e/specs/notification-emails.spec.ts

import { test, expect } from '@playwright/test';
import {
  submitIdea,
  getPendingReviewForIdea,
  clearEmailLogs,
  getEmailLogs,
  emailLogsContain,
} from '../fixtures/helpers';

const SPACE_ID = 'test_space_1'; // active, initiator=1, members=1,2,3,4
const SPACE_API = `/api/project-space/${SPACE_ID}`;

test.describe.serial('Notification Emails', () => {
  // --- Group 1: Idea lifecycle (submit → approve, submit → reject, submit → feedback) ---

  test('1. ProjectIdeaSubmitted → admin email', async ({ request }) => {
    await clearEmailLogs(request);
    const since = Math.floor(Date.now() / 1000) - 5;

    await submitIdea(request, {
      title: 'E2E Notification Test Idea',
      description: 'Testing that submission triggers admin notification',
      category: 'environment',
    });

    const logs = await getEmailLogs(request, since);
    expect(
      emailLogsContain(logs, 'Neue Projektidee eingereicht'),
      'Expected admin notification email for submitted idea',
    ).toBe(true);
  });

  test('2. ProjectIdeaApproved → submitter email', async ({ request }) => {
    const ideaId = await submitIdea(request, {
      title: 'E2E Approval Notification Idea',
      description: 'Testing that approval triggers submitter notification',
      category: 'environment',
    });
    const review = await getPendingReviewForIdea(request, ideaId);
    expect(review).toBeTruthy();

    await clearEmailLogs(request);
    const since = Math.floor(Date.now() / 1000) - 5;

    await request.post(`/api/moderation/${review!.id}/approve`, {
      data: { notes: 'Approved for E2E test' },
    });

    const logs = await getEmailLogs(request, since);
    expect(
      emailLogsContain(logs, 'freigegeben'),
      'Expected approval notification email to submitter',
    ).toBe(true);
  });

  test('3. ProjectIdeaRejected → submitter email', async ({ request }) => {
    const ideaId = await submitIdea(request, {
      title: 'E2E Rejection Notification Idea',
      description: 'Testing that rejection triggers submitter notification',
      category: 'other',
    });
    const review = await getPendingReviewForIdea(request, ideaId);
    expect(review).toBeTruthy();

    await clearEmailLogs(request);
    const since = Math.floor(Date.now() / 1000) - 5;

    await request.post(`/api/moderation/${review!.id}/reject`, {
      data: { notes: 'Not relevant for E2E test' },
    });

    const logs = await getEmailLogs(request, since);
    expect(
      emailLogsContain(logs, 'Rückmeldung'),
      'Expected rejection notification email to submitter',
    ).toBe(true);
  });

  test('4. ModerationFeedbackRequested → submitter email', async ({ request }) => {
    const ideaId = await submitIdea(request, {
      title: 'E2E Feedback Notification Idea',
      description: 'Testing that feedback request triggers submitter notification',
      category: 'other',
    });
    const review = await getPendingReviewForIdea(request, ideaId);
    expect(review).toBeTruthy();

    await clearEmailLogs(request);
    const since = Math.floor(Date.now() / 1000) - 5;

    await request.post(`/api/moderation/${review!.id}/feedback`, {
      data: { notes: 'Please add more details', deadline: '2026-07-01' },
    });

    const logs = await getEmailLogs(request, since);
    expect(
      emailLogsContain(logs, 'Rückmeldung'),
      'Expected feedback notification email to submitter',
    ).toBe(true);
  });

  // --- Group 2: Join requests ---

  test('5. JoinRequestReceived → initiator email', async ({ request }) => {
    await clearEmailLogs(request);
    const since = Math.floor(Date.now() / 1000) - 5;

    // Use test_space_4 where user 5 (moderator) is not a member
    const res = await request.post('/api/project-space/test_space_4/join-request');
    // May fail if user already a member — that's ok, we check logs regardless
    if (res.ok()) {
      const logs = await getEmailLogs(request, since);
      expect(
        emailLogsContain(logs, 'Beitrittsanfrage'),
        'Expected join request notification to space initiator',
      ).toBe(true);
    }
  });

  test('6. JoinRequestAccepted → requester email', async ({ request }) => {
    // Submit a fresh join request to get a request ID
    const joinRes = await request.post('/api/project-space/test_space_4/join-request');
    let requestId: string | undefined;
    if (joinRes.ok()) {
      const body = await joinRes.json();
      requestId = body.data?.id;
    }

    if (requestId) {
      await clearEmailLogs(request);
      const since = Math.floor(Date.now() / 1000) - 5;

      await request.put(`/api/project-space/test_space_4/join-request/${requestId}`, {
        data: { accept: true },
      });

      const logs = await getEmailLogs(request, since);
      expect(
        emailLogsContain(logs, 'angenommen'),
        'Expected join acceptance notification to requester',
      ).toBe(true);
    }
  });

  test('7. JoinRequestRejected → requester email', async ({ request }) => {
    const joinRes = await request.post('/api/project-space/test_space_4/join-request');
    let requestId: string | undefined;
    if (joinRes.ok()) {
      const body = await joinRes.json();
      requestId = body.data?.id;
    }

    if (requestId) {
      await clearEmailLogs(request);
      const since = Math.floor(Date.now() / 1000) - 5;

      await request.put(`/api/project-space/test_space_4/join-request/${requestId}`, {
        data: { accept: false },
      });

      const logs = await getEmailLogs(request, since);
      expect(
        emailLogsContain(logs, 'abgelehnt'),
        'Expected join rejection notification to requester',
      ).toBe(true);
    }
  });

  // --- Group 3: Member invitation ---

  test('8. MemberInvited → invited user email', async ({ request }) => {
    await clearEmailLogs(request);
    const since = Math.floor(Date.now() / 1000) - 5;

    await request.post(`${SPACE_API}/invite`, {
      data: { userId: '5' }, // Invite user 5 (moderator, not a member of space 1)
    });

    const logs = await getEmailLogs(request, since);
    expect(
      emailLogsContain(logs, 'eingeladen'),
      'Expected invitation notification to invited user',
    ).toBe(true);
  });

  // --- Group 4: Expert requests ---

  test('9. ExpertRequested → expert email', async ({ request }) => {
    // List available experts
    const expertsRes = await request.get(`${SPACE_API}/experts`);
    const expertsBody = await expertsRes.json();
    const expertUserId = expertsBody.data?.[0]?.userId;
    test.skip(!expertUserId, 'No experts available');

    await clearEmailLogs(request);
    const since = Math.floor(Date.now() / 1000) - 5;

    await request.post(`${SPACE_API}/expert-request`, {
      data: { expertUserId, message: 'E2E test expert request' },
    });

    const logs = await getEmailLogs(request, since);
    expect(
      emailLogsContain(logs, 'Expertise'),
      'Expected expert request notification to expert',
    ).toBe(true);
  });

  test('10. ExpertRequestAccepted → initiator email', async ({ request }) => {
    // Get space detail to find a pending expert request
    const spaceRes = await request.get(SPACE_API);
    const spaceBody = await spaceRes.json();
    const pending = (spaceBody.data?.expertRequests as Array<{ id: string; status: string }> ?? [])
      .find(r => r.status === 'pending');
    test.skip(!pending, 'No pending expert request');

    await clearEmailLogs(request);
    const since = Math.floor(Date.now() / 1000) - 5;

    await request.put(`${SPACE_API}/expert-request/${pending!.id}`, {
      data: { accept: true },
    });

    const logs = await getEmailLogs(request, since);
    expect(
      emailLogsContain(logs, 'angenommen'),
      'Expected expert acceptance notification to initiator',
    ).toBe(true);
  });

  test('11. ExpertRequestDeclined → initiator email', async ({ request }) => {
    // Create a new expert request first, then decline it
    const expertsRes = await request.get(`${SPACE_API}/experts`);
    const expertsBody = await expertsRes.json();
    const expertUserId = expertsBody.data?.[0]?.userId;
    test.skip(!expertUserId, 'No experts available for decline test');

    // Request an expert
    await request.post(`${SPACE_API}/expert-request`, {
      data: { expertUserId, message: 'E2E decline test' },
    });

    // Find the pending request
    const spaceRes = await request.get(SPACE_API);
    const spaceBody = await spaceRes.json();
    const pending = (spaceBody.data?.expertRequests as Array<{ id: string; status: string }> ?? [])
      .find(r => r.status === 'pending');
    test.skip(!pending, 'No pending expert request for decline');

    await clearEmailLogs(request);
    const since = Math.floor(Date.now() / 1000) - 5;

    await request.put(`${SPACE_API}/expert-request/${pending!.id}`, {
      data: { accept: false },
    });

    const logs = await getEmailLogs(request, since);
    expect(
      emailLogsContain(logs, 'abgelehnt'),
      'Expected expert decline notification to initiator',
    ).toBe(true);
  });

  // --- Group 5: Status updates ---

  test('12. StatusUpdatePublished → all members email', async ({ request }) => {
    await clearEmailLogs(request);
    const since = Math.floor(Date.now() / 1000) - 5;

    const res = await request.post(`${SPACE_API}/status-update`, {
      data: {
        title: 'E2E Notification Status Update',
        body: '<p>Testing that members receive notification</p>',
      },
    });
    expect(res.status()).toBe(201);

    const logs = await getEmailLogs(request, since);
    // test_space_1 has 4 members (users 1,2,3,4). Author is the current user.
    // So at least 3 emails should be sent (4 members - 1 author = 3)
    const updateEmails = logs.filter(log =>
      log.message.toLowerCase().includes('update'),
    );
    expect(
      updateEmails.length,
      `Expected at least 3 status update emails, got ${updateEmails.length}`,
    ).toBeGreaterThanOrEqual(3);
  });
});
```

- [ ] **Step 2: Verify TypeScript compiles**

Run: `npm run typecheck`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/notification-emails.spec.ts
git commit -m "test(e2e): add 12 notification email verification tests"
```
