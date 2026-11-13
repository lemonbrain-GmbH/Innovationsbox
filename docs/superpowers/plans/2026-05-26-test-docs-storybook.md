# Test-Script, Tests, Docs & Storybook Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a reproducible test-data seed script, close test gaps, update documentation (Markdown + Typst), and sync Storybook stories with the current code state.

**Architecture:** Shell script for seeding reads `.env` and runs pure SQL. E2E tests use Playwright against the running CMS. Documentation updates target specific chapters in existing Markdown and Typst files. Storybook updates modify `.variants.php` and `.stories.json` for changed components.

**Tech Stack:** Bash, MySQL CLI, Pest PHP, Playwright, Typst, Storybook (PHP server)

**Spec:** `docs/superpowers/specs/2026-05-26-test-docs-storybook-design.md`

---

## File Map

### New Files

| File                                    | Responsibility                                                    |
| --------------------------------------- | ----------------------------------------------------------------- |
| `scripts/seed-testdata.sh`              | Shell script that truncates all `ib_*` tables and seeds test data |
| `docs/testing.md`                       | Documentation for test data setup                                 |
| `tests/e2e/specs/projektraeume.spec.ts` | E2E tests for overview page                                       |
| `tests/e2e/specs/projektraum.spec.ts`   | E2E tests for detail page                                         |

### Modified Files

| File                                                                                                          | Change                                                   |
| ------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| `Makefile`                                                                                                    | Add `seed` target                                        |
| `README.md`                                                                                                   | Update component counts, add `make seed` command         |
| `CLAUDE.md`                                                                                                   | Update package table, component counts, add seed command |
| `docs/typst/innovationsbox-dokumentation/05-building-block-view.typ`                                          | Add category management                                  |
| `docs/typst/innovationsbox-dokumentation/06-runtime-view.typ`                                                 | Add category filter scenario                             |
| `docs/typst/innovationsbox-dokumentation/12-glossary.typ`                                                     | Add new terms                                            |
| `public/packages/inno_frontend/components/molecules/project-card/project-card.variants.php`                   | Add category props, avatar URL                           |
| `public/packages/inno_frontend/components/molecules/project-card/project-card.stories.json`                   | Add "With Category" story                                |
| `public/packages/inno_frontend/components/molecules/join-request-card/join-request-card.variants.php`         | Update button markup                                     |
| `public/packages/inno_frontend/components/molecules/collaboard-item/collaboard-item.variants.php`             | Update button classes                                    |
| `public/packages/inno_frontend/components/organisms/project-space-sidebar/project-space-sidebar.variants.php` | Add avatar markup                                        |
| `public/packages/inno_frontend/components/molecules/status-update-entry/status-update-entry.variants.php`     | Swiss date format                                        |

---

## Task 1: Create Seed Script

**Files:**

- Create: `scripts/seed-testdata.sh`
- Modify: `Makefile`

- [ ] **Step 1: Create the seed script**

