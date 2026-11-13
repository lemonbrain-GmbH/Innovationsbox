# Phase 3: Documentation & Accessibility — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close all OpenAPI, accessibility, and documentation gaps identified in the QA infrastructure spec — verify what's already done, fix what's missing.

**Architecture:** Three workstreams (OpenAPI, ARIA/a11y, docs) that are independent of each other. Each task starts with a verification step so already-complete items can be confirmed and skipped.

**Tech Stack:** PHP 8 OpenAPI attributes, HTML/ARIA, Markdown, Spectral, axe-core, Bitbucket Pipelines

**Spec:** `docs/superpowers/specs/2026-06-09-comprehensive-qa-infrastructure-design.md` (Phase 3)

---

## File Structure

| Action | File | Responsibility |
|--------|------|----------------|
| Modify | `public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php` | Add OA annotations to `show()` and `requestFeedback()` |
| Modify | `public/packages/inno_frontend/components/organisms/status-update-timeline/status-update-timeline.php` | Add `aria-label` to `<section>` |
| Modify | `public/packages/inno_frontend/components/organisms/collaboard-panel/collaboard-panel.php` | Add `aria-label` to `<section>`, `aria-label` to iframe container |
| Modify | `public/packages/inno_frontend/components/organisms/project-space-sidebar/project-space-sidebar.php` | Add `aria-label` to `<aside>` |
| Modify | `public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.php` | Add `aria-label` to grid region |
| Modify | `public/packages/inno_frontend/components/organisms/hero-image/hero-image.php` | Add `role="banner"` to wrapper |
| Modify | `public/packages/inno_frontend/components/molecules/member-card/member-card.php` | Add `aria-label` to card |
| Modify | `public/packages/inno_frontend/components/molecules/project-card/project-card.php` | Add `aria-labelledby` to `<article>` |
| Modify | `docs/testing.md` | Add `make seed-e2e` and Docker E2E section |
| Verify | `bitbucket-pipelines.yml` | Confirm OpenAPI staleness check and markdown-link-check exist |
| Verify | `tests/e2e/specs/storybook/axe.spec.ts` | Confirm disabled rules are justified |
| Verify | `docs/e2e-testing.md` | Confirm it covers Docker, seed, mailpit, helpers |
| Verify | `README.md` | Confirm it covers docker-compose.test.yml, make seed-e2e |

---

### Task 1: Verify OpenAPI CI staleness check

**Files:**
- Verify: `bitbucket-pipelines.yml:48-50`

- [ ] **Step 1: Verify the pipeline has the staleness check**

Run: Read `bitbucket-pipelines.yml` lines 46-53

Expected: Lines 48-50 contain:
```yaml
# OpenAPI spec staleness check
- composer openapi:generate
- git diff --exit-code docs/api/openapi.yaml docs/api/openapi.json || (echo "ERROR: ..." && exit 1)
```

- [ ] **Step 2: Verify spec files exist and are committed**

Run: `git ls-files docs/api/openapi.yaml docs/api/openapi.json`
Expected: Both files listed (tracked in git)

- [ ] **Step 3: Mark as done if both checks pass**

If both pass, this task is already complete — no changes needed.

---

### Task 2: Verify markdown-link-check in pipeline

**Files:**
- Verify: `bitbucket-pipelines.yml:53`

- [ ] **Step 1: Verify the pipeline has markdown-link-check**

Run: Read `bitbucket-pipelines.yml` line 53

Expected: Line contains:
```yaml
- npx markdown-link-check README.md CONTRIBUTING.md TESTING.md SECURITY.md --quiet || true
```

- [ ] **Step 2: Verify all target files exist**

Run: `ls README.md CONTRIBUTING.md TESTING.md SECURITY.md`
Expected: All 4 files exist

- [ ] **Step 3: Verify the check actually runs locally**

Run: `npx markdown-link-check README.md --quiet`
Expected: Exits 0 (or shows some broken links — note any failures for Task 11)

- [ ] **Step 4: Mark as done if pipeline step exists**

