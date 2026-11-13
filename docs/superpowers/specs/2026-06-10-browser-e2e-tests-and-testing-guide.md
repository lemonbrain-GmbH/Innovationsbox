# Browser-Based E2E Tests & Testing Guide

**Date:** 2026-06-10
**Status:** Approved
**Scope:** Real browser-based E2E tests for key user journeys + comprehensive testing guide documentation

## Context

The Innovationsbox platform has 127 test files across unit, integration, API, and Storybook layers. However, the "E2E" specs in Phase 2 (`02-ideas.spec.ts`, `03-moderation.spec.ts`, etc.) are API-level tests — they call REST endpoints directly via `AuthedApi`, bypassing the UI entirely. They test backend API contracts, not what users actually see and click.

Real browser-based E2E tests that simulate user journeys (navigate to page, fill form, click button, see result) are missing for most flows. Additionally, there is no documentation explaining when to use which test type and where to add new tests.

## Decisions

- **Browser E2E = real user simulation.** Every test navigates to a page, interacts with visible UI elements, and asserts what the user sees.
- **Every email-triggering action verifies email content via Mailpit** — not just "email was sent" but the actual subject and body content.
- **CKEditor gets real keyboard interaction** — click into contenteditable div, type text. No programmatic bypass.
- **File upload uses a real fixture file** — a 1x1 PNG uploaded via Playwright's `setInputFiles()`.
- **Register completes email validation** — extract confirmation link from Mailpit, follow it.
- **Forgot password completes the full reset cycle** — Mailpit → extract link → new password → login.
- **Existing API specs stay in place** — they're valuable API contract tests. No moves.
- **New browser tests go in `tests/e2e/specs/browser/`** — clear separation.

---

## Browser Test Specs

### `tests/e2e/specs/browser/auth.spec.ts` — Authentication lifecycle (5 tests)

All tests use `loginViaUI()` (fills actual form) instead of `loginAs()` (API shortcut).

| # | Test | Steps | Email verification |
|---|------|-------|--------------------|
| 1 | Register + email validation | Navigate to `/register`, fill `input[name="uEmail"]`, `input[name="uName"]`, `input[name="uPassword"]`, `input[name="uPasswordConfirm"]`, submit. Check Mailpit for validation email to the registered address. Extract confirmation link from email body. Navigate to link. Verify `email_validated` success page. | Validation email: check subject contains "Bestätigung" or "verify", check body contains confirmation link |
| 2 | Login valid | Navigate to `/login`, fill `input[name="uName"]` with registered email, fill `input[name="uPassword"]`, submit. Verify redirect away from `/login`. Verify user menu or logged-in indicator visible. | — |
| 3 | Login invalid | Navigate to `/login`, fill wrong password, submit. Verify error message visible (`.alert--error, .alert--danger, .ccm-system-errors, [role="alert"]`). Verify still on `/login`. | — |
| 4 | Forgot password full flow | Navigate to `/login`, click "Passwort vergessen" link. Fill email, submit. Check Mailpit for reset email. Extract reset link. Navigate to link. Fill `input[name="uPassword"]` + `input[name="uPasswordConfirm"]` with new password. Submit. Verify success page. Login with new password. Verify success. | Reset email: check subject contains "Passwort", check body contains reset link |
| 5 | Logout | Login first. Click user menu. Click logout link/button. Verify redirected to login or homepage. Verify no longer in logged-in state. | — |

### `tests/e2e/specs/browser/idea-submission.spec.ts` — Submit idea via form (1 test)

| # | Test | Steps | Email verification |
|---|------|-------|--------------------|
| 1 | Submit idea with file upload | Login via UI as `user@e2e.test`. Navigate to `/projektidee/einreichen`. Fill `#idea-title`. Select option in `#idea-category`. Click into CKEditor editable area for `#idea-description`, type description text. Upload `tests/e2e/fixtures/test-upload.png` via `#idea-files` input. Click `#idea-submit`. Verify redirect to `/projektidee/meine` or success message. Verify the idea appears in `#ideas-container` with the title. | Admin notification email: check Mailpit for email to admin, verify subject contains idea title |

### `tests/e2e/specs/browser/idea-management.spec.ts` — My ideas page (3 tests)

Uses seeded data from `initiator@e2e.test` who has ideas in all statuses.

| # | Test | Steps | Email verification |
|---|------|-------|--------------------|
| 1 | View my ideas with status badges | Login as `initiator@e2e.test`. Navigate to `/projektidee/meine`. Verify `#ideas-container` is visible. Verify cards show status badges (submitted, approved, rejected, feedback_requested). Verify approved idea has link to project space. | — |
| 2 | Edit idea in modal | On `/projektidee/meine`, click edit button on a submitted idea. Verify `#edit-idea-modal` is visible. Change title. Save. Verify modal closes. Verify updated title visible in the card. | — |
| 3 | Withdraw idea | On `/projektidee/meine`, click withdraw button on a submitted idea. Verify `#withdraw-modal` appears. Confirm. Verify idea removed from list or marked as withdrawn. | — |