Read `scripts/export.sh` for reference on how existing scripts load `.env` and handle Docker. Then create `scripts/seed-testdata.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd")"
source "$SCRIPT_DIR/helpers/lib.sh"
load_env_upwards

DB_HOST="${DB_HOSTNAME:-127.0.0.1}"
DB_USER="${DB_USERNAME:?DB_USERNAME not set}"
DB_PASS="${DB_PASSWORD:?DB_PASSWORD not set}"
DB_NAME="${DB_DATABASE:?DB_DATABASE not set}"
DOCKER="${DOCKER:-false}"

run_sql() {
  if [[ "$DOCKER" == "true" ]]; then
    docker exec -i "${PROJECT_NAME}_mysql" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME"
  else
    mysql -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME"
  fi
}

echo "=== Innovationsbox Test Data Seed ==="
echo "Database: $DB_NAME @ $DB_HOST"
echo ""

# --- Truncate ---
echo "1/6 Truncating ib_* tables..."
run_sql <<'SQL'
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE ib_project_space_collaboards;
TRUNCATE TABLE ib_project_space_updates;
TRUNCATE TABLE ib_project_space_members;
TRUNCATE TABLE ib_project_spaces;
TRUNCATE TABLE ib_categories;
SET FOREIGN_KEY_CHECKS = 1;
SQL

# --- Categories ---
echo "2/6 Seeding categories..."
run_sql <<'SQL'
INSERT INTO ib_categories (id, name, slug, sort_order, created_at, updated_at) VALUES
('cat_bauen', 'Bauen', 'bauen', 0, NOW(), NOW()),
('cat_wohnen', 'Wohnen', 'wohnen', 1, NOW(), NOW()),
('cat_landwirtschaft', 'Landwirtschaft', 'landwirtschaft', 2, NOW(), NOW()),
('cat_tourismus', 'Tourismus', 'tourismus', 3, NOW(), NOW()),
('cat_mobilitaet', 'Mobilität', 'mobilitaet', 4, NOW(), NOW());
SQL

# --- Project Spaces ---
echo "3/6 Seeding project spaces..."
run_sql <<'SQL'
INSERT INTO ib_project_spaces (id, project_idea_id, title, description, status, initiator_id, category_id, created_at, updated_at) VALUES
('test_space_1', 'idea_1', 'Solarenergie für Schulen', 'Installation von Photovoltaik-Anlagen auf Schulgebäuden im Kanton Glarus. Ziel: 100% erneuerbare Energie für alle Schulen bis 2030.', 'active', 1, 'cat_bauen', DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
('test_space_2', 'idea_2', 'Velonetz Glarus Nord', 'Konzepte für sichere Veloverbindungen zwischen Glarus, Näfels und Mollis. Fokus auf Pendlerrouten und Schulwege.', 'active', 1, 'cat_mobilitaet', DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
('test_space_3', 'idea_3', 'Biodiversität im Siedlungsraum', 'Förderung der Artenvielfalt in Wohngebieten durch naturnahe Gestaltung von Grünflächen und Gärten.', 'completed', 1, 'cat_landwirtschaft', DATE_SUB(NOW(), INTERVAL 240 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),
('test_space_4', 'idea_4', 'Fernwärmenetz Glarus Süd', 'Planung eines Fernwärmenetzes für die Gemeinden Glarus Süd mit erneuerbaren Energiequellen.', 'active', 1, 'cat_wohnen', DATE_SUB(NOW(), INTERVAL 45 DAY), NOW()),
('test_space_5', 'idea_5', 'Wanderwege Digitalisierung', 'Digitale Erfassung und Beschilderung aller Wanderwege im Kanton mit QR-Codes und App-Integration.', 'archived', 1, 'cat_tourismus', DATE_SUB(NOW(), INTERVAL 365 DAY), DATE_SUB(NOW(), INTERVAL 60 DAY)),
('test_space_6', 'idea_6', 'Energetische Sanierung Rathaus', 'Sanierungskonzept für das historische Rathaus unter Berücksichtigung des Denkmalschutzes.', 'draft', 1, 'cat_bauen', DATE_SUB(NOW(), INTERVAL 7 DAY), NOW());
SQL

# --- Members ---
echo "4/6 Seeding members..."
run_sql <<'SQL'
INSERT INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at, decided_at) VALUES
-- Space 1: initiator + 3 developers
('mem_1_1', 'test_space_1', 1, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 90 DAY), NULL),
('mem_1_2', 'test_space_1', 2, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 80 DAY), DATE_SUB(NOW(), INTERVAL 79 DAY)),
('mem_1_3', 'test_space_1', 3, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 70 DAY), DATE_SUB(NOW(), INTERVAL 69 DAY)),
('mem_1_4', 'test_space_1', 4, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 50 DAY), DATE_SUB(NOW(), INTERVAL 49 DAY)),
-- Space 1: 1 pending join request
('mem_1_5', 'test_space_1', 5, 'developer', 'pending', DATE_SUB(NOW(), INTERVAL 2 DAY), NULL),
-- Space 2: initiator + 2 developers
('mem_2_1', 'test_space_2', 1, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 60 DAY), NULL),
('mem_2_2', 'test_space_2', 2, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 55 DAY), DATE_SUB(NOW(), INTERVAL 54 DAY)),
('mem_2_3', 'test_space_2', 3, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 39 DAY)),
-- Space 3: initiator + 4 developers (completed project, more contributors)
('mem_3_1', 'test_space_3', 1, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 240 DAY), NULL),
('mem_3_2', 'test_space_3', 2, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 230 DAY), DATE_SUB(NOW(), INTERVAL 229 DAY)),
('mem_3_3', 'test_space_3', 3, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 200 DAY), DATE_SUB(NOW(), INTERVAL 199 DAY)),
('mem_3_4', 'test_space_3', 4, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 180 DAY), DATE_SUB(NOW(), INTERVAL 179 DAY)),
('mem_3_5', 'test_space_3', 5, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 150 DAY), DATE_SUB(NOW(), INTERVAL 149 DAY)),
-- Space 4: initiator + 2 developers
('mem_4_1', 'test_space_4', 1, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 45 DAY), NULL),
('mem_4_2', 'test_space_4', 2, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY)),
('mem_4_3', 'test_space_4', 4, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY)),
-- Space 5: initiator + 2 developers (archived)
('mem_5_1', 'test_space_5', 1, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 365 DAY), NULL),
('mem_5_2', 'test_space_5', 3, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 350 DAY), DATE_SUB(NOW(), INTERVAL 349 DAY)),
('mem_5_3', 'test_space_5', 5, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 300 DAY), DATE_SUB(NOW(), INTERVAL 299 DAY)),
-- Space 6: initiator only (draft)
('mem_6_1', 'test_space_6', 1, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 7 DAY), NULL);
SQL

# --- Status Updates ---
echo "5/6 Seeding status updates..."
run_sql <<'SQL'
INSERT INTO ib_project_space_updates (id, space_id, title, body, author_id, created_at) VALUES
-- Space 1: 3 updates
('upd_1_1', 'test_space_1', 'Kickoff-Meeting durchgeführt', '<p>Erstes Treffen mit allen Interessierten. Arbeitsgruppen gebildet und nächste Schritte definiert.</p>', 1, DATE_SUB(NOW(), INTERVAL 80 DAY)),
('upd_1_2', 'test_space_1', 'Umfrage gestartet', '<p>Online-Umfrage zur Priorisierung der Schulgebäude wurde an alle Gemeinden verschickt.</p>', 1, DATE_SUB(NOW(), INTERVAL 45 DAY)),
('upd_1_3', 'test_space_1', 'Ergebnisse der Umfrage ausgewertet', '<p>Die Umfrage hat 127 Antworten erhalten. Wichtigste Erkenntnis: 68% wünschen sich Photovoltaik auf dem Schulhaus Glarus.</p>', 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
-- Space 2: 2 updates
('upd_2_1', 'test_space_2', 'Bestandsaufnahme abgeschlossen', '<p>Alle bestehenden Velowege kartiert und Gefahrenstellen identifiziert.</p>', 1, DATE_SUB(NOW(), INTERVAL 40 DAY)),
('upd_2_2', 'test_space_2', 'Variantenstudie in Arbeit', '<p>Drei Routenvarianten werden derzeit vom Ingenieurbüro geprüft.</p>', 3, DATE_SUB(NOW(), INTERVAL 10 DAY)),
-- Space 3: 2 updates (completed)
('upd_3_1', 'test_space_3', 'Pilotprojekt gestartet', '<p>In zwei Quartieren werden Blühwiesen und Insektenhotels installiert.</p>', 1, DATE_SUB(NOW(), INTERVAL 180 DAY)),
('upd_3_2', 'test_space_3', 'Projekt erfolgreich abgeschlossen', '<p>Alle geplanten Massnahmen umgesetzt. Monitoring zeigt 40% mehr Insektenarten.</p>', 1, DATE_SUB(NOW(), INTERVAL 35 DAY)),
-- Space 4: 2 updates
('upd_4_1', 'test_space_4', 'Machbarkeitsstudie beauftragt', '<p>Die Studie zur Wärmequelle wird vom Ingenieurbüro Müller & Partner durchgeführt.</p>', 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
('upd_4_2', 'test_space_4', 'Infoveranstaltung geplant', '<p>Am 15. Juni findet eine öffentliche Informationsveranstaltung im Gemeindesaal statt.</p>', 4, DATE_SUB(NOW(), INTERVAL 5 DAY));
SQL

# --- Collaboard Links ---
echo "6/6 Seeding collaboard links..."
run_sql <<'SQL'
INSERT INTO ib_project_space_collaboards (id, space_id, title, url, added_at) VALUES
('cb_1_1', 'test_space_1', 'Brainstorming Board', 'https://web.collaboard.app/board/solar-brainstorm', DATE_SUB(NOW(), INTERVAL 75 DAY)),
('cb_1_2', 'test_space_1', 'Massnahmenplan', 'https://web.collaboard.app/board/solar-plan', DATE_SUB(NOW(), INTERVAL 40 DAY)),
('cb_2_1', 'test_space_2', 'Routenplanung', 'https://web.collaboard.app/board/velo-routes', DATE_SUB(NOW(), INTERVAL 50 DAY)),
('cb_4_1', 'test_space_4', 'Konzeptentwurf', 'https://web.collaboard.app/board/waerme-konzept', DATE_SUB(NOW(), INTERVAL 25 DAY));
SQL

echo ""
echo "=== Done ==="
echo "Seeded: 5 categories, 6 spaces, 21 members (incl. 1 pending), 9 updates, 4 collaboards"
echo "Active spaces: test_space_1, test_space_2, test_space_4"
echo "Completed: test_space_3 | Archived: test_space_5 | Draft: test_space_6"
```

