# Comprehensive QA Infrastructure & Documentation Audit

**Date:** 2026-06-09
**Status:** Approved
**Scope:** Docker test environment, full E2E coverage, integration tests, documentation/OpenAPI/accessibility audit

## Context

The Innovationsbox platform has solid unit test coverage (69 files, all use-case handlers and event handlers tested) and 13 existing E2E specs. However, several gaps prevent confident release-gating:

- **Integration tests:** Only 1 file exists (moderation repository)
- **E2E gaps:** No tests for register, forgot password, edit/delete idea, join request, invite member, Collaboard links, space status transitions
- **No isolated test environment:** E2E tests require a manually configured local CMS instance
- **OpenAPI:** CategoryApiController missing annotations
- **Accessibility:** 56% of components lack explicit ARIA attributes
- **Documentation drift:** No CI enforcement that docs stay in sync with code

## Decisions

- **Docker-based CI (Approach C):** `docker-compose.test.yml` spins up CMS + DB + mailpit, runs all tests, tears down. For local dev, API-seeded tests against a running CMS (Approach A).
- **Phased execution (Approach C):** Foundation first, then E2E, then docs/a11y, then integration tests.
- **All 30 user flows are release-blocking.**
- **Pipeline Stage 5** for E2E + integration tests.
- **Living documentation:** CI checks enforce OpenAPI spec freshness, markdown link validity, and Storybook build health.

---

## Phase 1: Docker Test Environment + Seed + Pipeline

### Docker Compose (`docker-compose.test.yml`)

**Services:**

| Service | Image | Purpose |
|---------|-------|---------|
| `db` | MySQL 8.0 | Test database (`innovationsbox_test`), health-checked with `mysqladmin ping` |
| `app` | Custom (PHP 8.2 + Apache) | Built from `Dockerfile.test`. Runs `composer install`, `c5:install`, installs all 5 packages, runs `make seed-e2e` |
| `mailpit` | `axllent/mailpit` | SMTP capture server with REST API for querying captured emails |

**Startup sequence:**
1. `db` starts and becomes healthy
2. `app` waits for `db`, bootstraps CMS with test config, installs packages, seeds data
3. `mailpit` starts independently
4. Playwright runs against `http://app:80`

### Dockerfile.test

Based on `php:8.2-apache`. Installs:
- PHP extensions: pdo_mysql, gd, intl, zip, mbstring (Concrete CMS requirements)
- Composer dependencies
- Node.js + npm for frontend build
- Apache mod_rewrite enabled

Build steps:
1. Copy project files
2. `composer install --no-interaction`
3. `npm ci && npm run production`
4. Entrypoint script handles CMS install + seed on first run

### Seed System (`make seed-e2e`)

Extends the existing `make seed` Makefile target.

**Test users:**

| Email | Role | Password |
|-------|------|----------|
| `user@e2e.test` | Registered user | `E2eTest!2026` |
| `moderator@e2e.test` | Moderator | `E2eTest!2026` |
| `expert@e2e.test` | Expert (flagged) | `E2eTest!2026` |
| `admin@e2e.test` | Admin | `E2eTest!2026` |
| `initiator@e2e.test` | Registered user (owns pre-seeded data) | `E2eTest!2026` |

**Pre-seeded data:**
- 3 categories: Energie, Mobilitat, Biodiversitat
- 6 ideas (one per status): draft, submitted, under_review, approved, rejected, feedback_requested — all owned by `initiator@e2e.test`
- 4 project spaces (one per status): draft, active, completed, archived — derived from approved ideas
- 1 pending join request on the active space
- 1 accepted membership on the active space
- 1 pending expert request on the active space

**Register flow** uses `e2e-{timestamp}@test.local` for fresh user creation (no seed collision).

### Pipeline Stage 5

Added to `bitbucket-pipelines.yml` after the existing 4 stages:

```yaml
- step:
    name: "E2E & Integration Tests"
    caches: [node, docker]
    services: [docker]
    size: 2x  # larger runner for Docker + Playwright
    script:
      - docker compose -f docker-compose.test.yml up -d --wait
      - docker compose -f docker-compose.test.yml exec app composer test:integration
      - npx playwright install chromium
      - npx playwright test --project=e2e
      - docker compose -f docker-compose.test.yml down -v
```

