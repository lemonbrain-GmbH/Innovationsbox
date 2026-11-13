# CMS Content Snapshot for E2E Testing — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the Docker E2E environment a 1:1 clone of dev by snapshotting CMS content (DB + files), while keeping `ib_*` app data seeded from mock scripts.

**Architecture:** A snapshot script dumps the local dev DB excluding all `ib_*` tables and saves it as `docker/dev-db-dump.sql` (committed to repo). The existing `test-entrypoint.sh` fast path already imports this dump, then the seed scripts overlay mock data. For uploaded files, a local-only Docker Compose override mounts `application/files/` read-only into the container.

**Tech Stack:** Bash (mysqldump), Docker Compose override, Playwright E2E tests

## Global Constraints

- The `ib_*` tables are NEVER included in the dump — they are always seeded by `scripts/seed-e2e-data.sh`
- The `docker/dev-db-dump.sql` file is committed to the repo (un-gitignored)
- The `application/files/` volume mount is local-only (not in pipeline)
- `docker-compose.test.override.yml` is gitignored
- All existing E2E tests must pass after changes
- The entrypoint must also handle the dev dump path installing packages that exist on disk but not in the dump's DB

---

### Task 1: Create the Snapshot Script + Un-gitignore the Dump

**Files:**
- Create: `scripts/snapshot-dev-for-e2e.sh`
- Modify: `.gitignore:58-59` (remove the `docker/dev-db-dump.sql` exclusion)
- Modify: `docker-compose.test.yml` (add comment about override file)
- Create: `docker-compose.test.override.example.yml` (documented example, committed)

**Interfaces:**
- Consumes: Local dev DB credentials from `.env` (DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_HOSTNAME), `scripts/helpers/lib.sh` for `load_env_upwards()`
- Produces: `docker/dev-db-dump.sql` — a MySQL dump of all tables EXCEPT `ib_*` tables

- [ ] **Step 1: Create the snapshot script**

Create `scripts/snapshot-dev-for-e2e.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

# Snapshots the local dev database for the E2E Docker test environment.
# Excludes all ib_* tables (these are seeded by scripts/seed-e2e-data.sh).
#
# Usage: bash scripts/snapshot-dev-for-e2e.sh
# Requires: .env with DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_HOSTNAME

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
source "$SCRIPT_DIR/helpers/lib.sh"
load_env_upwards

DB_HOST="${DB_HOSTNAME:-127.0.0.1}"
DB_USER="${DB_USERNAME:?DB_USERNAME not set}"
DB_PASS="${DB_PASSWORD:?DB_PASSWORD not set}"
DB_NAME="${DB_DATABASE:?DB_DATABASE not set}"
DOCKER="${DOCKER:-false}"
DUMP_FILE="$REPO_ROOT/docker/dev-db-dump.sql"

echo "=== Innovationsbox Dev → E2E Snapshot ==="
echo "Source: $DB_NAME @ $DB_HOST"
echo "Target: $DUMP_FILE"
echo ""

# Discover all ib_* tables to exclude
echo "Discovering ib_* tables to exclude..."
if [[ "$DOCKER" == "true" ]]; then
  IB_TABLES=$(docker exec -i "${PROJECT_NAME}_mysql" \
    mysql -u"$DB_USER" -p"$DB_PASS" -N -e \
    "SELECT table_name FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name LIKE 'ib_%';" "$DB_NAME" 2>/dev/null)
else
  IB_TABLES=$(mysql -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" --skip-ssl -N -e \
    "SELECT table_name FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name LIKE 'ib_%';" "$DB_NAME" 2>/dev/null)
fi

IGNORE_ARGS=""
for table in $IB_TABLES; do
  echo "  Excluding: $table"
  IGNORE_ARGS="$IGNORE_ARGS --ignore-table=$DB_NAME.$table"
done

echo ""
echo "Dumping database (excluding ib_* tables)..."
if [[ "$DOCKER" == "true" ]]; then
  docker exec -i "${PROJECT_NAME}_mysql" \
    mysqldump -u"$DB_USER" -p"$DB_PASS" --no-tablespaces $IGNORE_ARGS "$DB_NAME" > "$DUMP_FILE"
else
  mysqldump -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" --skip-ssl --no-tablespaces $IGNORE_ARGS "$DB_NAME" > "$DUMP_FILE"
fi

# Also dump the ib_* table STRUCTURES (CREATE TABLE only, no data)
# so the seed scripts can INSERT into them
echo "Dumping ib_* table structures (no data)..."
if [[ -n "$IB_TABLES" ]]; then
  IB_TABLE_LIST=$(echo "$IB_TABLES" | tr '\n' ' ')
  if [[ "$DOCKER" == "true" ]]; then
    docker exec -i "${PROJECT_NAME}_mysql" \
      mysqldump -u"$DB_USER" -p"$DB_PASS" --no-tablespaces --no-data "$DB_NAME" $IB_TABLE_LIST >> "$DUMP_FILE"
  else
    mysqldump -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" --skip-ssl --no-tablespaces --no-data "$DB_NAME" $IB_TABLE_LIST >> "$DUMP_FILE"
  fi
fi

DUMP_SIZE=$(wc -c < "$DUMP_FILE" | xargs)
echo ""
echo "=== Snapshot Complete ==="
echo "File: $DUMP_FILE ($DUMP_SIZE bytes)"
echo ""
echo "Next steps:"
echo "  1. git add docker/dev-db-dump.sql"
echo "  2. git commit -m 'chore: update dev DB snapshot for E2E'"
echo "  3. docker compose -f docker-compose.test.yml up -d --build --wait"
```