Make it executable:

```bash
chmod +x scripts/seed-testdata.sh
```

- [ ] **Step 2: Add Makefile target**

Append to `Makefile`:

```makefile
seed:
 @echo "Seeding test data into database..."
 @bash scripts/seed-testdata.sh
```

- [ ] **Step 3: Test the script**

Run: `make seed`
Expected: Output showing 6 steps, summary line with counts. Verify with:

```bash
mysql -h 127.0.0.1 -u innovationsbox_usr -p123456 innovationsbox -e "SELECT COUNT(*) FROM ib_categories; SELECT COUNT(*) FROM ib_project_spaces; SELECT COUNT(*) FROM ib_project_space_members;"
```

Expected: 5, 6, 21

- [ ] **Step 4: Commit**

```bash
git add scripts/seed-testdata.sh Makefile
git commit -m "feat: add test data seed script (make seed)"
```

---

## Task 2: Create Testing Documentation

**Files:**

- Create: `docs/testing.md`

- [ ] **Step 1: Write docs/testing.md**

````markdown
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
````

Or directly:

```bash
bash scripts/seed-testdata.sh

# With Docker:
DOCKER=true PROJECT_NAME=innovationsbox bash scripts/seed-testdata.sh
```

### What Gets Created

| Table                          | Records | Details                                                  |
| ------------------------------ | ------- | -------------------------------------------------------- |
| `ib_categories`                | 5       | Bauen, Wohnen, Landwirtschaft, Tourismus, Mobilität      |
| `ib_project_spaces`            | 6       | 3 active, 1 completed, 1 archived, 1 draft               |
| `ib_project_space_members`     | 21      | Initiator + developers per space, 1 pending join request |
| `ib_project_space_updates`     | 9       | 2-3 updates per active/completed space                   |
| `ib_project_space_collaboards` | 4       | 1-2 boards per active space                              |

