# Comprehensive Quality Audit — Design Spec

**Date:** 2026-06-02
**Goal:** Internal quality gate — block feature work until documentation, tests, Storybook/accessibility, and security all meet the required standard.
**Approach:** Bottom-up (Foundation → Surface) — fix security and test infrastructure first, then Storybook/a11y, then documentation, then verify everything.

---

## Current State Summary

| Area | State | Key Concerns |
|---|---|---|
| Documentation | Largely fresh (last updates May 2026), 29 specs, arc42 current | Cross-cutting docs older (April 2026), accuracy unverified |
| Unit tests | 41 files, low coverage: notification 17%, moderation 19%, shared 18% | Coverage threshold not enforced |
| Integration tests | Structure exists, zero actual test implementations | Major gap |
| E2E tests | 21 Playwright specs covering key flows + Storybook smoke | 15 Storybook specs vs 63 components |
| Storybook | 63 components, 100% file completeness, no duplicates, tokens consistent | No automated a11y CI gate |
| Accessibility | Good aria patterns in PHP, a11y addon enabled | 3 rules disabled, no dedicated a11y test suite, no WCAG audit |
| Security | Parameterized queries, role-based auth, output escaping with `h()` | 40 npm vulns (6 critical), no explicit CSRF, no DOMPurify, semgrep not installed |

---

## Phase 1: Security Hardening

### 1.1 — npm Vulnerability Remediation
- Run `npm audit` and triage all 40 vulnerabilities
- Upgrade direct dependencies where possible (update ws, pnotify; for bootstrap 3 XSS vulns: assess whether the vulnerable Popover/Tooltip components are used — if not, document as mitigated; if yes, evaluate targeted patching or upgrading)
- For Concrete CMS transitive deps (CKEditor 4, babel plugins): document in `SECURITY-KNOWN-ISSUES.md` with risk assessment and mitigation notes
- Run `composer audit` and resolve any PHP-side vulnerabilities
- Ensure `npm run audit:npm` passes CI (high/critical = fail)

### 1.2 — CSRF Protection
- Audit all custom API endpoints across inno_moderation, inno_project_idea, inno_project_space, inno_notification
- Implement Concrete CMS's built-in CSRF token validation (`$token->validate()`) on all state-changing endpoints (POST/PUT/DELETE)
- Ensure frontend JS sends the CSRF token with API requests (add to shared fetch/axios config)

### 1.3 — Input Validation
- Add explicit validation in API controllers (or use case handlers) for all user-facing inputs — at minimum: type checks, length limits, allowed values
- Sanitize user-provided HTML content if any rich-text fields exist

### 1.4 — Frontend XSS Prevention
- Audit all TypeScript code for `innerHTML` or dynamic DOM insertion patterns
- Add DOMPurify where user-generated content is rendered client-side
- Verify `h()` helper is used consistently in all PHP templates for output escaping

### 1.5 — Security Headers
- Add middleware or config for: `X-Content-Type-Options: nosniff`, `Strict-Transport-Security`, `X-XSS-Protection: 0` (modern approach), `Referrer-Policy`
- Verify existing CSP from Concrete CMS is adequate

### 1.6 — Tooling
- Get semgrep installed and `npm run audit:sast` working
- Verify `detect-secrets` pre-commit hook is functional
- Remove any hardcoded credentials from `.env.dist`

---

## Phase 2: Test Infrastructure

### 2.1 — Enforce Coverage Threshold in CI
- Configure Pest/PHPUnit to enforce 80% line coverage minimum on `public/packages/inno_*/src/`
- Ensure CI fails on coverage drops — add `--min` flag or equivalent to the test command
- Add coverage report output (Clover/HTML) for developer visibility

### 2.2 — Establish Integration Test Bootstrap
- The integration test structure exists (`tests/integration/` with per-package folders) but has zero actual tests
- Create a working integration test bootstrap that spins up the CMS context with a real database connection
- Write one exemplary integration test per package to establish the pattern: test a persistence adapter against the real DB
- Document the setup in `docs/testing.md` (DB config, how to run, how to seed)