**Smoke test (`tests/e2e/specs/00-smoke.spec.ts`):** Logs in as each role, hits one endpoint per package. Validates the Docker environment is functional before the full suite runs.

---

## Phase 2: E2E Test Coverage — All 30 Flows

### Test Organization

```
tests/e2e/specs/
  00-smoke.spec.ts           (infrastructure validation)
  01-auth.spec.ts            (flows 1-6)
  02-ideas.spec.ts           (flows 7-11)
  03-moderation.spec.ts      (flows 12-15)
  04-project-spaces.spec.ts  (flows 16-27)
  04b-experts.spec.ts        (flows 28-29)
  05-admin.spec.ts           (flows 30-31)
  06-notifications.spec.ts   (flow 32)
```

### Helper Extensions (`fixtures/helpers.ts`)

New helpers added to existing file:
- `registerUser(page, email, password)` — fills register form, returns AuthedApi
- `resetPassword(page, email)` — triggers forgot password flow
- `seedIdea(api, overrides?)` — quick API-based idea creation
- `seedSpace(api, overrides?)` — quick API-based space creation
- `waitForEmail(api, recipient, subject, timeout)` — polls mailpit API until matching email arrives
- `emailLogsContain(logs, substring)` — searches email content (case-insensitive)

### Test Specifications

#### `01-auth.spec.ts` — Authentication (6 tests)

| # | Test | User | Validates |
|---|------|------|-----------|
| 1 | Register new account | fresh `e2e-{ts}@test.local` | Form submission, welcome email via mailpit, can login after |
| 2 | Login with valid credentials | `user@e2e.test` | Redirect to dashboard/home |
| 3 | Login with invalid credentials | wrong password | Error message shown, no redirect |
| 4 | Logout | `user@e2e.test` | Session destroyed, redirected to login |
| 5 | Forgot password + reset | `user@e2e.test` | Email received via mailpit, reset link works, new password accepted |
| 6 | Profile completion on first login | fresh user | Required fields enforced, profile saved |

#### `02-ideas.spec.ts` — Idea Lifecycle (5 tests)

| # | Test | User | Validates |
|---|------|------|-----------|
| 7 | Submit idea with attachment | `user@e2e.test` | POST /api/project-idea succeeds, appears in "my ideas" |
| 8 | Edit own idea | `user@e2e.test` | PUT /api/project-idea/{id}, fields updated |
| 9 | Delete own idea | `user@e2e.test` | DELETE /api/project-idea/{id}, removed from list |
| 10 | View my ideas list | `initiator@e2e.test` | GET /api/project-idea/mine returns all statuses |
| 11 | Resubmit after feedback | `initiator@e2e.test` | Uses seeded feedback_requested idea, status returns to submitted |

#### `03-moderation.spec.ts` — Moderation (4 tests)

| # | Test | User | Validates |
|---|------|------|-----------|
| 12 | View moderation queue | `moderator@e2e.test` | GET /api/moderation returns submitted ideas |
| 13 | Approve idea | `moderator@e2e.test` | POST approve, idea status changes, project space auto-created |
| 14 | Reject idea with notes | `moderator@e2e.test` | POST reject, idea status changes |
| 15 | Request feedback with deadline | `moderator@e2e.test` | POST request-feedback, idea status changes, deadline set |

#### `04-project-spaces.spec.ts` — Project Spaces (13 tests)

| # | Test | User | Validates |
|---|------|------|-----------|
| 16 | Browse spaces | visitor | GET /api/project-space returns list with pagination |
| 17 | Search/filter by category + status | visitor | Query params filter correctly |
| 18 | View space detail | `user@e2e.test` | GET /api/project-space/{id} returns members, updates, links |
| 19 | Request to join | `user@e2e.test` | POST join-request, appears for initiator |
| 20 | Accept join request | `initiator@e2e.test` | PUT join-request/{id} accept, member added |
| 21 | Reject join request | `initiator@e2e.test` | PUT join-request/{id} reject, requester notified |
| 22 | Invite member | `initiator@e2e.test` | POST invite, invitee notified |
| 23 | Remove member | `initiator@e2e.test` | DELETE members/{userId}, member removed |
| 24 | Leave space | `user@e2e.test` | POST leave, membership removed |
| 25 | Status transitions | `initiator@e2e.test` | PUT status: draft -> active -> completed -> archived |
| 26 | Status update CRUD | `initiator@e2e.test` | POST, PUT, DELETE status-update |
| 27 | Collaboard link CRUD | `initiator@e2e.test` | POST + DELETE collaboard link |
| 28 | Request expert + accept | `initiator@e2e.test` + `expert@e2e.test` | POST request-expert, PUT accept |
| 29 | Expert declines request | `expert@e2e.test` | PUT decline, initiator notified |