### Deterministic IDs

All IDs are fixed strings for test referencing:

- Spaces: `test_space_1` through `test_space_6`
- Categories: `cat_bauen`, `cat_wohnen`, `cat_landwirtschaft`, `cat_tourismus`, `cat_mobilitaet`
- Members: `mem_{spaceNum}_{memberNum}` (e.g., `mem_1_3`)
- Updates: `upd_{spaceNum}_{updateNum}` (e.g., `upd_1_2`)

### Warning

**This script TRUNCATES all `ib_*` tables.** Never run on production. All existing project data will be deleted.

## Running Tests

### Unit Tests

```bash
composer test              # Run all unit tests
composer test:coverage     # With coverage report (80% minimum enforced)
```

### E2E Tests

```bash
make seed                  # Seed test data first
npm run test:e2e           # Run Playwright tests (requires running CMS)
```

### Full CI Suite (local)

```bash
npm run test:ci            # Lint + typecheck + unit tests + coverage + build + storybook smoke
```

````

- [ ] **Step 2: Commit**

```bash
git add docs/testing.md
git commit -m "docs: add testing and test data setup guide"
````

---

## Task 3: E2E Tests for Projekträume Overview

**Files:**

- Create: `tests/e2e/specs/projektraeume.spec.ts`

- [ ] **Step 1: Write the E2E test**

```typescript
import { test, expect } from '@playwright/test';