- [ ] **Step 2: Make the script executable**

Run: `chmod +x scripts/snapshot-dev-for-e2e.sh`

- [ ] **Step 3: Un-gitignore `docker/dev-db-dump.sql`**

In `.gitignore`, remove lines 58-59:

```
# Dev DB dump for E2E (generated per developer, not committed)
docker/dev-db-dump.sql
```

- [ ] **Step 4: Create the Docker Compose override example**

Create `docker-compose.test.override.example.yml`:

```yaml
# Local-only overrides for E2E testing.
# Copy this file to docker-compose.test.override.yml to activate.
# The override file is gitignored — it stays local.
#
# Usage:
#   cp docker-compose.test.override.example.yml docker-compose.test.override.yml
#   docker compose -f docker-compose.test.yml -f docker-compose.test.override.yml up -d --wait

services:
  app:
    volumes:
      - ./public/application/files:/var/www/html/public/application/files:ro
```

- [ ] **Step 5: Add override file to `.gitignore`**

Add to `.gitignore` after the existing Docker-related entries:

```
# Local-only Docker Compose override (not committed)
docker-compose.test.override.yml
```

- [ ] **Step 6: Add Makefile target**

Add to `Makefile` after the `seed-e2e` target:

```makefile
snapshot-e2e:
	@echo "Snapshotting dev DB for E2E (excluding ib_* tables)..."
	@bash scripts/snapshot-dev-for-e2e.sh
```

- [ ] **Step 7: Update CLAUDE.md with snapshot workflow**

In the `#### E2E with Docker test environment` section of `CLAUDE.md`, add after the existing Docker commands:

```markdown
Update the CMS content snapshot from your local dev DB:
```bash
make snapshot-e2e                                         # Dump dev DB (minus ib_* app tables)
git add docker/dev-db-dump.sql && git commit -m "chore: update dev DB snapshot for E2E"
```

For local testing with uploaded files (images, avatars):
```bash
cp docker-compose.test.override.example.yml docker-compose.test.override.yml
docker compose -f docker-compose.test.yml -f docker-compose.test.override.yml up -d --wait
```
```

- [ ] **Step 8: Run the snapshot script against the local dev DB**

Run: `make snapshot-e2e`
Expected: `docker/dev-db-dump.sql` is created/updated with all tables except `ib_*` data

- [ ] **Step 9: Commit**

```bash
git add scripts/snapshot-dev-for-e2e.sh .gitignore docker-compose.test.override.example.yml Makefile CLAUDE.md docker/dev-db-dump.sql
git commit -m "feat: add dev DB snapshot script for realistic E2E environment"
```

---

### Task 2: Update Entrypoint for Reliable Dev Dump Import

The existing fast path in `docker/test-entrypoint.sh` (lines 38-58) needs a small adjustment: after importing a dev dump, packages may not be in the DB if the dump came from a dev environment where packages were installed differently. The entrypoint should install any missing packages after import.