If the step is in the pipeline, this task is already complete. Note: the `|| true` means it won't fail the build — the spec says it should be enforced. If you want it to block the build, remove `|| true`. Ask user before changing.

---

### Task 3: Verify existing OpenAPI annotations — all controllers

**Files:**
- Verify: `public/packages/inno_*/src/Adapter/In/Http/*ApiController.php`

- [ ] **Step 1: Count OA annotations per controller**

Run: `grep -c "OA\\\\" public/packages/inno_*/src/Adapter/In/Http/*ApiController.php`

Expected (minimum):
- CategoryApiController: ~27
- ModerationApiController: ~20 (will increase in Task 4)
- UserApiController: ~13
- ProjectIdeaApiController: ~17
- ProjectSpaceApiController: ~61
- NotificationApiController: ~7

- [ ] **Step 2: Verify NotificationApiController is fully annotated**

Read: `public/packages/inno_notification/src/Adapter/In/Http/NotificationApiController.php`

Expected: Both `index()` (line 36) and `markRead()` (line 51) have OA annotations. This controller is intentionally stub-like — endpoints return empty arrays. The OA annotations document the stub contract. Mark as done if both methods have annotations.

- [ ] **Step 3: Verify UserApiController is fully annotated**

Read: `public/packages/inno_moderation/src/Adapter/In/Http/UserApiController.php` (it lives in the moderation package)

Check that all public methods have `#[OA\...]` attributes. If any are missing, note which methods for a follow-up task.

- [ ] **Step 4: Mark as done if all controllers are annotated (except the 2 gaps in Task 4)**

---

### Task 4: Add OpenAPI annotations to ModerationApiController gaps

**Files:**
- Modify: `public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php:89,216`

Two public methods lack OA annotations: `show()` (line 89) and `requestFeedback()` (line 216).

- [ ] **Step 1: Add OA annotations to `show()`**

Insert before line 89 (`public function show`):

```php
    #[OA\Get(
        path: '/moderation/{id}',
        summary: 'View moderation review detail',
        description: 'Returns the full review with embedded idea data. Auto-locks the idea to UnderReview status if it was Submitted. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['Moderation'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Review ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Review detail with embedded idea',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/ModerationReviewDetail'),
            ],
        ),
    )]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Review not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
```

- [ ] **Step 2: Add OA annotations to `requestFeedback()`**

Insert before line 216 (`public function requestFeedback`):

```php
    #[OA\Post(
        path: '/moderation/{id}/feedback',
        summary: 'Request feedback from idea submitter',
        description: 'Sets the review to feedback_requested status and notifies the submitter. Notes are required. Optional deadline. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['Moderation'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Review ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['notes'],
        properties: [
            new OA\Property(property: 'notes', type: 'string', description: 'Feedback notes for the submitter'),
            new OA\Property(property: 'deadline', type: 'string', format: 'date', description: 'Optional feedback deadline (YYYY-MM-DD)', nullable: true),
        ],
    ))]
    #[OA\Response(response: 200, description: 'Feedback requested', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Review not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 422, description: 'Validation error — notes required', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
```

- [ ] **Step 3: Regenerate OpenAPI spec**

Run: `composer openapi:generate`
Expected: Exits 0, `docs/api/openapi.yaml` and `docs/api/openapi.json` updated

- [ ] **Step 4: Lint the updated spec**

Run: `npm run lint:api`
Expected: No new errors (warnings for missing `operation-description` are OK per `.spectral.yaml`)

- [ ] **Step 5: Run PHPStan to check for syntax issues**

