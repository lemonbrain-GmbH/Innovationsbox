# Code Quality, Testing, Security & CI/CD Pipeline

**Date:** 2026-04-13
**Branch:** feature/ddd-package-struktur
**Status:** Approved

## Context

The Innovationsbox project has a solid test foundation (Pest unit/integration, Playwright E2E, Storybook component tests) and a Bitbucket Pipelines deployment pipeline (tag/staging → package distribution). However, there are no quality gates: no linting, no formatting, no security scanning, no PR validation, no coverage enforcement. Documentation is minimal (README.md is near-empty, no CONTRIBUTING/SECURITY/TESTING guides).

This spec defines a comprehensive code quality, security, and CI/CD setup across 5 implementation plans.

---

## 1. Pipeline Architecture

### Existing (unchanged)

- **Tag push:** 5 parallel steps distributing packages to external Bitbucket repos
- **Staging branch push:** same distribution to staging branches

### New: PR Validation Pipeline

Triggered on pull requests targeting `main` or `staging`. Fail-fast — cheapest checks run first.

```
Stage 1: Lint & Format (parallel steps)
  ├── eslint + prettier (TS/JS)
  ├── stylelint (SCSS)
  └── phpstan + php-cs-fixer --dry-run (PHP)

Stage 2: Type Check
  └── tsc --noEmit

Stage 3: Test + Coverage (parallel steps)
  ├── pest --coverage --min=80 (PHP unit tests)
  ├── npm run production (asset build check)
  └── storybook smoke tests (PHP server + Playwright)

Stage 4: Security
  ├── npm audit --audit-level=high
  ├── composer audit
  └── semgrep --config=auto
```

### New: Staging Post-Deployment

After the existing staging distribution, add a step that runs integration + E2E tests against the deployed staging environment (when the infrastructure supports it).

### New: Weekly Schedule

A scheduled pipeline that runs `npm outdated` and `composer outdated`, posting results for visibility.

### Caching

`node_modules/` and `vendor/` cached via Bitbucket Pipelines caching between runs.

### SSH Deployment Keys

Replace the 5 hardcoded OAuth tokens in `bitbucket-pipelines.yml` with SSH deployment keys configured in Bitbucket repository settings. Each target package repo gets a read/write deployment key.

---

## 2. Linting & Formatting

### JavaScript/TypeScript

- **ESLint** — flat config (`eslint.config.js`) with `@typescript-eslint/strict-type-checked`
- **Prettier** — formatting, integrated with ESLint via `eslint-config-prettier`
- Rules:
  - Import ordering (via `eslint-plugin-import`)
  - No unused variables (`@typescript-eslint/no-unused-vars: error`)
  - Cyclomatic complexity limit: 10
  - Consistent naming: camelCase for functions/variables, PascalCase for classes/enums

### SCSS

- **Stylelint** with `stylelint-config-standard-scss`
- Rules: property ordering, no duplicate selectors, color format consistency

### PHP

- **PHPStan level 6** with a baseline file (`phpstan-baseline.neon`)
  - Existing violations recorded in the baseline — they don't fail CI
  - New violations (not in baseline) fail CI immediately
  - Baseline is regenerated periodically to remove fixed violations
- **PHP CS Fixer** with `@PER-CS2.0` ruleset
  - `declare(strict_types=1)` enforced
  - Ordered imports, consistent spacing
  - Runs as `--dry-run --diff` in CI (reports violations without fixing)

### Pre-commit Hooks

- **husky** + **lint-staged** for auto-formatting on commit
- Prettier for TS/JS/JSON, PHP CS Fixer for PHP, Stylelint for SCSS
- **detect-secrets** for secret detection (scans staged files for API keys, tokens, passwords)
- Developers can bypass with `--no-verify`; CI catches everything

### npm Scripts

```json
"lint": "eslint resources/js/ --ext .ts",
"lint:fix": "eslint resources/js/ --ext .ts --fix",
"format": "prettier --check 'resources/**/*.{ts,js,json}'",
"format:fix": "prettier --write 'resources/**/*.{ts,js,json}'",
"lint:scss": "stylelint 'resources/**/*.scss'",
"lint:scss:fix": "stylelint 'resources/**/*.scss' --fix",
"lint:php": "php vendor/bin/phpstan analyse && php vendor/bin/php-cs-fixer fix --dry-run --diff",
"lint:php:fix": "php vendor/bin/php-cs-fixer fix",
"lint:all": "npm run lint && npm run format && npm run lint:scss && npm run lint:php",
"license:check": "license-checker --production --failOn 'GPL-3.0;AGPL-3.0'"
```

---

## 3. Testing in CI

### What runs in the PR pipeline

| Test type | Tool | CI stage | Environment |
|---|---|---|---|
| PHP unit tests | Pest `--testsuite=Unit --coverage --min=80` | Stage 3 | Docker `php:8.3-cli` + Composer + xdebug |
| Asset build | `npm run production` | Stage 3 | Node 20 |
| Storybook smoke | PHP story server + Playwright | Stage 3 | Node 20 + PHP 8.3 |

### What does NOT run in PR pipeline

- **PHP integration tests** — need MySQL + CMS install, reserved for staging post-deployment
- **Full E2E tests** — need Docker Compose + running app, reserved for staging

### Coverage

- Pest `--coverage --min=80` fails the build if line coverage drops below 80%
- Coverage report as Clover XML, uploaded as pipeline artifact
- Measured packages: `public/packages/inno_*/src/`

### Storybook smoke in CI