**Files:**
- Modify: `docker/test-entrypoint.sh:55-57` (package installation after dev dump import)

**Interfaces:**
- Consumes: `docker/dev-db-dump.sql` (produced by Task 1)
- Produces: A fully initialized CMS with all packages installed

- [ ] **Step 1: Update the dev dump fast path in the entrypoint**

In `docker/test-entrypoint.sh`, replace lines 55-57 (the current package refresh block):

```bash
    # Ensure packages are installed (they exist on disk, dev dump has their DB state)
    echo "=== Refreshing packages ==="
    /var/www/html/public/concrete/bin/concrete c5:entities:refresh --allow-as-root 2>/dev/null || true
```

With:

```bash
    # Install packages that exist on disk but may not be in the dev dump's DB
    echo "=== Installing/refreshing packages ==="
    PACKAGES="inno_shared inno_frontend inno_moderation inno_project_idea inno_project_space inno_notification"
    for pkg in $PACKAGES; do
      # Check if package is already registered in the database
      PKG_COUNT=$(mysql -h "$DB_HOSTNAME" -u"$DB_USERNAME" -p"$DB_PASSWORD" $MYSQL_SSL \
        -N -e "SELECT COUNT(*) FROM Packages WHERE pkgHandle='$pkg';" "$DB_DATABASE" 2>/dev/null || echo "0")
      if [ "$PKG_COUNT" -eq 0 ]; then
        echo "Installing missing package: $pkg"
        /var/www/html/public/concrete/bin/concrete c5:package:install "$pkg" --allow-as-root
      else
        echo "Package already installed: $pkg"
      fi
    done
    /var/www/html/public/concrete/bin/concrete c5:entities:refresh --allow-as-root 2>/dev/null || true
```

- [ ] **Step 2: Rebuild and test the container**

```bash
docker compose -f docker-compose.test.yml down -v
docker compose -f docker-compose.test.yml up -d --build --wait
```

Expected: Container starts, imports dev dump, installs any missing packages, seeds E2E data, Apache starts. Health check passes.

- [ ] **Step 3: Verify the app loads with CMS content**

```bash
curl -s http://localhost:8880/ | head -20
```

Expected: HTML output with themed page content (navigation, hero, footer), not a bare skeleton.

- [ ] **Step 4: Commit**

```bash
git add docker/test-entrypoint.sh
git commit -m "fix: install missing packages after dev dump import in test entrypoint"
```

---

### Task 3: Run All E2E Tests and Fix Failures

With the realistic CMS content loaded, run the full E2E suite and fix any tests that fail. The most likely issues are:
- Selectors that assumed empty pages now find extra content
- Timeouts on pages that are heavier with real content
- Navigation or layout changes affecting element visibility

**Files:**
- Modify: `tests/e2e/specs/*.spec.ts` (any failing tests)
- Modify: `tests/e2e/fixtures/helpers.ts` (if helper adjustments needed)

**Interfaces:**
- Consumes: Running Docker test environment (from Task 2)
- Produces: All E2E tests passing with 0 failures, 0 flaky

- [ ] **Step 1: Run the full E2E suite**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1
```

Expected: Note which tests pass and which fail. Record exact error messages.

- [ ] **Step 2: Fix each failing test**

For each failure, diagnose the root cause:
- If a selector can't find an element: check if CMS content changed the DOM structure, update the selector
- If a timeout occurs: check if the page is heavier with real content, increase specific wait or use `waitForSelector`
- If an assertion fails: check if the expected text/count changed due to CMS content, update the assertion
- If a navigation issue: check if CMS pages changed URL structure, update the test URL

Fix each test individually and re-run it to confirm.

- [ ] **Step 3: Re-run the full suite to confirm all pass**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1
```

Expected: All tests pass, 0 failures.

- [ ] **Step 4: Run the suite a second time to check for flakiness**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1
```

Expected: All tests pass again. If any test fails intermittently, investigate and fix the flakiness (usually missing waits or race conditions).

- [ ] **Step 5: Commit all test fixes**

```bash
git add tests/e2e/
git commit -m "fix: make all E2E tests pass with realistic CMS content"
```
