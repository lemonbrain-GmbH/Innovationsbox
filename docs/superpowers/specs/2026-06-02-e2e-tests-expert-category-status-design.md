# E2E Tests: Expert Request, Category Management, Status Update — Design Spec

**Date:** 2026-06-02
**Goal:** Add E2E test coverage for three untested API flows: expert request lifecycle, category CRUD, and status update publishing.

---

## Shared Patterns

- API-level E2E tests using Playwright `request` fixture
- Authentication via page-based login (session cookies carry to API calls)
- Test data from `make seed` (users 1-5, spaces test_space_1-6, categories)
- Tests clean up created resources where possible
- Add `loginAs(page, email, password)` helper to `tests/e2e/fixtures/helpers.ts`

**Test users:**
| Email | Password | User ID | Role |
|---|---|---|---|
| `admin@lemonbrain.ch` | `123456` | 1 | Administrator |
| `initiator@lemonbrain.ch` | `123456` | 3 | Initiator (owns test_space_1) |
| `entwickler@lemonbrain.ch` | `123456` | 4 | Developer/member |
| `moderator@lemonbrain.ch` | `123456` | 5 | Moderator |

---

## Test 1: `tests/e2e/specs/expert-request.spec.ts`

**Actor:** initiator (user 3) requests expert, admin (user 1) handles request.

**Tests:**
1. List available experts for a space — `GET /api/project-space/{id}/experts` returns 200 with array
2. Request an expert — `POST /api/project-space/{id}/expert-request` with `{ expertUserId, message }` returns 200
3. Verify pending request appears in space detail — `GET /api/project-space/{id}` includes the expert request
4. Accept expert request — `PUT /api/project-space/{id}/expert-request` with `{ requestId, decision: 'accept' }` returns 200
5. Decline scenario — request another expert, decline with `{ requestId, decision: 'decline' }`

**Space:** `test_space_1` (active, owned by user 3)

---

## Test 2: `tests/e2e/specs/category-management.spec.ts`

**Actor:** admin (user 1) — has moderation.review permission.

**Tests:**
1. List categories — `GET /api/moderation/category` returns 200 with array
2. Create category — `POST /api/moderation/category` with `{ name, slug }` returns 201 with id
3. Verify created — GET list includes new category
4. Update category — `PUT /api/moderation/category/{id}` with `{ name }` returns 200
5. Verify updated — GET list shows new name
6. Delete category — `DELETE /api/moderation/category/{id}` returns 200
7. Verify deleted — GET list no longer includes it
8. Delete category with assigned ideas fails — 409 (if seeded categories have ideas)

**Cleanup:** Test creates a unique category (`e2e-test-{timestamp}`) and deletes it.

---

## Test 3: `tests/e2e/specs/status-update.spec.ts`

**Actor:** initiator (user 3) — owns test_space_1.

**Tests:**
1. Add status update — `POST /api/project-space/{id}/status-update` with `{ title, body }` returns 200
2. Verify in space detail — `GET /api/project-space/{id}` includes the update
3. Edit status update — `PUT /api/project-space/{id}/status-update/{updateId}` returns 200
4. Verify edit — GET shows updated title/body
5. Delete status update — `DELETE /api/project-space/{id}/status-update/{updateId}` returns 200
6. Verify deleted — GET no longer includes it

**Space:** `test_space_1` (active, owned by user 3)
