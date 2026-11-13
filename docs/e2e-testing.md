# E2E Testing Guide

## Quick Start (Docker)

```bash
# Start the test environment
docker compose -f docker-compose.test.yml up -d --build --wait

# Run all E2E tests
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e

# Run a specific spec
E2E_DOCKER=true npx playwright test tests/e2e/specs/01-auth.spec.ts --project=e2e

# Tear down
docker compose -f docker-compose.test.yml down -v
```

## Quick Start (Local Dev)

Requires a running CMS at `$PROJECT_NAME.local:$APP_PORT`.

```bash
npx playwright test --config=tests/e2e/playwright.config.ts --project=chromium
```

## Test Users

| Email | Role | Password |
|-------|------|----------|
| `user@e2e.test` | Registered user | `E2eTest!2026` |
| `moderator@e2e.test` | Moderator | `E2eTest!2026` |
| `expert@e2e.test` | Expert | `E2eTest!2026` |
| `expert2@e2e.test` | Expert (pending expert request) | `E2eTest!2026` |
| `admin@e2e.test` | Admin | `E2eTest!2026` |
| `initiator@e2e.test` | Initiator (owns seeded data) | `E2eTest!2026` |

## Seed Data

The Docker environment automatically seeds:
- 3 categories (Energie, Mobilitat, Biodiversitat)
- 6 ideas (one per status: draft, submitted, under_review, approved, rejected, feedback_requested)
- 4 project spaces (one per status: draft, active, completed, archived)
- Members, status updates, collaboard links, expert requests on the active space

Reset seed data: `docker compose -f docker-compose.test.yml down -v && docker compose -f docker-compose.test.yml up -d --build --wait`

## Test Helpers

Located in `tests/e2e/fixtures/helpers.ts`:

- `loginAs(page, email, password)` — Login and return `AuthedApi` with CSRF token
- `submitIdea(api, data)` — Create an idea via API
- `seedIdea(api, overrides?)` — Quick idea creation for test setup
- `loginAsNewContext(browser, email, password)` — Login in a fresh browser context
- `registerUser(page, email, username, password)` — Register via form
- `searchEmails(request, query)` — Search Mailpit (Docker only)
- `waitForEmail(request, query, timeout?)` — Poll Mailpit for email
- `clearMailpit(request)` — Delete all Mailpit messages

## Writing New Tests

1. Create a new `.spec.ts` file in `tests/e2e/specs/`
2. Use `test.describe.serial()` for flows that depend on order
3. Use `loginAs()` in `test.beforeAll()` to set up authenticated API clients
4. Use `test.skip(!condition, 'reason')` for conditional execution
5. For multi-user tests, use `loginAsNewContext()` to get separate sessions
6. Use unique identifiers: `` `e2e-${Date.now()}` `` for test isolation

## Docker Environment Setup Notes

### Page creation order matters

The Docker entrypoint creates `/members/*`, `/members/directory`, and `/members/profile` pages **before** package installation. This is required so that when `inno_frontend` installs and calls `registerSinglePageOverrides()`, the pages already exist and the innovationsbox theme is correctly applied to them.

### Display names for E2E users

After seeding, `set-e2e-display-names.php` must be run to set a non-empty `display_name` for all E2E users. Without this, `ProfileCompletionChecker` redirects every request to the profile completion page, breaking all tests.

### Members pages config

`user.profiles_enabled` must be set to `true` via site config for `/members/*` pages to work. This is set by the Docker entrypoint alongside other site configuration before package install.

## CI Pipeline

E2E tests run in **Pipeline Stage 5** on every PR. The Docker environment is built fresh for each run.
