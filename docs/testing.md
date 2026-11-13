# Testing & Test Data

## Test Data Setup

The project includes a seed script that creates a reproducible set of test data for local development and test servers.

### Prerequisites

- CMS and all packages installed (`make install` or manual)
- Database tables exist (created automatically on package install/upgrade)
- `.env` file with valid `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`, `DB_HOSTNAME`

### Usage

```bash
make seed
```

Or directly:

```bash
bash scripts/seed-testdata.sh

# With Docker:
DOCKER=true PROJECT_NAME=innovationsbox bash scripts/seed-testdata.sh
```

### What Gets Created

| Table | Records | Details |
|-------|---------|---------|
| `ib_categories` | 5 | Bauen, Wohnen, Landwirtschaft, Tourismus, Mobilität |
| `ib_project_spaces` | 6 | 3 active, 1 completed, 1 archived, 1 draft |
| `ib_project_space_members` | 20 + 1 pending | Initiator + developers per space, 1 pending join request |
| `ib_project_space_updates` | 9 | 2-3 updates per active/completed space |
| `ib_project_space_collaboards` | 4 | 1-2 boards per active space |

### Deterministic IDs

All IDs are fixed strings for test referencing:

- Spaces: `test_space_1` through `test_space_6`
- Categories: `cat_bauen`, `cat_wohnen`, `cat_landwirtschaft`, `cat_tourismus`, `cat_mobilitaet`
- Members: `mem_{spaceNum}_{memberNum}` (e.g., `mem_1_3`)
- Updates: `upd_{spaceNum}_{updateNum}` (e.g., `upd_1_2`)

### Warning

**This script TRUNCATES all `ib_*` tables.** Never run on production. All existing project data will be deleted.

### E2E Test Data

For E2E tests (Docker or local), use the dedicated E2E seed target:

```bash
make seed-e2e
```

This creates 6 test users (`user@e2e.test`, `moderator@e2e.test`, `expert@e2e.test`, `expert2@e2e.test`, `admin@e2e.test`, `initiator@e2e.test`), 3 categories, 6 ideas (one per status), and 4 project spaces (one per status). See `docs/e2e-testing.md` for full details.

## Running Tests

### Unit Tests

```bash
composer test              # Run all unit tests (also enforces 80% line coverage)
composer test:unit         # Same, without coverage report
composer test:coverage     # With coverage report (80% minimum enforced)
```

The `composer test` script runs `pest --coverage --min=80`. If line coverage on `public/packages/inno_*/src/` drops below 80 %, the command exits non-zero and CI fails.

#### Event handler tests

Each `Application/EventHandler/` class has a corresponding test in `tests/unit/<package>/Application/`. 9 event handler test files cover all 9 registered listeners across the four domain packages:

| Test file | Handler under test |
|-----------|-------------------|
| `inno_project_idea/Application/OnModerationDecisionMadeTest.php` | `OnModerationDecisionMade` |
| `inno_project_idea/Application/OnModerationFeedbackRequestedTest.php` | `OnModerationFeedbackRequested` |
| `inno_project_idea/Application/OnProjectSpaceCreatedTest.php` | `OnProjectSpaceCreated` |
| `inno_project_space/Application/OnProjectIdeaApprovedTest.php` | `OnProjectIdeaApproved` |
| `inno_moderation/Application/OnProjectIdeaSubmittedTest.php` | `OnProjectIdeaSubmitted` |
| `inno_moderation/Application/OnProjectIdeaResubmittedTest.php` | `OnProjectIdeaResubmitted` |
| `inno_notification/Application/OnProjectIdeaSubmittedTest.php` | `OnProjectIdeaSubmitted` |
| `inno_notification/Application/OnProjectIdeaApprovedTest.php` | `OnProjectIdeaApproved` |
| `inno_notification/Application/OnProjectIdeaRejectedTest.php` | `OnProjectIdeaRejected` |

### Integration Tests

Integration tests exercise real adapters against an actual database and a booted
Concrete CMS application. They live under `tests/integration/` and use the
[Pest](https://pestphp.com/) framework, matching the same style as unit tests.

#### Prerequisites

1. CMS installed: `make install` (or manual CMS installation)
2. All five packages installed and activated in the CMS back-end
3. `.env` configured with valid `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`,
   `DB_HOSTNAME` pointing to a running database
4. `ib_*` tables exist (created automatically during package install/upgrade)

#### Running

```bash
composer test:integration
```

This runs `pest --configuration=phpunit.integration.xml --testsuite=Integration`,
which uses `tests/integration/bootstrap.php` to boot the full CMS application.

If `public/concrete` is not present, the bootstrap exits gracefully with a
descriptive message — no tests run and the process exits `0`.

#### Legacy script runner

`tests/integration/run_integration_tests.php` is an older script-based runner
for the cross-cutting-concern adapters (config, logger, cache, transaction,
health-check, metrics, auth). It still works and can be invoked via:

```bash
php public/concrete/bin/concrete5 c5:exec tests/integration/run_integration_tests.php
```

New integration tests should be written as Pest tests, not added to that script.

#### Transaction-rollback isolation

Each test wraps database mutations in a transaction that is rolled back in
`afterEach()`. This keeps the database clean between tests without truncating
tables and is safe to run against a development database that already contains
data.

```php
beforeEach(function (): void {
    $this->db = app(\Concrete\Core\Database\Connection\Connection::class);
    $this->db->beginTransaction();
    $this->repo = new ConcreteFooRepository($this->db);
});

afterEach(function (): void {
    $this->db->rollBack();
});
```

The pattern is demonstrated in full in
`tests/integration/inno_moderation/Persistence/ConcreteModerationReviewRepositoryTest.php`.

#### Adding new integration tests

- Mirror the `tests/unit/` directory structure under `tests/integration/`.
- Resolve dependencies from the CMS container using `app(SomeClass::class)`.
- Always use the transaction-rollback pattern for database tests.
- Do not call `$this->db->commit()` inside a test — let `afterEach` roll back.
- Tests that cannot meaningfully run without a CMS should be placed here, not in
  the unit suite.

### E2E Tests

```bash
make seed-e2e              # Seed E2E test data (users, ideas, spaces)
npm run test:e2e           # Run Playwright tests (requires running CMS)
```

In Docker, seeding and environment setup are handled automatically by the entrypoint. See `docs/e2e-testing.md` for the Docker workflow.

#### Storybook smoke tests

The Playwright spec files under `tests/e2e/specs/storybook/` cover all 63 components (24 atoms + 26 molecules + 9 organisms + 2 templates + 2 pages). They run against the Storybook dev server and are included in the CI PR pipeline (`npm run test:ci`). No running CMS is required for these tests.

### Full CI Suite (local)

```bash
npm run test:ci            # Lint + typecheck + unit tests + coverage + build + storybook smoke
```