### 2.3 — Unit Test Patterns and Gaps
- Establish a consistent test pattern per package: every use case handler gets a test, every event handler gets a test, every port implementation gets a test
- Create test templates/examples showing how to mock `Port/Out/` interfaces with Mockery
- Identify highest-risk untested code (use case handlers, event handlers) and write tests for those
- Focus areas by current gap: inno_notification (1 test / 6 src), inno_moderation (4/21), inno_project_idea (5/22)

### 2.4 — E2E Test Improvements
- Verify Playwright config targets the correct local URL and is documented for new developers
- Ensure Storybook smoke tests in `tests/e2e/specs/storybook/` cover all 63 components (currently 15 specs — check for gaps)
- Add axe-core accessibility assertions to E2E tests (integrates with Phase 3)

### 2.5 — CI Gate Alignment
- Ensure `composer test`, `npm run test:ci`, and coverage checks all run in the PR pipeline
- Verify the 4-stage pipeline (lint → typecheck → test → security) reflects all new checks from Phase 1 and 2

---

## Phase 3: Storybook & Accessibility

### 3.1 — Storybook as Single Source of Truth
- Audit for component drift: verify every CMS block template in `public/packages/inno_frontend/blocks/` uses a component partial from the component library (no inline HTML that bypasses components)
- Check that single-page templates (`inno_frontend/single_pages/`) also use component partials where possible
- Verify no duplicate component definitions exist outside the component library
- Ensure the webpack mapping table in `webpack.mix.js` covers all components that need compiled assets

### 3.2 — Design Token Consistency
- Audit all 63 component SCSS files for hardcoded values (colors, spacing, font sizes, radii, durations) that should use token variables
- Verify `tokens.json` is the single source — no competing variable definitions in `resources/css/`
- Check that `npm run tokens` regeneration produces SCSS that matches what components actually import
- Document the token usage contract: which tokens exist, what they're for, naming conventions

### 3.3 — Automated Accessibility (axe-core in CI)
- Add axe-core testing to Storybook: run against all 63 components in CI via the existing Storybook smoke test infrastructure
- Review the 3 currently disabled a11y rules (landmark-one-main, page-has-heading-one, region) — document why they're disabled
- Set a zero-violations baseline: all components must pass axe-core with no violations at WCAG 2.1 AA level
- Add axe-core assertions to the Playwright E2E specs for full-page checks

### 3.4 — Manual WCAG 2.1 AA Audit
- **Keyboard navigation:** verify all interactive components (button, input, select, checkbox, radio, toggle, dropdown, modal, tabs, pagination, search-bar, navigation-drawer) are fully keyboard-operable
- **Focus management:** check focus trapping in modal, focus restoration after dropdown close, visible focus indicators on all interactive elements
- **Color contrast:** verify all text/background combinations meet AA ratios (4.5:1 normal text, 3:1 large text) using the token color palette
- **Screen reader:** verify aria attributes are correct and complete — check label associations, live regions, role usage
- Document findings as a checklist, fix violations found

### 3.5 — Corporate Design Consistency
- Verify all components use the design token palette exclusively — no off-brand colors
- Check typography consistency: heading hierarchy, font weights, sizes follow a single scale from tokens
- Verify spacing rhythm: components use the spacing scale consistently (no arbitrary pixel values)
- Check visual consistency across component variants (e.g., all "primary" variants use the same brand color)

---

## Phase 4: Documentation Reconciliation

### 4.1 — API Documentation (OpenAPI)
- Run `composer openapi:generate` and compare output against committed spec — flag any drift
- Verify every API endpoint in the 4 domain packages has OpenAPI attributes (path, method, parameters, request/response schemas, security)
- Run `npm run lint:api` (Spectral) and fix any violations
- Ensure Swagger UI at `/api/docs` renders correctly and matches the codebase