#### `05-admin.spec.ts` — Admin Operations (2 tests)

| # | Test | User | Validates |
|---|------|------|-----------|
| 30 | Category CRUD | `admin@e2e.test` | POST create, PUT update, DELETE, verify gone |
| 31 | User management | `admin@e2e.test` | List users, toggle expert flag on/off |

#### `06-notifications.spec.ts` — Email Lifecycle (1 test)

| # | Test | User | Validates |
|---|------|------|-----------|
| 32 | Full lifecycle emails | multiple roles | Submit idea -> moderator email -> approve -> author email -> join request -> initiator email. Queries mailpit after each step. |

### Test Isolation Strategy

- Each spec file is independent: uses seeded data or creates its own via API helpers
- Tests that modify shared data (delete, remove) operate on data they create, not seeded data
- `beforeAll` per spec: login as the needed role, store AuthedApi instance
- No cleanup needed: Docker environment is disposable per CI run
- Local dev: `make seed-e2e` resets to known state before each test run

---

## Phase 3: Documentation & Accessibility

### OpenAPI — Fill Gaps

**Missing annotations:**
- `CategoryApiController`: Add `#[OA\Get]`, `#[OA\Post]`, `#[OA\Put]`, `#[OA\Delete]` for all 4 CRUD endpoints with request/response schemas
- Audit for any other undocumented endpoints (user-related)

**CI staleness check (Pipeline Stage 1):**
```bash
composer openapi:generate
git diff --exit-code docs/api/openapi.yaml docs/api/openapi.json
```
Fails the build if annotations changed but spec wasn't regenerated.

### Accessibility — Close ARIA Gaps

**High priority (interactive components):**
- `skip-link`: Add `aria-label`, fix focus management (bug)
- `image`: Enforce `alt` attribute (empty for decorative, descriptive for meaningful)
- `overlay`: Add `aria-hidden` when inactive, focus trap when active
- `auth-card`: Add heading association or landmark role
- `member-card`, `join-request-card`, `project-card`, `page-card`: Add meaningful `aria-label` or heading structure

**Medium priority (structural components):**
- `content-block`: Add `role="main"` or `<main>` on primary content area
- `hero-image`: Decorative image handling (`aria-hidden` or empty `alt`)
- `page-list-grid`: Add `aria-label` on the list region
- `status-update-timeline`: Add `aria-label` on timeline, semantic list markup
- `collaboard-panel`: Add `aria-label` for the panel region
- `project-space-sidebar`: Add landmark role or `aria-label`

**Low priority (presentational):**
- `container`, `divider`, `code`, `caption`, `paragraph`, `accent-text`, `partner-logo`: Verify semantic HTML, add ARIA only where it aids screen readers

**CI enforcement:**
- Review `disabledRules` in axe.spec.ts — remove any that aren't strictly necessary for component-level isolation
- Ensure `aria-required-attr` and `aria-valid-attr` are in the enforced ruleset

### Markdown Documentation