Run: `composer lint:php`
Expected: No new errors

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php docs/api/openapi.yaml docs/api/openapi.json
git commit -m "docs: add OpenAPI annotations to ModerationApiController show() and requestFeedback()"
```

---

### Task 5: Verify axe-core disabled rules are justified

**Files:**
- Verify: `tests/e2e/specs/storybook/axe.spec.ts:31`

- [ ] **Step 1: Read the disabled rules**

Run: Read `tests/e2e/specs/storybook/axe.spec.ts` lines 28-35

Expected disabled rules:
- `landmark-one-main` — components tested in isolation (no full page)
- `page-has-heading-one` — individual components don't need h1
- `region` — component snippets not wrapped in landmark regions

- [ ] **Step 2: Verify these are isolation-only rules**

All three are page-level rules that don't apply to component-level testing. They are NOT suppressing real violations. These disabled rules are justified. No changes needed.

- [ ] **Step 3: Confirm `aria-required-attr` and `aria-valid-attr` are enforced**

These rules are part of the default axe-core ruleset and are NOT in the disabled list. They are enforced. Mark as done.

---

### Task 6: Add ARIA attributes to organism components

**Files:**
- Modify: `public/packages/inno_frontend/components/organisms/status-update-timeline/status-update-timeline.php:10`
- Modify: `public/packages/inno_frontend/components/organisms/collaboard-panel/collaboard-panel.php:10,42`
- Modify: `public/packages/inno_frontend/components/organisms/project-space-sidebar/project-space-sidebar.php:15`
- Modify: `public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.php:14`
- Modify: `public/packages/inno_frontend/components/organisms/hero-image/hero-image.php:20`

- [ ] **Step 1: Add `aria-label` to status-update-timeline section**

In `status-update-timeline.php`, change line 10:

```php
// OLD:
<section class="status-update-timeline">
// NEW:
<section class="status-update-timeline" aria-label="<?= t('Projekt-Updates') ?>">
```

- [ ] **Step 2: Add `aria-label` to collaboard-panel section and iframe container**

In `collaboard-panel.php`, change line 10:

```php
// OLD:
<section class="collaboard-panel">
// NEW:
<section class="collaboard-panel" aria-label="<?= t('Collaboards') ?>">
```

Change line 42:

```php
// OLD:
    <div class="collaboard-panel__iframe-container" id="collaboard-iframe-container" hidden>
// NEW:
    <div class="collaboard-panel__iframe-container" id="collaboard-iframe-container" hidden aria-hidden="true">
```

- [ ] **Step 3: Add `aria-label` to project-space-sidebar**

In `project-space-sidebar.php`, change line 15:

```php
// OLD:
<aside class="project-space-sidebar">
// NEW:
<aside class="project-space-sidebar" aria-label="<?= t('Projektteam und Statistik') ?>">
```

- [ ] **Step 4: Add `aria-label` to page-list-grid**

In `page-list-grid.php`, change line 14:

```php
// OLD:
    <div class="page-list-wrapper">
// NEW:
    <div class="page-list-wrapper" role="region" aria-label="<?= t('Seitenliste') ?>">
```

- [ ] **Step 5: Add `role="banner"` to hero-image wrapper**

In `hero-image.php`, change line 20:

```php
// OLD:
<div class="hero-image-wrapper">
// NEW:
<div class="hero-image-wrapper" role="banner">
```

- [ ] **Step 6: Run Storybook axe tests to verify no regressions**

Run: `npm run storybook:build && npx playwright test tests/e2e/specs/storybook/axe.spec.ts`

If Storybook isn't set up locally, run: `npm run storybook:build` and verify it succeeds. The axe tests will run in CI.

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_frontend/components/organisms/status-update-timeline/status-update-timeline.php \
        public/packages/inno_frontend/components/organisms/collaboard-panel/collaboard-panel.php \
        public/packages/inno_frontend/components/organisms/project-space-sidebar/project-space-sidebar.php \
        public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.php \
        public/packages/inno_frontend/components/organisms/hero-image/hero-image.php
git commit -m "a11y: add ARIA labels to organism components (timeline, collaboard, sidebar, grid, hero)"
```

---

### Task 7: Add ARIA attributes to molecule components

**Files:**
- Modify: `public/packages/inno_frontend/components/molecules/member-card/member-card.php:28`
- Modify: `public/packages/inno_frontend/components/molecules/project-card/project-card.php:28,42`