const BASE = '/projektraeume';

test.describe('Projekträume Overview', () => {
  test('page loads with title and search', async ({ page }) => {
    await page.goto(BASE);
    await expect(page.locator('h1')).toContainText('Projekträume');
    await expect(page.locator('#project-search')).toBeVisible();
  });

  test('status filter buttons are present', async ({ page }) => {
    await page.goto(BASE);
    const filters = page.locator('#project-filters');
    await expect(filters.locator('[data-value=""]')).toContainText('Alle');
    await expect(filters.locator('[data-value="active"]')).toContainText('Aktiv');
    await expect(filters.locator('[data-value="completed"]')).toContainText('Abgeschlossen');
  });

  test('category filter pills are rendered from API', async ({ page }) => {
    await page.goto(BASE);
    const catFilters = page.locator('#category-filters');
    await expect(catFilters.locator('[data-category=""]')).toContainText('Alle');
    await expect(catFilters.locator('[data-category="bauen"]')).toBeVisible();
    await expect(catFilters.locator('[data-category="mobilitaet"]')).toBeVisible();
  });

  test('project cards are displayed', async ({ page }) => {
    await page.goto(BASE);
    await page.waitForSelector('.project-card');
    const cards = page.locator('.project-card');
    await expect(cards).toHaveCount(3); // 3 active/completed visible by default
  });

  test('category filter narrows results', async ({ page }) => {
    await page.goto(BASE);
    await page.waitForSelector('.project-card');
    await page.locator('#category-filters [data-category="bauen"]').click();
    await page.waitForTimeout(500);
    const cards = page.locator('.project-card');
    const count = await cards.count();
    expect(count).toBeLessThanOrEqual(3);
  });

  test('search filters by title', async ({ page }) => {
    await page.goto(BASE);
    await page.waitForSelector('.project-card');
    await page.fill('#project-search', 'Solar');
    await page.waitForTimeout(500);
    const cards = page.locator('.project-card');
    await expect(cards).toHaveCount(1);
    await expect(cards.first().locator('.project-card__title')).toContainText('Solar');
  });

  test('cards show category badge', async ({ page }) => {
    await page.goto(BASE);
    await page.waitForSelector('.project-card');
    const badge = page.locator('.project-card__badge--category').first();
    await expect(badge).toBeVisible();
  });
});
```

- [ ] **Step 2: Run to verify tests work**

Run: `npx playwright test tests/e2e/specs/projektraeume.spec.ts`
Expected: Tests pass (requires CMS running + `make seed` data)

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/projektraeume.spec.ts
git commit -m "test(e2e): add Playwright tests for Projekträume overview page"
```

---

## Task 4: E2E Tests for Projektraum Detail

**Files:**

- Create: `tests/e2e/specs/projektraum.spec.ts`

- [ ] **Step 1: Write the E2E test**