**Automated CI checks (Pipeline Stage 1):**
- `markdown-link-check` on all `*.md` files (add as dev dependency)
- Storybook build verification (already exists, ensure it's enforced)

**Manual audit tasks:**
- Cross-reference `CLAUDE.md` package table against actual controllers/events/use cases
- Update `docs/testing.md` to document Docker E2E infrastructure
- Update `README.md` with new commands: `docker compose` test env, `make seed-e2e`
- Update `TESTING.md` to document the full three-level test strategy
- Verify `docs/cross-cutting-concerns/` files match current implementations

**New documentation:**
- `docs/e2e-testing.md`: How to run E2E tests locally and in Docker, how to write new specs, helper API reference, mailpit usage, seed data reference

---

## Phase 4: Integration Tests

### Test Organization

```
tests/integration/
  moderation/
    ConcreteModerationReviewRepositoryTest.php  (exists)
    ManageCategoryIntegrationTest.php
    ReviewProjectIdeaIntegrationTest.php
  project_idea/
    ConcreteProjectIdeaRepositoryTest.php
    SubmitProjectIdeaIntegrationTest.php
    EditProjectIdeaIntegrationTest.php
    DeleteProjectIdeaIntegrationTest.php
  project_space/
    ConcreteProjectSpaceRepositoryTest.php
    JoinRequestFlowIntegrationTest.php
    ExpertRequestFlowIntegrationTest.php
    SpaceStatusTransitionTest.php
    StatusUpdateIntegrationTest.php
    MembershipIntegrationTest.php
  notification/
    EmailDeliveryIntegrationTest.php
    EventToEmailChainTest.php
  shared/
    EventDispatchIntegrationTest.php
```

### Test Categories

**Repository tests (one per package):** Verify `Adapter/Out/Persistence/` implementations correctly read/write MySQL. Insert via repository, query back, assert field mapping. Catches column renames, type mismatches, and query bugs that unit test mocks hide.

**Use case integration tests:** Run full handler with real repositories (no mocks), skip HTTP. Example: `SubmitProjectIdeaIntegrationTest` calls `SubmitProjectIdeaHandler->handle()` with real `ConcreteProjectIdeaRepository`, verifies idea persisted and `ProjectIdeaSubmitted` event dispatched.

**Cross-package event chain tests (`EventDispatchIntegrationTest`):**
- `ProjectIdeaSubmitted` -> moderation review created + submitter email sent
- `ProjectIdeaApproved` -> project space auto-created
- `ModerationDecisionMade` -> idea status updated
- `JoinRequestReceived` -> initiator email sent

**Email delivery test:** Uses mailpit SMTP to verify notification handlers produce correctly formatted emails with right recipients, subjects, and template rendering.

**Space status transition test:** Verifies full state machine (draft -> active -> completed -> archived) at domain/application level, including that invalid transitions are rejected.

### Test Infrastructure

**Bootstrap (`tests/integration/bootstrap.php`):**
- Boots the Concrete CMS application container
- Connects to test MySQL database
- Wraps each test in a database transaction that rolls back after (fast isolation, no leftover data)

**Running:**
```bash
composer test:integration                                                      # Local (requires running DB)
docker compose -f docker-compose.test.yml exec app composer test:integration   # In Docker
```

**Pipeline:** Runs in Stage 5 before Playwright (faster, fails early):
```yaml
- step:
    name: "E2E & Integration Tests"
    caches: [node, docker]
    services: [docker]
    size: 2x
    script:
      - docker compose -f docker-compose.test.yml up -d --wait
      - docker compose -f docker-compose.test.yml exec app composer test:integration
      - npx playwright install chromium
      - npx playwright test --project=e2e
      - docker compose -f docker-compose.test.yml down -v
```

---

## Summary

| Phase | Deliverables | Dependencies |
|-------|-------------|--------------|
| **1. Foundation** | `docker-compose.test.yml`, `Dockerfile.test`, `make seed-e2e`, Pipeline Stage 5, `00-smoke.spec.ts` | None |
| **2. E2E Coverage** | 32 release-blocking E2E tests across 7 spec files, extended helpers | Phase 1 |
| **3. Docs & A11y** | OpenAPI annotations, ARIA fixes, markdown audit, CI staleness checks, `docs/e2e-testing.md` | Independent (can overlap with Phase 2) |
| **4. Integration** | 16 integration test files, `bootstrap.php`, event chain tests | Phase 1 (Docker env) |

**Total new test files:** 7 E2E specs + 16 integration tests + 1 smoke test = 24 files
**Total release-blocking test cases:** 32 (covering all 30 user flows)
**CI pipeline:** 5 stages (lint -> typecheck -> test/coverage -> security -> E2E/integration)
