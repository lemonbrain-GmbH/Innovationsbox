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
      Persistence/                   <- Repository tests
      UseCase/                       <- Handler + real repo tests
    inno_moderation/
      Persistence/                   <- Repository tests
      UseCase/                       <- Handler + real repo tests
    inno_project_space/
      Persistence/                   <- Repository tests
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

### Linting and type checks
```bash
npm run lint:all           # ESLint + Stylelint + Prettier
npm run typecheck          # TypeScript (tsc --noEmit)
composer lint:php          # PHPStan + PHP CS Fixer
npm run lint:api           # Spectral OpenAPI linting
```

## How to Write New Tests

### Unit test (PHP)
1. Create file in `tests/unit/{package}/{Domain|Application}/YourTest.php`
2. Use Pest `test('description', function () { ... })` syntax
3. Mock all dependencies with Mockery
4. Reference: `tests/unit/inno_moderation/Domain/ModerationReviewTest.php`

### Integration test (PHP)
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
4. Reference: `tests/integration/inno_project_idea/UseCase/SubmitProjectIdeaIntegrationTest.php`

### API test (TypeScript)
1. Create file in `tests/e2e/specs/{nn}-{name}.spec.ts`
2. Login via `loginAs()` helper to get `AuthedApi`
3. Call REST endpoints and assert responses
4. Reference: `tests/e2e/specs/02-ideas.spec.ts`

### Browser E2E test (TypeScript)
1. Create file in `tests/e2e/specs/browser/{name}.spec.ts`
2. Use `loginViaUI()` to login through the actual form
3. Navigate to pages, fill forms, click buttons, verify visible results
4. Use `fillCKEditor()` for rich text fields
5. Use `getMailpitMessage()` to verify emails after actions
6. Use `extractLinkFromEmail()` to follow email links
7. Reference: `tests/e2e/specs/browser/auth.spec.ts`

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
| `expert2@e2e.test` | Expert (pending expert request in seed data) | `E2eTest!2026` |
| `admin@e2e.test` | Administrator | `E2eTest!2026` |
| `initiator@e2e.test` | Initiator (owns seeded data) | `E2eTest!2026` |

> Note: The pending expert request in the seed data belongs to `expert2@e2e.test`, not `expert@e2e.test`.

### Seeded data
- 3 categories: Energie, Mobilitat, Biodiversitat
- 6 ideas (one per status): draft, submitted, under_review, approved, rejected, feedback_requested
- 4 project spaces (one per status): draft, active, completed, archived
- 1 pending join request, 1 accepted membership, 1 pending expert request

See `docs/e2e-testing.md` for full details.
