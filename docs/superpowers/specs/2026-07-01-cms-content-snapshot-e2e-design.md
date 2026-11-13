# CMS Content Snapshot for E2E Testing

**Date:** 2026-07-01
**Status:** Approved

## Problem

The Docker E2E test environment installs CMS + packages and seeds `ib_*` app tables with mock data, but has no CMS content layer — pages, blocks, navigation, hero images, footer, theme settings are all missing. This causes:

- Screenshots that look nothing like the real site (bare skeleton vs styled pages)
- Tests that can't verify visual correctness of the full user experience
- A test environment that doesn't match the dev environment

## Solution

Two-part approach: a snapshot script that captures CMS content from the dev DB, and an optional volume mount for uploaded files.

### Part 1: Snapshot Script

**New file:** `scripts/snapshot-dev-for-e2e.sh`

- Dumps the local dev MySQL database via `mysqldump`
- **Strips all `ib_*` tables** from the dump (these are reseeded by existing mock data scripts)
- Saves to `docker/dev-db-dump.sql`
- The dump file is **committed to the repo** (un-gitignored) so the pipeline gets it
- Run manually whenever CMS content changes (new pages, blocks, navigation updates)

**Tables stripped from dump (9):**
- `ib_categories`
- `ib_project_ideas`
- `ib_idea_attachments`
- `ib_moderation_reviews`
- `ib_project_spaces`
- `ib_project_space_members`
- `ib_project_space_updates`
- `ib_project_space_collaboards`
- `ib_expert_requests`

### Part 2: Files Volume Mount (local only)

**New file:** `docker-compose.test.override.yml` (gitignored, created locally)

- Binds `./public/application/files/` read-only into the container
- Local: screenshots show real images/avatars
- Pipeline: no mount, files folder empty — CMS works, images show placeholders

**Example override:**
```yaml
services:
  app:
    volumes:
      - ./public/application/files:/var/www/html/public/application/files:ro
```

### Part 3: Verify All E2E Tests Pass

- Run every E2E test spec against the Docker environment with CMS content loaded
- Fix any tests that fail or are flaky due to the new content-rich environment
- Ensure tests are robust against realistic CMS state (navigation, themed pages, etc.)

## File Changes

| File | Action | Purpose |
|------|--------|---------|
| `scripts/snapshot-dev-for-e2e.sh` | Create | Dump dev DB minus `ib_*` tables |
| `docker/dev-db-dump.sql` | Un-gitignore | Committed CMS content snapshot |
| `.gitignore` | Edit | Remove `docker/dev-db-dump.sql` line |
| `docker-compose.test.yml` | No change | Existing fast-path handles the dump |
| `docker-compose.test.override.yml` | Document | Gitignored, local-only files mount |
| `docker/test-entrypoint.sh` | Review/tweak | Ensure fast-path handles packages correctly after dump import |
| `tests/e2e/specs/*.spec.ts` | Fix as needed | Make all tests pass against realistic CMS content |

## What Stays the Same

- All `ib_*` table seeding (mock data scripts are single source of truth for app data)
- E2E user creation and display name setup
- Playwright config and test execution model
- Pipeline workflow (just picks up the committed dump)

## Workflow

```
Developer updates CMS content locally
    |
./scripts/snapshot-dev-for-e2e.sh       <-- run manually
    |
docker/dev-db-dump.sql updated          <-- commit + push
    |
docker compose -f docker-compose.test.yml up -d --wait
    |
Container imports dump -> seeds ib_* tables -> ready
    |
E2E tests run against realistic CMS content
```