### `tests/e2e/specs/browser/moderation.spec.ts` — Moderator reviews (4 tests)

Uses `moderator@e2e.test`. Requires a submitted idea in the queue (either seeded or created in a prior test).

| # | Test | Steps | Email verification |
|---|------|-------|--------------------|
| 1 | View moderation queue | Login as moderator. Navigate to `/moderation`. Verify `#reviews-container` shows review cards/rows. Verify each shows idea title, submitter, category. | — |
| 2 | Approve idea | Click a review link → `/moderation/detail?id=X`. Verify idea details visible. Click approve button. Verify success indication. | Approval email: check Mailpit for email to submitter (`initiator@e2e.test`), verify subject contains "genehmigt" or idea title |
| 3 | Reject idea | Open a review detail. Click reject button. Fill `#reject-reason` in `#reject-modal`. Confirm. Verify success. | Rejection email: check Mailpit for email to submitter, verify body contains the rejection reason text |
| 4 | Request feedback | Open a review detail. Click feedback button. Fill notes. Submit. Verify success. | Feedback email: check Mailpit for email to submitter, verify body contains the feedback notes |

### `tests/e2e/specs/browser/project-spaces.spec.ts` — Space interaction (4 tests)

| # | Test | Steps | Email verification |
|---|------|-------|--------------------|
| 1 | Browse + filter + search | Navigate to `/projektraeume`. Verify `#project-spaces-grid` has cards. Click status filter button `[data-value="active"]`. Verify grid updates. Type in `#project-search`. Verify results filter. | — |
| 2 | Join space | Login as `user@e2e.test`. Navigate to active space `/projektraum/view/{e2e_space_active}`. Click join button. Verify confirmation or success. | Join request email: check Mailpit for email to initiator (`initiator@e2e.test`), verify subject mentions the space title |
| 3 | Write status update | Login as `initiator@e2e.test`. Navigate to own space. Click "+ Update schreiben" button. Fill `#update-title` in `#update-modal`. Click into CKEditor for update body, type content. Save. Verify update appears in `#status-update-timeline-container`. | Status update email: check Mailpit for email to space members, verify subject contains update title |
| 4 | Leave space | Login as member who joined. Navigate to space. Click leave button. Confirm. Verify user no longer in sidebar member list. | Member left email: check Mailpit for notification email |

### `tests/e2e/specs/browser/expert-flow.spec.ts` — Expert requests (2 tests)

| # | Test | Steps | Email verification |
|---|------|-------|--------------------|
| 1 | Request expert from space | Login as `initiator@e2e.test`. Navigate to own active space. Select expert from `#expert-select`. Submit request. Verify request appears in sidebar. | Expert request email: check Mailpit for email to `expert@e2e.test`, verify body mentions space title |
| 2 | Expert declines request | Login as `expert@e2e.test`. Navigate to `/expertanfragen`. Verify request card visible with space title. Click decline button. Verify card shows "Abgelehnt" status. | Decline email: check Mailpit for email to initiator, verify body mentions decline |

---

## Helper Functions

Added to `tests/e2e/fixtures/helpers.ts`:

### `loginViaUI(page, email, password)`
Fills the actual `/login` form and submits. Waits for redirect. Returns void (no API client — this is pure browser interaction). Different from `loginAs()` which uses the browser to get a session then returns an `AuthedApi` for REST calls.

### `fillCKEditor(page, selector, text)`
Finds the CKEditor instance associated with `selector` (the original textarea ID). Clicks the `.ck-editor__editable` contenteditable div inside the CKEditor wrapper. Types the text via `page.keyboard.type()`. Works for both idea description and status update body fields.

### `getMailpitMessage(request, to, subject, timeoutMs = 15000)`
Polls Mailpit API (`/api/v1/search`) for a message matching the `to` address and `subject` substring. Returns the full message object including HTML body. Throws if no match within timeout. Uses the same `MAILPIT_API` base URL from the existing mailpit helpers.

### `extractLinkFromEmail(messageHtml, pattern)`
Regex extracts a URL from the email HTML body matching the given pattern (e.g. `/\/register\/validate\?/` for validation links, `/\/login\/callback\/forgot_password\?/` for reset links). Returns the full URL string. Throws if no match.

---

## Test Fixture

### `tests/e2e/fixtures/test-upload.png`
A minimal 1x1 pixel transparent PNG file (~67 bytes). Used by `idea-submission.spec.ts` for the file upload test. Generated once, committed to the repo.

---

## Testing Guide Documentation

### New file: `docs/testing-guide.md`

### Content structure:

#### 1. Test Types at a Glance

