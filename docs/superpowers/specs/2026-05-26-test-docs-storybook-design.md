# Test-Script, Test Coverage, Documentation & Storybook Update

## Overview

Consolidate the current project state after the categories/search feature and project-space UI redesign. Four workstreams in priority order: (1) test-data seed script for reproducible server state, (2) close test gaps, (3) update documentation, (4) sync Storybook stories with current code.

---

## 1. Test-Script (`scripts/seed-testdata.sh`)

### Purpose

A shell script that creates a reproducible set of test data in the database. Assumes CMS and packages are already installed (tables exist). Can be run via `make seed`.

### Behaviour

1. **Reset** — TRUNCATE all `ib_*` tables in correct FK order:
   - `ib_project_space_collaboards`
   - `ib_project_space_updates`
   - `ib_project_space_members`
   - `ib_project_spaces`
   - `ib_categories`

2. **Seed categories** — 5 categories with deterministic IDs:
   - `cat_bauen` / Bauen / bauen / sort 0
   - `cat_wohnen` / Wohnen / wohnen / sort 1
   - `cat_landwirtschaft` / Landwirtschaft / landwirtschaft / sort 2
   - `cat_tourismus` / Tourismus / tourismus / sort 3
   - `cat_mobilitaet` / Mobilität / mobilitaet / sort 4

3. **Seed project spaces** — 6 spaces across statuses:
   - `test_space_1` — Active, cat_bauen, "Solarenergie für Schulen"
   - `test_space_2` — Active, cat_mobilitaet, "Velonetz Glarus Nord"
   - `test_space_3` — Completed, cat_landwirtschaft, "Biodiversität im Siedlungsraum"
   - `test_space_4` — Active, cat_wohnen, "Fernwärmenetz Glarus Süd"
   - `test_space_5` — Archived, cat_tourismus, "Wanderwege Digitalisierung"
   - `test_space_6` — Draft, cat_bauen, "Energetische Sanierung Rathaus"

4. **Seed members** — Each space gets initiator (user 1) + 2-4 developers. One space gets a pending join request.

5. **Seed status updates** — 2-3 updates per active space with realistic timestamps (spread over weeks).

6. **Seed collaboard links** — 1-2 boards per active space with collaboard.app URLs.

7. **Output** — Print summary: "Seeded: 5 categories, 6 spaces, N members, N updates, N collaboards"

### Technical Details

- Pure SQL via `mysql` CLI — no PHP dependency
- Reads `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`, `DB_HOSTNAME` from `.env`
- Supports `DOCKER=true` flag (runs mysql inside container, matching existing `scripts/` convention)
- Deterministic IDs (e.g., `test_space_1`, `cat_bauen`) for test referencing
- All timestamps use `NOW()` minus offsets for realistic relative dates

### Makefile Target

```makefile
seed:
	@bash scripts/seed-testdata.sh
```

### Documentation

New file `docs/testing.md` documenting:
- Prerequisites (CMS + packages installed)
- How to run: `make seed` or `bash scripts/seed-testdata.sh`
- What data is created (table of spaces, members, etc.)
- How to customise (edit the SQL in the script)
- Note: this TRUNCATES all `ib_*` data — never run on production

---

## 2. Test Gaps

### Unit Tests to Add

| Test File | Tests |
|-----------|-------|
| `tests/unit/inno_moderation/Domain/CategoryTest.php` | Add edge cases: empty name, special characters in slug, duplicate slug handling |

All other new domain/handler tests already exist from the categories implementation.

### E2E Tests to Add (Playwright)

| Spec File | What it Tests |
|-----------|--------------|
| `tests/e2e/specs/projektraeume.spec.ts` | Overview page: search field present, category filter pills rendered, clicking category filters cards, status filter still works, cards show category badge |
| `tests/e2e/specs/projektraum.spec.ts` | Detail page: header shows title + status + category badge, timeline shows updates with Swiss date format, sidebar shows members with avatars/initials, back link at bottom of page, collaboard section present |

Both require the test data from `make seed` to be present. Tests use Playwright against `http://$PROJECT_NAME.local:$APP_PORT`.

---

## 3. Documentation Updates

### Markdown

| File | Changes |
|------|---------|
| `README.md` | Update component counts (26 molecules, 9 organisms). Add `make seed` to command reference. Mention category filter and search in feature list. |
| `CLAUDE.md` | Update package table: add `CategoryReaderPort` in `inno_shared`, category CRUD in `inno_moderation`. Update component counts. Add `make seed` command. |
| `docs/testing.md` | **New file** — Test data setup guide (see section 1). |

### Typst Arc42

| Chapter | Changes |
|---------|---------|
| Chapter 5 (Building Block View) | Add category management to `inno_moderation` building block. Show `CategoryReaderPort` in `inno_shared` as cross-package interface. Show `category_id` reference in `inno_project_space`. |
| Chapter 6 (Runtime View) | Add sequence: category filter request → API with `?category=slug` → QueryBuilder JOIN on `ib_categories` → filtered response. |
| Chapter 12 (Glossary) | Add: Kategorie (dynamisch verwaltete Projektkategorie), CategoryReaderPort (read-only interface for cross-package category access). |

Only update chapters directly affected by new features.

---

## 4. Storybook Stories Update

Update `.variants.php` and `.stories.json` for components whose rendered output changed:

| Component | Story Changes |
|-----------|--------------|
| `molecules/project-card` | Add variant "Active with Category" showing category badge. Add variant "With Avatar Image" using `initiatorAvatarUrl`. Update existing variants with `categoryName` prop. |
| `molecules/join-request-card` | Update variant: buttons show ✓/✕ icons only (not full text). Add `unstyled-button` class to buttons in variant markup. |
| `molecules/collaboard-item` | Update variant: "Öffnen" button uses `btn btn--secondary btn--sm`. "Entfernen" button uses `btn btn--sm` with red text. Both without `unstyled-button`. |
| `organisms/project-space-sidebar` | Update variant: member rows include avatar circles with initials. Add variant with `avatarUrl` showing `<img>` tag. |
| `molecules/status-update-entry` | Update variant: meta line shows Swiss date format (dd.mm.yyyy, hh:mm) instead of ISO. |

No new components — only update existing variants and stories to match current rendering.

---

## Out of Scope

- Integration tests (not in CI pipeline per project convention)
- Production deployment guide (separate concern)
- OpenAPI spec regeneration (existing `composer openapi:generate` handles this)
- New Storybook components (all needed components already exist)
