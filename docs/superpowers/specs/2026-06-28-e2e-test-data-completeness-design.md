# E2E Test Data Completeness — Design Spec

**Date:** 2026-06-28
**Status:** Approved
**Scope:** Ensure all 45 browser E2E tests pass without skips by fixing seed data, CMS page creation, and test guards.

## Problem

9 of 45 browser E2E tests skip due to missing test data, missing CMS pages, or incorrect test assumptions:

| Skip count | Root cause |
|-----------|------------|
| 6 | `/members/profile` and `/members/directory` CMS pages don't exist in Docker test env |
| 2 | `#expert-select` empty — the only expert user is already a member of the test space, so the experts API excludes them |
| 1 | Collaboard panel test runs as anonymous visitor, but panel only renders for members/initiators |

Additionally, 2 tests fail (not skip) due to test data being consumed by previous runs without re-seeding:
- Expert request status changes from `pending` to `declined` after the expert-flow test runs
- Moderation feedback test has no remaining unreviewed idea after approve/reject tests consume them

## Decisions

- Create missing CMS pages in `test-entrypoint.sh` (not in package install — these are core CMS pages)
- Add a second expert user (`expert2@e2e.test`) who is NOT a member of any space
- Fix test assertions to match the actual data and UI state
- Remove overly cautious `test.skip()` guards where the seed data guarantees the prerequisite

---

## Change 1: Add `expert2@e2e.test` user

**File:** `scripts/seed-e2e-users.sh`

Add a 6th test user:

| uName | uEmail | Group | Purpose |
|-------|--------|-------|---------|
| `e2e-expert2` | `expert2@e2e.test` | `Experte` | Available expert who is NOT a member of any space |

Same password (`E2eTest!2026`), same attributes as other users. The key difference from `expert@e2e.test`: this user is never added to `ib_project_space_members`.

---

## Change 2: Fix seed data relationships

**File:** `scripts/seed-e2e-data.sh`

### Remove conflicting expert membership

Delete the `e2e_mem_active_pending` row (expert@e2e.test as pending member of e2e_space_active). This row creates a conflict: the expert is both a pending member AND in the Experte group, causing the experts API to exclude them from the available experts list.

### Restructure expert data for two separate roles

Use TWO expert users with distinct roles:
- `expert@e2e.test` — in `Experte` group, NOT a member of any space, NOT in any expert request. Available in the `#expert-select` dropdown for the "request expert" test.
- `expert2@e2e.test` — in `Experte` group, has the seeded pending expert request `e2e_ereq_pending` for `e2e_space_active`. Shows as pending on `/expertanfragen` for the "decline request" test.

Changes to seed data:
1. Remove `e2e_mem_active_pending` (expert@e2e.test as pending member of e2e_space_active)
2. Update `e2e_ereq_pending` to use `expert2@e2e.test` as the expert

Result:
- `#expert-select` shows `expert@e2e.test` (available, no membership, no request)
- `/expertanfragen` for `expert2@e2e.test` shows the pending request to decline

### Add extra submitted idea for feedback test

The moderation tests are serial: approve consumes one idea, reject consumes another, feedback needs a third. The seed has `e2e_review_submitted` (for `e2e_idea_submitted`) and `e2e_review_under_review` (for `e2e_idea_under_review`), but the browser tests create their own ideas via `seedIdea()` API calls — so this should be sufficient. The feedback test failure is likely a timing/email issue, not a data issue.

---

## Change 3: Create CMS member pages in entrypoint

**File:** `docker/test-entrypoint.sh`

After all packages are installed and before seeding, create the core CMS member pages using a PHP script executed via `c5:exec`:

**File:** `scripts/create-member-pages.php`

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

Called from `test-entrypoint.sh`:
```bash
echo "=== Creating CMS member pages ==="
./vendor/bin/concrete c5:exec scripts/create-member-pages.php
```

After page creation, the `inno_frontend` package's `registerSinglePageOverrides()` will reassign them to the innovationsbox theme on the next request (runs in `on_start()`).

---

## Change 4: Fix test assertions and remove skip guards

### `tests/e2e/specs/projektraum.spec.ts` — collaboard test

**Current:** Visits `/projektraum/view/e2e_space_active` as anonymous, checks `.collaboard-panel`.
**Fix:** Login as `initiator@e2e.test` before visiting the space detail page. The collaboard panel only renders for members/initiators. Remove the `test.skip()` guard.

### `tests/e2e/specs/browser/expert-flow.spec.ts` — request expert

**Current:** Skips if `#expert-select` not visible within 3s.
**Fix:** With `expert@e2e.test` available in the dropdown (not a member, no pending request), the select will have options. Keep a reasonable wait timeout but remove the skip — if the select doesn't appear, the test should fail (not skip), since seed data guarantees it.

### `tests/e2e/specs/browser/expert-flow.spec.ts` — expert declines

**Current:** Skips if `button[data-accept="false"]` not visible.
**Fix:** Login as `expert2@e2e.test` (who has the pending request). The decline button should appear. Remove the skip guard — seed data guarantees the request exists.

### `tests/e2e/specs/browser/project-spaces.spec.ts` — write status update

**Current:** Skips if `a[href*="/projektraum/view/"]` not visible or `#btn-write-update` not visible.
**Fix:** The seed data has `e2e_idea_approved` with `project_space_id = e2e_space_active` for `initiator@e2e.test`. The "Zum Projektraum" link should render. Increase the timeout and convert the skip to a proper assertion failure.

### `tests/e2e/specs/profile-access.spec.ts` — all 4 tests

**Current:** Skip if `GET /members/profile` returns 404.
**Fix:** With the CMS pages created in the entrypoint, the 404 check will pass. Remove the skip guards.

### `tests/e2e/specs/expert-pool.spec.ts` — both tests

**Current:** Skip if `GET /members/directory` returns 404.
**Fix:** Same — CMS page will exist. Remove the skip guards.

---

## Change 5: Test isolation for consumed seed data

The expert-flow tests are serial but operate on DIFFERENT expert requests:
- Test 1 (request expert): initiator uses the browser UI to request `expert@e2e.test` (available in dropdown, no existing request)
- Test 2 (decline): `expert2@e2e.test` declines the seeded `e2e_ereq_pending` request

Since they target different expert users, the tests are independent and don't consume each other's data.

The Docker environment is disposable — `docker compose down -v` between CI runs. For local development, `make seed-e2e` re-seeds all tables.

---

## Summary of file changes

| File | Change |
|------|--------|
| `scripts/seed-e2e-users.sh` | Add `expert2@e2e.test` user in `Experte` group |
| `scripts/seed-e2e-data.sh` | Remove `e2e_mem_active_pending`, update `e2e_ereq_pending` to use expert2 |
| `docker/test-entrypoint.sh` | Create `/members`, `/members/directory`, `/members/profile` CMS pages |
| `tests/e2e/specs/projektraum.spec.ts` | Login as initiator for collaboard test |
| `tests/e2e/specs/browser/expert-flow.spec.ts` | Use expert2 for decline, remove skip guards |
| `tests/e2e/specs/browser/project-spaces.spec.ts` | Increase timeouts, convert skips to assertions |
| `tests/e2e/specs/profile-access.spec.ts` | Remove 404 skip guards |
| `tests/e2e/specs/expert-pool.spec.ts` | Remove 404 skip guards |

**Expected result:** 45 tests, 0 skipped, 0 failed.