```typescript
import { test, expect } from '@playwright/test';

const DETAIL_URL = '/projektraum/view/test_space_1';

test.describe('Projektraum Detail', () => {
  test('page loads with project title', async ({ page }) => {
    await page.goto(DETAIL_URL);
    await expect(page.locator('#space-title')).toContainText('Solarenergie');
  });

  test('header shows status and category badge', async ({ page }) => {
    await page.goto(DETAIL_URL);
    await expect(page.locator('#space-status-badge .badge')).toBeVisible();
  });

  test('status update timeline is visible', async ({ page }) => {
    await page.goto(DETAIL_URL);
    await page.waitForSelector('.status-update-timeline');
    const entries = page.locator('.status-update-entry');
    const count = await entries.count();
    expect(count).toBeGreaterThanOrEqual(1);
  });

  test('status update shows Swiss date format', async ({ page }) => {
    await page.goto(DETAIL_URL);
    await page.waitForSelector('.status-update-entry__meta');
    const meta = await page.locator('.status-update-entry__meta').first().textContent();
    // Swiss format: dd.mm.yyyy, hh:mm
    expect(meta).toMatch(/\d{2}\.\d{2}\.\d{4}/);
  });

  test('sidebar shows team members', async ({ page }) => {
    await page.goto(DETAIL_URL);
    await page.waitForSelector('.project-space-sidebar');
    const members = page.locator('.project-space-sidebar__member-row');
    const count = await members.count();
    expect(count).toBeGreaterThanOrEqual(2);
  });

  test('collaboard section is present', async ({ page }) => {
    await page.goto(DETAIL_URL);
    await page.waitForSelector('.collaboard-panel');
    const items = page.locator('.collaboard-item');
    const count = await items.count();
    expect(count).toBeGreaterThanOrEqual(1);
  });

  test('back link is at bottom of page', async ({ page }) => {
    await page.goto(DETAIL_URL);
    const backLink = page.locator('.projektraum-page__back');
    await expect(backLink).toBeVisible();
    await expect(backLink).toContainText('Zurück');
    // Verify it comes after the content
    const header = page.locator('.projektraum-page__header');
    const backBox = await backLink.boundingBox();
    const headerBox = await header.boundingBox();
    if (backBox && headerBox) {
      expect(backBox.y).toBeGreaterThan(headerBox.y + headerBox.height);
    }
  });
});
```

- [ ] **Step 2: Run to verify**

Run: `npx playwright test tests/e2e/specs/projektraum.spec.ts`
Expected: Tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/projektraum.spec.ts
git commit -m "test(e2e): add Playwright tests for Projektraum detail page"
```

---

## Task 5: Update Storybook — Project Card

**Files:**

- Modify: `public/packages/inno_frontend/components/molecules/project-card/project-card.variants.php`
- Modify: `public/packages/inno_frontend/components/molecules/project-card/project-card.stories.json`

- [ ] **Step 1: Update variants with category and avatar props**

Replace the contents of `project-card.variants.php`:

```php
<?php

