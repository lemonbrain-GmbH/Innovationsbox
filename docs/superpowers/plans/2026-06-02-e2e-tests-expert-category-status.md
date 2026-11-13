# E2E Tests: Expert Request, Category Management, Status Update — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add E2E test coverage for expert request lifecycle, category CRUD, and status update publishing.

**Architecture:** Three new Playwright test specs following the existing API-level pattern (direct `request` fixture calls with JSON). Plus a `loginAs` helper for tests that need authenticated sessions. Tests use seeded data from `make seed`.

**Tech Stack:** Playwright, TypeScript, Concrete CMS API

---

## File Structure

| File | Responsibility |
|------|---------------|
| `tests/e2e/fixtures/helpers.ts` | Shared test helpers — add `loginAs` |
| `tests/e2e/specs/category-management.spec.ts` | Category CRUD via `/api/moderation/category` |
| `tests/e2e/specs/status-update.spec.ts` | Status update lifecycle via `/api/project-space/{id}/status-update` |
| `tests/e2e/specs/expert-request.spec.ts` | Expert request + accept/decline via `/api/project-space/{id}/expert-request` |

---

### Task 1: Add loginAs helper to fixtures

**Files:**
- Modify: `tests/e2e/fixtures/helpers.ts`

- [ ] **Step 1: Add loginAs helper**

Append to `tests/e2e/fixtures/helpers.ts`:

```typescript
import { APIRequestContext, Page } from '@playwright/test';

// ... existing helpers above ...

/**
 * Login via the CMS login page. After this, the page's browser context
 * carries session cookies that are shared with `request` in the same test.
 */
export async function loginAs(
  page: Page,
  email: string,
  password: string,
): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="uName"]', email);
  await page.fill('input[name="uPassword"]', password);
  await page.click('button[type="submit"]');
  await page.waitForURL(/\/(account|index\.php).*|\/$/);
}
```

- [ ] **Step 2: Verify TypeScript compiles**

Run: `npm run typecheck`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/fixtures/helpers.ts
git commit -m "test(e2e): add loginAs helper for authenticated E2E tests"
```

---

### Task 2: Category management E2E test

**Files:**
- Create: `tests/e2e/specs/category-management.spec.ts`

- [ ] **Step 1: Write the test spec**

```typescript
// tests/e2e/specs/category-management.spec.ts

import { test, expect } from '@playwright/test';

const API = '/api/moderation/category';
const UNIQUE = `e2e-${Date.now()}`;