- [ ] **Step 1: Add `aria-label` to member-card**

In `member-card.php`, change line 28:

```php
// OLD:
<<?= $tagName ?><?= $linkAttr ?> class="member-card">
// NEW:
<<?= $tagName ?><?= $linkAttr ?> class="member-card" aria-label="<?= htmlspecialchars($userName) ?>">
```

- [ ] **Step 2: Add `aria-labelledby` to project-card article**

In `project-card.php`, change line 28:

```php
// OLD:
<article class="project-card<?= $isActive ? '' : ' project-card--completed' ?>">
// NEW:
<article class="project-card<?= $isActive ? '' : ' project-card--completed' ?>" aria-labelledby="project-card-title-<?= htmlspecialchars($id) ?>">
```

Change line 42:

```php
// OLD:
    <h3 class="project-card__title"><?= htmlspecialchars($title) ?></h3>
// NEW:
    <h3 class="project-card__title" id="project-card-title-<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($title) ?></h3>
```

- [ ] **Step 3: Run Storybook build to verify no breakage**

Run: `npm run storybook:build`
Expected: Exits 0 without errors

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/member-card/member-card.php \
        public/packages/inno_frontend/components/molecules/project-card/project-card.php
git commit -m "a11y: add ARIA labels to member-card and project-card molecules"
```

---

### Task 8: Verify low-priority presentational components

**Files:**
- Verify: `public/packages/inno_frontend/components/atoms/container/container.php`
- Verify: `public/packages/inno_frontend/components/atoms/divider/divider.php`
- Verify: `public/packages/inno_frontend/components/atoms/code/code.php`
- Verify: `public/packages/inno_frontend/components/atoms/caption/caption.php`
- Verify: `public/packages/inno_frontend/components/atoms/paragraph/paragraph.php`
- Verify: `public/packages/inno_frontend/components/molecules/accent-text/accent-text.php`
- Verify: `public/packages/inno_frontend/components/molecules/partner-logo/partner-logo.php`

- [ ] **Step 1: Read each presentational component**

Read all 7 files listed above. Verify they use semantic HTML:
- `container.php` → `<div>` wrapper (no ARIA needed, purely structural)
- `divider.php` → `<hr>` (semantic)
- `code.php` → `<code>` or `<pre>` (semantic)
- `caption.php` → semantic text element
- `paragraph.php` → `<p>` (semantic)
- `accent-text.php` → `<span>` or similar (decorative)
- `partner-logo.php` → `<img>` with `alt` attribute

- [ ] **Step 2: Verify partner-logo has alt text**

If `partner-logo.php` uses an `<img>` tag, confirm it has an `alt` attribute. If missing, add `alt="<?= htmlspecialchars($name ?? '') ?>"`.

- [ ] **Step 3: Mark as done**

Presentational components using semantic HTML don't need ARIA. The axe-core tests catch any violations. No changes needed unless Step 2 found a missing alt.

---

### Task 9: Verify docs/e2e-testing.md covers all topics

**Files:**
- Verify: `docs/e2e-testing.md`

- [ ] **Step 1: Read the file**

Run: Read `docs/e2e-testing.md`

Expected sections:
- Quick Start (Docker) — mentions `docker-compose.test.yml`
- Quick Start (Local Dev)
- Test Users — 5 users with roles
- Seed Data — categories, ideas, spaces
- Test Helpers — `loginAs`, `submitIdea`, `seedIdea`, `loginAsNewContext`, `registerUser`, mailpit helpers
- Writing New Tests
- CI Pipeline

- [ ] **Step 2: Mark as done if all sections present**

If the file covers Docker, seed data, mailpit, and all helpers, no changes needed.

---

### Task 10: Verify README.md covers new commands

**Files:**
- Verify: `README.md`

- [ ] **Step 1: Check for docker-compose.test.yml commands**

Run: `grep "docker-compose.test.yml\|docker compose.*test" README.md`
Expected: At least 2 matches (build/up and down commands)

- [ ] **Step 2: Check for make seed-e2e**

Run: `grep "seed-e2e" README.md`
Expected: At least 1 match in the Makefile helpers section

- [ ] **Step 3: Mark as done if both present**

---

### Task 11: Add `make seed-e2e` reference to docs/testing.md

**Files:**
- Modify: `docs/testing.md`

- [ ] **Step 1: Read the current file**

Run: Read `docs/testing.md`

Find the section that documents `make seed` (test data setup). The `make seed-e2e` command is missing.

- [ ] **Step 2: Add seed-e2e documentation**

Find the paragraph or section describing `make seed` and append a subsection for E2E seed data. Add after the existing seed documentation:

```markdown
### E2E Test Data