- Starts PHP story server as background process (`php -S localhost:6007`)
- Runs Playwright storybook specs via `request` fixture (API-level, no browser)
- Verifies all component partials render without errors

### Docker image for PHP steps

`php:8.3-cli` with:
- Composer pre-installed
- xdebug extension (for coverage)
- pdo_mysql extension (for future integration tests)

---

## 4. Security

### In the PR pipeline (Stage 4)

| Tool | Checks | Fails on |
|---|---|---|
| `npm audit --audit-level=high` | npm dependency vulnerabilities | High or critical severity |
| `composer audit` | PHP dependency vulnerabilities | Any advisory |
| `semgrep --config=auto` | SAST: SQL injection, XSS, path traversal | Error-level findings |

### One-time fixes

- Remove `.htpasswd` from tracked files, add to `.gitignore`
- Replace 5 hardcoded OAuth tokens with SSH deployment keys
- Add `SECURITY.md` with responsible disclosure process

### Pre-commit secret detection

- `detect-secrets` integrated via husky pre-commit hook
- Scans staged files for: API keys, tokens, passwords, private keys
- Does not run in CI (pre-commit is sufficient)

### License compliance

- `license-checker` as manual npm script (`npm run license:check`)
- Not enforced in CI (MIT project, well-known dependencies)

### Not adding (YAGNI)

- No Snyk/Socket.dev (npm audit + composer audit + semgrep suffice)
- No container scanning (no Dockerfile)
- No CSP headers (Concrete CMS manages its own)

---

## 5. Documentation

### Project repo (`data/app/project/`)

| File | Content |
|---|---|
| `README.md` | Complete rewrite: project overview, prerequisites (Nix + Docker), local setup steps, available npm/composer/make scripts, environment variables from `.env.dist`, architecture summary (DDD + hexagonal + Atomic Design), links to arc42 docs |
| `CONTRIBUTING.md` | How to contribute: branch naming conventions, PR process, commit message format, code style tools and how to run them locally, pre-commit hook setup |
| `SECURITY.md` | Responsible disclosure: how to report vulnerabilities, security contact email, supported versions, response timeline |
| `TESTING.md` | Testing strategy: pyramid (unit → integration → E2E → Storybook), how to write tests for each level, naming conventions, folder structure, 80% coverage requirement, how to run tests locally |

### Documentation repo (`innovationsbox-dokumentation/`)

| arc42 Section | What to add |
|---|---|
| **07 Deployment View** | CI/CD pipeline overview: PR validation stages (lint → type-check → test → security), staging deployment flow, package distribution. Pipeline diagram. Caching strategy. SSH key setup. |
| **08 Concepts** | New section "8.11 Code-Qualität und Entwicklungsprozess": ESLint/Prettier/Stylelint/PHPStan/PHP CS Fixer configuration rationale, pre-commit hooks, code review checklist |
| **10 Quality Requirements** | Extend with: 80% coverage requirement, PHPStan level 6 policy, security scanning policy (npm audit + composer audit + semgrep), quality gate definitions for PR merge |
| **11 Technical Risks** | Add: dependency vulnerability risk (mitigated by audit + weekly outdated reports), credential exposure risk (mitigated by SSH keys + detect-secrets), test coverage regression risk (mitigated by 80% threshold) |

### Unchanged

- `CLAUDE.md` — already comprehensive, no changes needed
- arc42 sections 01-06, 09, 12 — not affected

---

## 6. Implementation Plan Decomposition

Five sequential plans:

| Plan | Scope | Depends on |
|---|---|---|
| **Plan 1: Pipeline skeleton + SSH keys** | PR validation pipeline with stub stages, caching, SSH key migration, `.htpasswd` fix | Nothing |
| **Plan 2: Linting & formatting** | ESLint, Prettier, Stylelint, PHPStan, PHP CS Fixer, pre-commit hooks. Fill in pipeline Stage 1+2 | Plan 1 |
| **Plan 3: Testing in CI** | Pest coverage in pipeline, asset build check, Storybook smoke in CI. Fill in pipeline Stage 3 | Plan 2 |
| **Plan 4: Security scanning** | npm audit, composer audit, semgrep, detect-secrets hook, SECURITY.md. Fill in pipeline Stage 4 + weekly schedule | Plan 3 |
| **Plan 5: Documentation** | README.md, CONTRIBUTING.md, TESTING.md, arc42 updates (sections 07, 08, 10, 11) | Plan 4 |

---

## 7. What Changes, What Stays

### New files

- `eslint.config.js`, `.prettierrc`, `.stylelintrc.json` — linter configs
- `phpstan.neon`, `phpstan-baseline.neon`, `.php-cs-fixer.dist.php` — PHP quality configs
- `.husky/pre-commit` — pre-commit hook
- `.detect-secrets.baseline` — secret detection baseline
- `README.md` — rewritten
- `CONTRIBUTING.md`, `SECURITY.md`, `TESTING.md` — new
- Pipeline stages in `bitbucket-pipelines.yml` — extended

### Modified

- `bitbucket-pipelines.yml` — add PR triggers, quality stages, caching, remove hardcoded tokens
- `package.json` — add lint/format/security scripts, husky + lint-staged
- `composer.json` — add phpstan + php-cs-fixer dev dependencies
- `flake.nix` — add semgrep, detect-secrets to dev shell
- `.gitignore` — add `.htpasswd`, linter caches

### Unchanged

- All source code (PHP packages, TS, SCSS) — no code changes, only config
- `CLAUDE.md` — already comprehensive
- Existing test files — no modifications
- Existing deployment pipeline (tag/staging triggers)