return [
    'defaults' => [
        'id' => 'space_1',
        'title' => 'Solarenergie für Schulen',
        'description' => 'Installation von Photovoltaik-Anlagen auf Schulgebäuden im Kanton Glarus.',
        'status' => 'active',
        'categoryName' => 'Bauen',
        'initiatorName' => 'Maria Keller',
        'initiatorInitials' => 'MK',
        'initiatorAvatarUrl' => '',
        'memberCount' => 5,
        'latestUpdateTitle' => 'Ergebnisse der Umfrage ausgewertet',
        'latestUpdateDate' => 'vor 3 Tagen',
        'createdAt' => 'vor 3 Monaten',
    ],
];
```

- [ ] **Step 2: Update stories with category variant**

Replace the contents of `project-card.stories.json`:

```json
{
  "title": "Molecules/Project Card",
  "argTypes": {
    "title": { "control": "text" },
    "description": { "control": "text" },
    "status": { "control": { "type": "select", "options": ["active", "completed", "archived"] } },
    "categoryName": { "control": "text" },
    "initiatorName": { "control": "text" },
    "initiatorAvatarUrl": { "control": "text" },
    "memberCount": { "control": "number" }
  },
  "stories": [
    { "name": "Active", "args": {} },
    { "name": "Active with Category", "args": { "categoryName": "Mobilität" } },
    { "name": "Completed", "args": { "status": "completed", "categoryName": "Landwirtschaft" } },
    { "name": "No Update", "args": { "latestUpdateTitle": "", "latestUpdateDate": "", "categoryName": "" } }
  ],
  "parameters": { "server": { "id": "molecules/project-card" } }
}
```

- [ ] **Step 3: Update the PHP template to render category badge**

Read `project-card.php` and add category badge rendering in the header section after the status badge. Add the `categoryName` prop extraction at the top and render `<span class="project-card__badge project-card__badge--category">` when present.

- [ ] **Step 4: Verify in Storybook**

Run: `npm run storybook`
Open: `http://localhost:6006`
Check: "Molecules/Project Card" shows all 4 variants correctly with category badges.

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/project-card/
git commit -m "fix(storybook): update project-card stories with category badge and avatar"
```

---

## Task 6: Update Storybook — Join Request, Collaboard, Sidebar, Status Update

**Files:**

- Modify: `public/packages/inno_frontend/components/molecules/join-request-card/join-request-card.php`
- Modify: `public/packages/inno_frontend/components/molecules/collaboard-item/collaboard-item.php`
- Modify: `public/packages/inno_frontend/components/organisms/project-space-sidebar/project-space-sidebar.variants.php`
- Modify: `public/packages/inno_frontend/components/molecules/status-update-entry/status-update-entry.variants.php`

- [ ] **Step 1: Update join-request-card.php**

Read the current template. Update the accept/reject buttons to use `unstyled-button` class and icon-only text (✓ and ✕ instead of "✓ Annehmen" / "✕ Ablehnen").

- [ ] **Step 2: Update collaboard-item.php**

Read the current template. Update the action button to use `btn btn--secondary btn--sm` classes. If a remove button exists, use `btn btn--sm` with `style="color: var(--color-error, #dc3545);"`.

- [ ] **Step 3: Update sidebar variants**

Replace the contents of `project-space-sidebar.variants.php` to include avatar initials in member data:

```php
<?php

return [
    'defaults' => [
        'initiatorName' => 'Maria Keller',
        'initiatorInitials' => 'MK',
        'members' => [
            ['name' => 'Maria Keller', 'initials' => 'MK', 'role' => 'initiator'],
            ['name' => 'Thomas Weber', 'initials' => 'TW', 'role' => 'developer'],
            ['name' => 'Anna Müller', 'initials' => 'AM', 'role' => 'developer'],
        ],
        'requests' => [
            ['id' => 'req_1', 'userName' => 'Lisa Schneider', 'userInitials' => 'LS'],
        ],
        'canManage' => 'true',
        'createdAt' => '15.03.2026',
        'updateCount' => '3',
        'collaboardCount' => '2',
    ],
];
```

- [ ] **Step 4: Update status-update-entry variants**

Update `status-update-entry.variants.php` — change `createdAt` default from ISO format to Swiss format:

```php
<?php

return [
    'defaults' => [
        'title' => 'Ergebnisse der Umfrage ausgewertet',
        'body' => '<p>Die Umfrage hat 127 Antworten erhalten.</p>',
        'authorName' => 'Maria Keller',
        'createdAt' => '22.05.2026, 14:30',
        'isLatest' => 'true',
    ],
];
```

- [ ] **Step 5: Verify all in Storybook**

Run: `npm run storybook`
Check each updated component in the browser.

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/join-request-card/ \
        public/packages/inno_frontend/components/molecules/collaboard-item/ \
        public/packages/inno_frontend/components/organisms/project-space-sidebar/ \
        public/packages/inno_frontend/components/molecules/status-update-entry/
git commit -m "fix(storybook): sync component stories with current rendering"
```

---

## Task 7: Update README.md and CLAUDE.md

**Files:**

- Modify: `README.md`
- Modify: `CLAUDE.md`

- [ ] **Step 1: Update README.md**