For E2E tests (Docker or local), use the dedicated E2E seed target:

```bash
make seed-e2e
```

This creates 5 test users (`user@e2e.test`, `moderator@e2e.test`, `expert@e2e.test`, `admin@e2e.test`, `initiator@e2e.test`), 3 categories, 6 ideas (one per status), and 4 project spaces (one per status). See `docs/e2e-testing.md` for full details.
```

- [ ] **Step 3: Commit**

```bash
git add docs/testing.md
git commit -m "docs: add make seed-e2e reference to testing.md"
```

---

### Task 12: Verify cross-cutting concerns docs are current

**Files:**
- Verify: `docs/cross-cutting-concerns/01-configuration.md`
- Verify: `docs/cross-cutting-concerns/02-logging.md`
- Verify: `docs/cross-cutting-concerns/03-error-handling.md`
- Verify: `docs/cross-cutting-concerns/04-security-auth.md`
- Verify: `docs/cross-cutting-concerns/05-caching.md`
- Verify: `docs/cross-cutting-concerns/06-transaction-management.md`
- Verify: `docs/cross-cutting-concerns/07-monitoring-metrics.md`

- [ ] **Step 1: Spot-check each doc against actual code**

For each doc, read the first 30 lines and verify the interfaces/classes it references still exist in the codebase:

Run for each doc:
```bash
# Example: check that interfaces mentioned in 03-error-handling.md exist
grep -oP 'ExceptionHandler\w+' docs/cross-cutting-concerns/03-error-handling.md | sort -u | while read cls; do grep -r "$cls" public/packages/inno_*/src/ --include="*.php" -l | head -1; done
```

- [ ] **Step 2: Note any discrepancies**

If a doc references a class/interface that no longer exists, note it. If the doc is broadly accurate, mark as done. Minor drift (e.g. a renamed method) can be fixed as a follow-up.

- [ ] **Step 3: Mark as done**

The cross-cutting docs were last updated May 5 / Jun 2. If spot-checks pass, they're current enough. The spec says "verify" not "rewrite."

---

### Task 13: Verify CLAUDE.md package table accuracy

**Files:**
- Verify: `CLAUDE.md` (the package/event table at the top)

- [ ] **Step 1: Cross-reference events in CLAUDE.md against actual event classes**

Run: `ls public/packages/inno_shared/src/Domain/Event/`

Verify all event classes listed in the CLAUDE.md table exist, and no new events have been added that aren't documented.

- [ ] **Step 2: Cross-reference controllers against routes**

Run: `grep -r "class.*ApiController" public/packages/inno_*/src/Adapter/In/Http/ --include="*.php" -l`

Verify all controllers are accounted for in the package table.

- [ ] **Step 3: Mark as done if table is accurate**

---

## Summary

After completing all 13 tasks:

- **OpenAPI:** All 6 controllers fully annotated, spec regenerated, CI staleness check confirmed
- **Accessibility:** ARIA labels on 7 components (5 organisms, 2 molecules), axe-core disabled rules verified, presentational components audited
- **Documentation:** `testing.md` updated with `seed-e2e`, `e2e-testing.md` and `README.md` verified, cross-cutting docs spot-checked, CLAUDE.md table verified
- **CI:** OpenAPI staleness and markdown-link-check both confirmed in Stage 1

**Next:** Phase 4 (Integration Tests)