### 4.2 — Arc42 Typst Documentation Accuracy
- Verify architecture diagrams (context, building blocks, runtime views) reflect the current 5-package structure with events and API endpoints
- Check that the event table in the docs matches actual event classes in `inno_shared/src/Event/`
- Verify deployment view reflects the current CI pipeline (4-stage PR, tag push, staging push)
- Confirm quality requirements and technical risks are still accurate
- Ensure `typst compile` succeeds without errors on all .typ files

### 4.3 — Markdown Documentation Completeness
- **Cross-cutting docs** (`docs/cross-cutting-concerns/`): verify each file (security-auth, logging, error-handling, caching, etc.) reflects actual implementation patterns, not aspirational
- **`docs/testing.md`**: update with integration test setup from Phase 2, coverage enforcement details, and E2E prerequisites
- **`docs/auth-pages.md`**: verify symlink instructions and template paths match current code
- **`README.md`**: verify setup instructions, commands, and prerequisites are accurate
- **`CONTRIBUTING.md`**: verify it references the current CI pipeline, linting commands, and test requirements
- **`SECURITY.md`**: update with any new security measures from Phase 1

### 4.4 — CLAUDE.md Accuracy
- Verify the package table (handles, APIs, events) still matches code
- Verify the component library listing matches actual components (confirm after any Phase 3 changes)
- Verify all listed commands still work as documented
- Update if any phases introduced new commands, scripts, or conventions

### 4.5 — Structural Health
- Check for broken internal links in markdown files
- Verify all doc file references in CLAUDE.md and README point to existing files
- Ensure no orphaned docs exist (files not referenced from anywhere)

---

## Phase 5: Final Verification

### 5.1 — Full CI Simulation
- Run the complete CI pipeline locally: `npm run lint:all` → `npm run typecheck` → `composer test` with coverage → `npm run test:ci` → security checks (`npm run audit:npm`, `composer audit`, `npm run audit:sast`)
- All stages must pass green — no skipped checks, no suppressed warnings

### 5.2 — Storybook Build Verification
- `npm run storybook:build` succeeds without errors
- All 63 components render correctly
- axe-core reports zero violations

### 5.3 — Production Build Verification
- `npm run production` produces clean output
- No missing assets, no compilation warnings
- Built assets match what the theme controller registers

### 5.4 — Documentation Compilation
- `typst compile` succeeds on all .typ files
- OpenAPI spec generates and lints clean
- No broken links in markdown

### 5.5 — Quality Gate Checklist
Final pass confirming every item from all phases:

- [ ] All npm/composer vulnerabilities resolved or documented
- [ ] CSRF token validation on all state-changing API endpoints
- [ ] Input validation on all user-facing API inputs
- [ ] No unescaped user content in PHP templates or JS
- [ ] Security headers configured (X-Content-Type-Options, HSTS, Referrer-Policy)
- [ ] Semgrep operational, detect-secrets functional
- [ ] No hardcoded credentials in .env.dist
- [ ] Test coverage threshold enforced in CI at 80%
- [ ] Integration test bootstrap working with exemplary tests per package
- [ ] Critical unit test paths covered (use case handlers, event handlers)
- [ ] Storybook smoke tests cover all 63 components
- [ ] axe-core accessibility gate in CI — zero AA violations
- [ ] WCAG 2.1 AA manual audit complete (keyboard, focus, contrast, screen reader)
- [ ] Design tokens used consistently, no hardcoded values in component SCSS
- [ ] All CMS block templates use component partials (no drift)
- [ ] OpenAPI spec matches code, Spectral lint clean
- [ ] Arc42 typst docs accurate and compile clean
- [ ] All markdown docs accurate against current code
- [ ] CLAUDE.md fully accurate
- [ ] No broken links, no orphaned docs
- [ ] Full CI pipeline passes green locally
- [ ] Production build clean
- [ ] Storybook build clean