| Type | Purpose | Catches | Speed | Runs in CI |
|------|---------|---------|-------|------------|
| **Unit** | Test domain logic, handlers, value objects in isolation | Logic bugs, business rule violations | Fast (~5s) | Stage 3 |
| **Integration** | Test handlers + real DB, event chains | Query bugs, column mismatches, event wiring | Medium (~30s) | Stage 5 |
| **API** | Test REST endpoint contracts via HTTP | Serialization, status codes, auth guards | Medium (~1m) | Stage 5 |
| **Browser E2E** | Simulate real user in browser | UI bugs, form validation, navigation, JS errors, email flows | Slow (~3m) | Stage 5 |
| **Storybook/Axe** | Accessibility audit per component | ARIA violations, contrast, keyboard traps | Medium (~1m) | Stage 3 |

#### 2. Decision Flowchart

```
I changed...
  ├─ a domain model method/entity → write UNIT test
  ├─ a repository SQL query → write INTEGRATION test
  ├─ an event handler → write UNIT test + INTEGRATION test (event chain)
  ├─ a use case handler → write UNIT test + INTEGRATION test
  ├─ an API controller response shape → write API test
  ├─ a page template / form / JS interaction → write BROWSER E2E test
  ├─ a component partial (.php) → verify STORYBOOK renders, check AXE
  ├─ an email template or notification → write test that checks MAILPIT content
  └─ SCSS / design tokens → run STORYBOOK, visual check
```

#### 3. Where Tests Live

```
tests/
  unit/                              ← Pure PHP, no CMS, mocked dependencies
    inno_project_idea/
      Domain/                        ← Domain model tests
      Application/                   ← Handler tests (Mockery mocks)
    inno_moderation/...
    inno_project_space/...
    inno_notification/...

  integration/                       ← Real DB, CMS bootstrapped
    inno_project_idea/
      Persistence/                   ← Repository tests
      UseCase/                       ← Handler + real repo tests
    inno_moderation/...
    inno_project_space/...
    shared/                          ← Cross-package event chain tests

  e2e/
    fixtures/
      helpers.ts                     ← Shared helpers (login, mailpit, CKEditor)
      test-upload.png                ← File upload fixture
    specs/
      00-smoke.spec.ts               ← Infrastructure health check
      browser/                       ← REAL USER browser tests
        auth.spec.ts
        idea-submission.spec.ts
        idea-management.spec.ts
        moderation.spec.ts
        project-spaces.spec.ts
        expert-flow.spec.ts
      storybook/                     ← Component accessibility tests
        axe.spec.ts
      *.spec.ts                      ← API-level contract tests (legacy naming)
```

#### 4. How to Run

| What | Command | Needs |
|------|---------|-------|
| Unit tests | `composer test` | Nothing |
| Unit + 80% coverage | `composer test:coverage` | Nothing |
| Integration tests | `composer test:integration` | Running CMS + DB |
| API tests (local) | `npm run test:e2e` | Running CMS |
| Browser E2E (local) | `npx playwright test tests/e2e/specs/browser/` | Running CMS |
| Storybook axe | `npm run storybook:build && npx playwright test tests/e2e/specs/storybook/` | Nothing |
| Everything in Docker | See "Full Docker" section below | Docker |
| Lint + type check | `npm run lint:all && npm run typecheck && composer lint:php` | Nothing |

**Full Docker (mirrors CI):**
```bash
docker compose -f docker-compose.test.yml up -d --build --wait
docker compose -f docker-compose.test.yml exec -T app composer test:integration
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e
docker compose -f docker-compose.test.yml down -v
```

#### 5. How to Write New Tests

Per test type with file naming convention, pattern to follow, and example reference file.

#### 6. CI Pipeline Stages

| Stage | What runs | Blocks merge on |
|-------|-----------|----------------|
| 1. Lint & Format | ESLint, Prettier, Stylelint, PHPStan, PHP CS Fixer, OpenAPI staleness, markdown-link-check | Any lint error |
| 2. Type Check | `tsc --noEmit` | Type errors |
| 3. Test & Coverage | Unit tests (80% min), production build, Storybook build + axe tests | Test failure or coverage < 80% |
| 4. Security | npm audit, composer audit, Semgrep SAST | High/critical vulns |
| 5. E2E & Integration | Docker env → integration tests → Playwright E2E (API + browser) → teardown | Any test failure |

#### 7. Test Data

Reference to `make seed` (dev data), `make seed-e2e` (E2E users + fixtures), test user table with roles/passwords, link to `docs/e2e-testing.md` for full seed data reference.

---

## What This Does NOT Include

- Avatar upload test (Vue cropper, very complex to automate)
- Password change test (low risk, simple form, covered by profile unit tests)
- Category management browser test (admin-only, covered by API tests)
- User management browser test (admin-only, covered by API tests)
- Moving existing API specs into `api/` subfolder (would break git blame for no functional gain)