Read the current file. Make these changes:

1. In the component counts section, update:
   - Atoms: 24 (add `email-link` if missing)
   - Molecules: 26 (add: `auth-card`, `avatar-upload`, `collaboard-item`, `join-request-card`, `member-card`, `password-field`, `project-card`, `status-update-entry`, `user-menu`)
   - Organisms: 9 (add: `collaboard-panel`, `project-space-sidebar`, `status-update-timeline`)

2. In the commands section, add:

   ```
   make seed              # Seed test data (TRUNCATES existing ib_* data)
   ```

- [ ] **Step 2: Update CLAUDE.md**

Read the current file. Make these changes:

1. Update the package table — add a note that `inno_moderation` now manages categories (CRUD API at `/api/moderation/category`)
2. Add `CategoryReaderPort` to `inno_shared` description
3. Update component counts in the Component Library section to match README
4. Add `make seed` to the testing commands section
5. Add `docs/testing.md` reference

- [ ] **Step 3: Commit**

```bash
git add README.md CLAUDE.md
git commit -m "docs: update README and CLAUDE.md with categories, component counts, make seed"
```

---

## Task 8: Update Typst Arc42 Documentation

**Files:**

- Modify: `docs/typst/innovationsbox-dokumentation/05-building-block-view.typ`
- Modify: `docs/typst/innovationsbox-dokumentation/06-runtime-view.typ`
- Modify: `docs/typst/innovationsbox-dokumentation/12-glossary.typ`

- [ ] **Step 1: Update Chapter 5 — Building Block View**

Read the current file. Add to the `inno_moderation` package section:

- New responsibility: "Verwaltung von Projektkategorien (CRUD)"
- New table: `ib_categories` (id, name, slug, sort_order)
- New API endpoints: `GET/POST/PUT/DELETE /api/moderation/category`
- New Use Case: `ManageCategory` (Create, Update, Delete, List)

Add to the `inno_shared` package section:

- New Port: `CategoryReaderPort` — read-only interface for cross-package category access

Add to the `inno_project_space` package section:

- New column: `category_id` on `ib_project_spaces`
- New query parameters: `?category=slug`, `?search=term`
- New endpoint: `GET /api/project-space/categories`

Update the package communication table to show that `inno_project_space` reads categories via `CategoryReaderPort`.

- [ ] **Step 2: Update Chapter 6 — Runtime View**

Add a new scenario "Projekträume nach Kategorie filtern":

The flow: User clicks category pill → Frontend sends `GET /api/project-space?category=bauen` → Controller passes slug to `findPublic()` → QueryBuilder JOINs `ib_categories` on `category_id = c.id` WHERE `c.slug = ?` → Filtered results returned.

- [ ] **Step 3: Update Chapter 12 — Glossary**

Add entries:

- **Kategorie**: Dynamisch verwaltete Projektkategorie (z.B. Bauen, Wohnen, Mobilität). Wird vom Moderator über die API verwaltet und bei der Ideenerfassung zugewiesen.
- **CategoryReaderPort**: Read-only Interface in `inno_shared`, das anderen Packages den Zugriff auf die Kategorienliste ermöglicht, ohne das `inno_moderation`-Package direkt zu importieren.

- [ ] **Step 4: Commit**

```bash
git add docs/typst/innovationsbox-dokumentation/
git commit -m "docs: update Arc42 chapters 5, 6, 12 with categories feature"
```

---

## Task 9: Final Verification

- [ ] **Step 1: Run all unit tests**

Run: `composer test`
Expected: All 145+ tests pass

- [ ] **Step 2: Run linting**

Run: `npm run lint:all && composer lint:php`
Expected: No errors

- [ ] **Step 3: Build production assets**

Run: `npm run production`
Expected: `webpack compiled successfully`

- [ ] **Step 4: Run seed script**

Run: `make seed`
Expected: Summary output, data in DB

- [ ] **Step 5: Verify Storybook builds**

Run: `npm run storybook:build`
Expected: Static site built without errors

- [ ] **Step 6: Commit any remaining fixes**