test.describe('Category Management CRUD', () => {
  let categoryId: string;

  test('list categories', async ({ request }) => {
    const res = await request.get(API);
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(Array.isArray(body.data)).toBe(true);
  });

  test('create category', async ({ request }) => {
    const res = await request.post(API, {
      data: { name: `Test Category ${UNIQUE}`, slug: `test-${UNIQUE}`, sort_order: 99 },
    });
    expect(res.status()).toBe(201);
    const body = await res.json();
    expect(body.data.name).toBe(`Test Category ${UNIQUE}`);
    expect(body.data.slug).toBe(`test-${UNIQUE}`);
    expect(body.data.id).toBeTruthy();
    categoryId = body.data.id;
  });

  test('verify created category in list', async ({ request }) => {
    const res = await request.get(API);
    const body = await res.json();
    const found = (body.data as Array<{ id: string }>).find(c => c.id === categoryId);
    expect(found).toBeTruthy();
  });

  test('update category', async ({ request }) => {
    const res = await request.put(`${API}/${categoryId}`, {
      data: { name: `Updated ${UNIQUE}` },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.data.name).toBe(`Updated ${UNIQUE}`);
  });

  test('delete category', async ({ request }) => {
    const res = await request.delete(`${API}/${categoryId}`);
    expect(res.status()).toBe(204);
  });

  test('verify deleted category gone', async ({ request }) => {
    const res = await request.get(API);
    const body = await res.json();
    const found = (body.data as Array<{ id: string }>).find(c => c.id === categoryId);
    expect(found).toBeUndefined();
  });
});
```

- [ ] **Step 2: Verify TypeScript compiles**

Run: `npm run typecheck`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/category-management.spec.ts
git commit -m "test(e2e): add category management CRUD spec"
```

---

### Task 3: Status update E2E test

**Files:**
- Create: `tests/e2e/specs/status-update.spec.ts`

The API endpoints are:
- `POST /api/project-space/{id}/status-update` → `{ data: { id } }` (201)
- `PUT /api/project-space/{id}/status-update/{updateId}` → `{ status: 'updated' }` (200)
- `DELETE /api/project-space/{id}/status-update/{updateId}` → 200

The seeded space `test_space_1` is active and owned by user 3 (initiator).

- [ ] **Step 1: Write the test spec**

```typescript
// tests/e2e/specs/status-update.spec.ts

import { test, expect } from '@playwright/test';

const SPACE_ID = 'test_space_1';
const API = `/api/project-space/${SPACE_ID}`;
const UNIQUE = `e2e-${Date.now()}`;

test.describe('Status Update Lifecycle', () => {
  let updateId: string;

  test('add status update', async ({ request }) => {
    const res = await request.post(`${API}/status-update`, {
      data: {
        title: `Status Update ${UNIQUE}`,
        body: `<p>Progress report for E2E test ${UNIQUE}</p>`,
      },
    });
    expect(res.status()).toBe(201);
    const body = await res.json();
    expect(body.data.id).toBeTruthy();
    updateId = body.data.id;
  });

  test('verify status update in space detail', async ({ request }) => {
    const res = await request.get(API);
    expect(res.status()).toBe(200);
    const body = await res.json();
    const updates = body.data.statusUpdates as Array<{ id: string; title: string }>;
    const found = updates.find(u => u.id === updateId);
    expect(found).toBeTruthy();
    expect(found!.title).toBe(`Status Update ${UNIQUE}`);
  });

  test('edit status update', async ({ request }) => {
    const res = await request.put(`${API}/status-update/${updateId}`, {
      data: {
        title: `Edited ${UNIQUE}`,
        body: `<p>Updated body ${UNIQUE}</p>`,
      },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.status).toBe('updated');
  });

  test('verify edit in space detail', async ({ request }) => {
    const res = await request.get(API);
    const body = await res.json();
    const updates = body.data.statusUpdates as Array<{ id: string; title: string }>;
    const found = updates.find(u => u.id === updateId);
    expect(found).toBeTruthy();
    expect(found!.title).toBe(`Edited ${UNIQUE}`);
  });

  test('delete status update', async ({ request }) => {
    const res = await request.delete(`${API}/status-update/${updateId}`);
    expect(res.ok()).toBe(true);
  });

  test('verify deleted', async ({ request }) => {
    const res = await request.get(API);
    const body = await res.json();
    const updates = body.data.statusUpdates as Array<{ id: string }>;
    const found = updates.find(u => u.id === updateId);
    expect(found).toBeUndefined();
  });
});
```

- [ ] **Step 2: Verify TypeScript compiles**

Run: `npm run typecheck`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/status-update.spec.ts
git commit -m "test(e2e): add status update lifecycle spec"
```

---

### Task 4: Expert request E2E test

**Files:**
- Create: `tests/e2e/specs/expert-request.spec.ts`

The API endpoints are:
- `GET /api/project-space/{id}/experts` → `{ data: [{ userId, name, expertise }] }`
- `POST /api/project-space/{id}/expert-request` → `{ status: 'requested' }` (201), body: `{ expertUserId, message? }`
- `PUT /api/project-space/{id}/expert-request/{requestId}` → `{ status: 'handled' }`, body: `{ accept: bool }`

Seeded data: `test_space_1` owned by user 3. User 11 is in Expert group (testserver seed) — but for standard seed, need to check if an expert user exists.

- [ ] **Step 1: Write the test spec**

```typescript
// tests/e2e/specs/expert-request.spec.ts

import { test, expect } from '@playwright/test';

const SPACE_ID = 'test_space_1';
const API = `/api/project-space/${SPACE_ID}`;

test.describe('Expert Request Lifecycle', () => {
  let expertUserId: string;
  let requestId: string;

  test('list available experts', async ({ request }) => {
    const res = await request.get(`${API}/experts`);
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(Array.isArray(body.data)).toBe(true);

    // Store first available expert for subsequent tests
    if (body.data.length > 0) {
      expertUserId = body.data[0].userId;
    }
  });

  test('request an expert', async ({ request }) => {
    test.skip(!expertUserId, 'No experts available in test environment');

    const res = await request.post(`${API}/expert-request`, {
      data: {
        expertUserId,
        message: 'E2E test: requesting expert assistance',
      },
    });
    expect(res.status()).toBe(201);
    const body = await res.json();
    expect(body.status).toBe('requested');
  });

  test('verify expert request in space detail', async ({ request }) => {
    test.skip(!expertUserId, 'No experts available in test environment');

    const res = await request.get(API);
    expect(res.status()).toBe(200);
    const body = await res.json();

    const expertRequests = body.data.expertRequests as Array<{
      id: string;
      expertUserId: string;
      status: string;
    }>;
    const found = expertRequests.find(
      r => r.expertUserId === expertUserId && r.status === 'pending',
    );
    expect(found).toBeTruthy();
    requestId = found!.id;
  });

  test('accept expert request', async ({ request }) => {
    test.skip(!requestId, 'No pending expert request to accept');

    const res = await request.put(`${API}/expert-request/${requestId}`, {
      data: { accept: true },
    });
    expect(res.ok()).toBe(true);
    const body = await res.json();
    expect(body.status).toBe('handled');
  });

  test('verify expert request accepted', async ({ request }) => {
    test.skip(!requestId, 'No expert request to verify');

    const res = await request.get(API);
    const body = await res.json();
    const expertRequests = body.data.expertRequests as Array<{
      id: string;
      status: string;
    }>;
    const found = expertRequests.find(r => r.id === requestId);
    expect(found).toBeTruthy();
    expect(found!.status).toBe('accepted');
  });
});
```

- [ ] **Step 2: Verify TypeScript compiles**

Run: `npm run typecheck`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/expert-request.spec.ts
git commit -m "test(e2e): add expert request lifecycle spec"
```
