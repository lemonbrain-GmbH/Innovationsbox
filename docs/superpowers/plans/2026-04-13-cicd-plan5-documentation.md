# CI/CD Plan 5: Documentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create comprehensive project documentation (README, CONTRIBUTING, TESTING) and update the arc42 documentation repository with CI/CD pipeline, code quality concepts, quality requirements, and risk updates.

**Architecture:** Project docs live in the app repo root. Arc42 documentation lives in the sibling `innovationsbox-dokumentation` repo and follows the established Typst format. Each arc42 section is updated independently.

**Tech Stack:** Markdown (project docs), Typst (arc42 docs)

**Spec:** `docs/superpowers/specs/2026-04-13-code-quality-cicd-design.md`

**Plan series:** Plan 5 of 5 (final). Plans 1-4 are complete.

---

### Task 1: Rewrite README.md

The current README is 14 lines listing site attributes. Replace with a comprehensive project overview.

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Read the current `README.md`**

Read `README.md` to understand what exists.

- [ ] **Step 2: Replace with comprehensive README**

```markdown
# Innovationsbox

A moderated platform for [klimaglarus](https://klimaglarus.ch) / [arento](https://arento.ch) where climate-relevant project ideas can be submitted, reviewed, published, and collaboratively developed in protected project spaces.

**Flow:** idea submitted → moderation review → published publicly → interested members join → collaborate in a project space.

## Tech Stack

- **CMS:** Concrete CMS 9.4+
- **Backend:** PHP 8.2+, hexagonal architecture (Ports & Adapters), DDD bounded contexts
- **Frontend:** SCSS + vanilla TypeScript, Atomic Design component library, Storybook
- **Testing:** Pest (PHP), Playwright (E2E + Storybook smoke)
- **CI/CD:** Bitbucket Pipelines (lint → type-check → test → security)

## Prerequisites

- [Nix](https://nixos.org/download.html) with flakes enabled (provides Node.js 20, PHP 8.3, semgrep, detect-secrets)
- Docker + Docker Compose (for MySQL + PHP-FPM in development)

## Local Setup

```bash
# 1. Clone and enter the project
git clone <repo-url> && cd innovationsbox/data/app/project

# 2. Enter the Nix development shell
nix develop

# 3. Copy environment config
cp .env.dist .env
# Edit .env with your database credentials

# 4. Install dependencies
npm install
composer install

# 5. Build frontend assets
npm run tokens      # Generate SCSS from design tokens
npm run development # Build CSS + JS

# 6. Install Concrete CMS (interactive)
./vendor/bin/concrete c5:install -i

# 7. (Optional) Start Storybook
npm run storybook   # Opens at http://localhost:6006
```

## Available Scripts

### npm
| Command | Description |
|---------|-------------|
| `npm run development` | Build frontend assets (dev) |
| `npm run production` | Build frontend assets (minified) |
| `npm run watch` | Watch mode with BrowserSync |
| `npm run tokens` | Regenerate SCSS from `tokens.json` |
| `npm run storybook` | Start Storybook + PHP story server |
| `npm run lint` | ESLint for TypeScript |
| `npm run format` | Prettier check |
| `npm run lint:scss` | Stylelint for SCSS |
| `npm run lint:all` | Run all JS/TS/SCSS checks |
| `npm run typecheck` | TypeScript type check |
| `npm run test:e2e` | Playwright E2E tests |
| `npm run test:ci` | Full CI test suite locally |
| `npm run audit:npm` | npm vulnerability scan |
| `npm run audit:sast` | Semgrep SAST scan |

### composer
| Command | Description |
|---------|-------------|
| `composer test` | Run PHP unit tests |
| `composer test:coverage` | Unit tests + 80% coverage |
| `composer test:integration` | Integration tests (needs DB) |
| `composer lint:php` | PHPStan + PHP CS Fixer check |
| `composer lint:cs:fix` | Auto-fix PHP formatting |
| `composer audit` | PHP vulnerability scan |

### make
| Command | Description |
|---------|-------------|
| `make create` | composer install + permissions |
| `make install` | Install Concrete CMS |
| `make export` | Export DB + files to archive |
| `make import` | Import archive |
| `make update` | Composer update + CMS update |
| `make clean` | Maintenance: cache, permissions |

## Environment Variables

See `.env.dist` for all available variables:

| Variable | Description |
|----------|-------------|
| `DB_USERNAME` | Database user |
| `DB_PASSWORD` | Database password |
| `DB_DATABASE` | Database name |
| `DB_HOSTNAME` | Database host (default: 127.0.0.1) |
| `CONCRETE5_ENV` | Set to `"live"` for production config |
| `PROJECT_NAME` | Used for BrowserSync proxy |
| `APP_PORT` | Used for BrowserSync proxy |

## Architecture

The project follows DDD with hexagonal architecture. Five domain packages communicate via events through a shared kernel.

```
public/packages/
  inno_shared/          ← Cross-package event contracts
  inno_project_idea/    ← Project idea lifecycle
  inno_project_space/   ← Collaboration spaces
  inno_moderation/      ← Review queue
  inno_notification/    ← Email notifications
  inno_frontend/        ← Theme + Atomic Design components
```

Each domain package follows `Domain/ → Application/ → Port/In + Port/Out → Adapter/In + Adapter/Out`.

Full architecture documentation: see the [arc42 docs](../innovationsbox-dokumentation/) (Typst format).

## CI/CD Pipeline

PRs are validated in 4 stages: **lint → type-check → test + coverage → security**. See [CLAUDE.md](CLAUDE.md) for details.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md).

## License

MIT

### Site Attributes

| Handle | Type | Name |
|--------|------|------|
| main_logo | Image/File | Logo (SVG) |
| logo_arento | Image/File | Logo Arento (Footer) |
| logo_klimaregion_gl | Image/File | Logo Klimaregion Glarnerland (Footer) |
| logo_klimaglarus | Image/File | Logo Klimaglarus (Footer) |
| arento_link | URL | Link Arento (Footer) |
| klimaregion_link | URL | Link Klimaregion Glarnerland (Footer) |
| klimaglarus_link | URL | Link Klimaglarus (Footer) |
```

- [ ] **Step 3: Commit**

```bash
git add README.md
git commit -m "docs: rewrite README.md with setup, scripts, architecture, and environment"
```

---

### Task 2: Create CONTRIBUTING.md

**Files:**
- Create: `CONTRIBUTING.md`

- [ ] **Step 1: Create `CONTRIBUTING.md`**

```markdown
# Contributing to Innovationsbox

Thank you for your interest in contributing to the Innovationsbox platform!

## Getting Started

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Set up the development environment (see [README.md](README.md))
4. Make your changes
5. Submit a pull request

## Branch Naming

- `feature/<name>` — new features
- `fix/<name>` — bug fixes
- `refactor/<name>` — code improvements without behavior changes
- `docs/<name>` — documentation updates

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(scope): short description
fix(scope): short description
refactor(scope): short description
docs(scope): short description
test(scope): short description
```

Scopes: `atoms`, `molecules`, `organisms`, `templates`, `pages`, `tokens`, `blocks`, `ci`, `lint`, `security`, `test`, or a package name like `inno_project_idea`.

## Code Quality

All code must pass the automated quality checks before merge:

### Before committing
Pre-commit hooks (husky + lint-staged) auto-format your code. If you need to bypass: `git commit --no-verify`.

### Before opening a PR
Run the full check locally:
```bash
npm run lint:all          # ESLint + Prettier + Stylelint + TypeScript
composer lint:php         # PHPStan + PHP CS Fixer
composer test             # PHP unit tests
```

### What CI checks
1. **Lint & Format** — ESLint, Prettier, Stylelint, PHPStan, PHP CS Fixer
2. **Type Check** — `tsc --noEmit`
3. **Test & Coverage** — Pest unit tests (80% minimum), asset build, Storybook smoke
4. **Security** — npm audit, composer audit, semgrep SAST

## Adding Components (Atomic Design)

Components live in `resources/components/` following Atomic Design:

```
atoms/       → smallest building blocks (button, heading, image, ...)
molecules/   → simple groups of atoms (feature-button, page-card, ...)
organisms/   → complex sections (hero-image, header, footer, ...)
templates/   → page layouts
pages/       → example pages with real content
```

Each component needs 4 files:
- `<name>.php` — pure PHP partial (receives `$props` array, outputs HTML)
- `<name>.variants.php` — defines valid props and default values
- `<name>.stories.json` — Storybook story definition
- `<name>.scss` — component-specific styles

### Testing your component

```bash
# Start the PHP story server
php -S localhost:6007 storybook-server/index.php &

# Verify it renders
curl "http://localhost:6007/?id=atoms/your-component&prop=value"

# Or start Storybook to see it visually
npm run storybook
```

## PHP Architecture

Domain packages follow hexagonal architecture:
- `Domain/` — pure PHP entities, no framework dependencies
- `Application/UseCase/` — command + handler pairs, implement `Port/In/` interfaces
- `Port/In/` — primary port interfaces (one per use case)
- `Port/Out/` — secondary port interfaces (repositories, services)
- `Adapter/In/Http/` — REST controllers, inject `Port/In/` interfaces
- `Adapter/Out/Persistence/` — database implementations

## Questions?

Open an issue or contact the maintainers at lemonbrain.
```

- [ ] **Step 2: Commit**

```bash
git add CONTRIBUTING.md
git commit -m "docs: add CONTRIBUTING.md with code quality, component, and architecture guides"
```

---

### Task 3: Create TESTING.md

**Files:**
- Create: `TESTING.md`

- [ ] **Step 1: Create `TESTING.md`**

```markdown
# Testing Strategy

## Test Pyramid

```
         ╱ E2E (Playwright) ╲         — Critical user flows
        ╱ Storybook Smoke    ╲        — Component rendering
       ╱ Integration (Pest)    ╲      — Real DB, CMS bootstrapped
      ╱ Unit (Pest + Mockery)    ╲    — Pure PHP, mocked ports
```

## Test Types

### Unit Tests (PHP)

**Location:** `tests/unit/`
**Framework:** Pest v1 + Mockery
**Run:** `composer test` or `composer test:unit`

Unit tests verify domain logic and application use cases in isolation. They mock `Port/Out/` interfaces (repositories, services) and test against `Port/In/` interfaces.

**How to write a unit test:**

```php
<?php
// tests/unit/inno_project_idea/Domain/ProjectIdeaTest.php
declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;

test('submit() creates idea with Submitted status', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Description', 'tech', 'user_1');
    expect($idea->getStatus())->toBe(IdeaStatus::Submitted);
});
```

**For use case handlers — mock the ports:**

```php
<?php
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn () => Mockery::close());

test('handle() saves and dispatches', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $repo->shouldReceive('save')->once();
    $dispatcher->shouldReceive('dispatch')->once();

    $handler = new SubmitProjectIdeaHandler($repo, $dispatcher);
    $handler->handle(new SubmitProjectIdeaCommand('Title', 'Desc', 'cat', 'user_1'));
});
```

### Integration Tests (PHP)

**Location:** `tests/integration/`
**Run:** `composer test:integration`
**Requires:** Running MySQL + Concrete CMS bootstrapped

Integration tests verify that adapters work correctly with real infrastructure. Not run in the PR pipeline — reserved for staging post-deployment.

### Storybook Smoke Tests

**Location:** `tests/e2e/specs/storybook/`
**Run:** Requires PHP story server running on port 6007
**CI:** Run in pipeline Stage 3

These tests verify that all component partials render without errors by calling the PHP story server's HTTP API:

```typescript
test('button: primary renders with correct class', async ({ request }) => {
    const res = await request.get('http://localhost:6007/?id=atoms/button&variant=primary&label=Test');
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('class="button button--primary"');
});
```

### E2E Tests (Playwright)

**Location:** `tests/e2e/specs/`
**Run:** `npm run test:e2e` (requires running CMS)

End-to-end tests verify critical user flows through the REST API.

## Coverage

- **Threshold:** 80% line coverage, enforced in CI
- **Scope:** `public/packages/inno_*/src/` (all 5 DDD packages)
- **Run locally:** `composer test:coverage`
- **Driver:** pcov (in CI), xdebug (locally if installed)

## Folder Structure

```
tests/
  unit/
    inno_project_idea/
      Domain/                    ← Entity tests
      Application/               ← Use case handler tests
    inno_moderation/
      Domain/
      Application/
    inno_notification/
      Application/               ← Event handler tests
    inno_project_space/
      Domain/
      Application/
    helpers/                     ← ConcreteComposer helper tests
  integration/
    inno_project_idea/Adapter/Out/Persistence/
    inno_moderation/Adapter/Out/Persistence/
    inno_notification/Adapter/Out/Email/
    inno_project_space/Adapter/Out/Persistence/
  e2e/
    playwright.config.ts
    fixtures/helpers.ts
    specs/
      submit-idea.spec.ts
      moderation-flow.spec.ts
      storybook/
        smoke.spec.ts
        atoms.spec.ts
        molecules-organisms.spec.ts
        templates-pages.spec.ts
```

## Naming Conventions

- Test files: `*Test.php` (Pest), `*.spec.ts` (Playwright)
- Test names: describe the behavior, not the method: `'submit() creates idea with Submitted status'`
- One assertion focus per test — test one behavior, not multiple
- Use `afterEach(fn () => Mockery::close())` in every test file that uses Mockery
```

- [ ] **Step 2: Commit**

```bash
git add TESTING.md
git commit -m "docs: add TESTING.md with strategy, examples, and folder structure"
```

---

### Task 4: Update arc42 Section 07 (Deployment View) — CI/CD pipeline

Add a CI/CD pipeline subsection to the deployment view in the documentation repo.

**Files:**
- Modify: `/home/progressio/lemonbrain/git/innovationsbox-dokumentation/src/product/arc42/07_deployment_view.typ`

- [ ] **Step 1: Read the current file**

Read `07_deployment_view.typ` to find where to add the CI/CD section.

- [ ] **Step 2: Add CI/CD pipeline section before the help block**

Find the last `#if show-help` block at the end of the file (after the E-Mail-System section). Insert this new section BEFORE it:

```typst
// ---------------------------------------------------------
// 7.3 CI/CD Pipeline
// ---------------------------------------------------------

== CI/CD Pipeline (Bitbucket Pipelines)

Die Innovationsbox nutzt Bitbucket Pipelines für automatisierte Qualitätssicherung und Paket-Distribution.

=== Pull-Request-Validierung

Jeder Pull Request durchläuft vier sequentielle Stages (Fail-fast — günstigste Prüfungen zuerst):

```
Stage 1: Lint & Format
  ├── ESLint + Prettier (TypeScript)
  ├── Stylelint (SCSS)
  └── PHPStan + PHP CS Fixer (PHP)

Stage 2: Type Check
  └── tsc --noEmit

Stage 3: Test & Coverage
  ├── Pest Unit Tests (80% Coverage Minimum)
  ├── Asset Build Check (npm run production)
  └── Storybook Smoke Tests (Playwright)

Stage 4: Security
  ├── npm audit (high/critical)
  ├── composer audit
  └── semgrep SAST
```

=== Deployment

- *Tag Push:* Baut Produktions-Assets und verteilt alle 5 Packages parallel an externe Bitbucket-Repos (via SSH Deployment Keys)
- *Staging Branch:* Identischer Prozess, pusht auf `staging`-Branches der Package-Repos

=== Wöchentliche Dependency-Prüfung

Ein geplanter Pipeline-Lauf (montags 08:00) führt `npm outdated` und `composer outdated` aus und macht veraltete Abhängigkeiten sichtbar.

=== Caching

`node_modules/` und `vendor/` werden über Bitbucket Pipelines Caching zwischen Runs zwischengespeichert (Key-basiert auf `package-lock.json` bzw. `composer.lock`).
```

- [ ] **Step 3: Also update the package names in Section 7.2 to use inno_ prefix**

In the same file, find the Ebene 2 ASCII diagram and table that reference old package names. Update them:

Find:
```
| - frontend    (Theme / UI)                           |
| - project_idea                                       |
| - project_space                                      |
| - moderation                                         |
| - notification                                       |
```

Replace with:
```
| - inno_frontend    (Theme / UI)                      |
| - inno_shared      (Event Contracts)                 |
| - inno_project_idea                                  |
| - inno_project_space                                 |
| - inno_moderation                                    |
| - inno_notification                                  |
```

Also update the table in Ebene 1 (the Zuordnung table) — find `Package \`project_idea\`` etc. and add the `inno_` prefix.

- [ ] **Step 4: Commit (in the dokumentation repo)**

```bash
cd /home/progressio/lemonbrain/git/innovationsbox-dokumentation
git add src/product/arc42/07_deployment_view.typ
git commit -m "docs: add CI/CD pipeline to deployment view, update package names"
```

---

### Task 5: Update arc42 Section 10 (Quality Requirements)

Add quality scenarios for code coverage, code quality, and security scanning.

**Files:**
- Modify: `/home/progressio/lemonbrain/git/innovationsbox-dokumentation/src/product/arc42/10_quality_requirements.typ`

- [ ] **Step 1: Read the current file**

Read `10_quality_requirements.typ`.

- [ ] **Step 2: Add new quality scenarios after QS-04**

Find the last quality scenario (QS-04: Systemverfügbarkeit). Add these new scenarios after it:

```typst
=== QS-05: Testabdeckung
*Quelle:* Entwicklerteam
*Stimulus:* Neuer Code wird als Pull Request eingereicht
*Umgebung:* CI/CD Pipeline
*Reaktion:* Pipeline prüft die Code-Abdeckung der Unit Tests
*Antwortmass:* Mindestens 80% Zeilenabdeckung über alle Domain-Packages (`inno_*/src/`)

=== QS-06: Code-Qualität
*Quelle:* Entwicklerteam
*Stimulus:* Pull Request wird erstellt
*Umgebung:* CI/CD Pipeline
*Reaktion:* Pipeline führt statische Analyse und Formatierungsprüfung durch
*Antwortmass:* ESLint, Stylelint, PHPStan (Level 6), PHP CS Fixer und TypeScript Type Check müssen fehlerfrei bestanden werden

=== QS-07: Sicherheit von Abhängigkeiten
*Quelle:* Automatisierte Pipeline
*Stimulus:* Pull Request wird erstellt
*Umgebung:* CI/CD Pipeline
*Reaktion:* Pipeline prüft npm- und Composer-Abhängigkeiten auf bekannte Schwachstellen
*Antwortmass:* Keine Schwachstellen mit Schweregrad «high» oder «critical» (npm) bzw. kein Advisory (Composer)
```

- [ ] **Step 3: Add Code-Qualität to the quality overview table**

Find the table in "Übersicht der Qualitätsanforderungen". Add this row after the existing rows (before the closing `)`):

```typst
  [Code-Qualität],
  [
  Automatisierte Qualitätsprüfungen (Linting, Typenprüfung, Testabdeckung,
  Sicherheitsscanning) stellen sicher, dass der Code konsistent, typsicher
  und frei von bekannten Schwachstellen ist.
  ],
```

- [ ] **Step 4: Commit**

```bash
cd /home/progressio/lemonbrain/git/innovationsbox-dokumentation
git add src/product/arc42/10_quality_requirements.typ
git commit -m "docs: add code quality, coverage, and security quality scenarios"
```

---

### Task 6: Update arc42 Section 11 (Technical Risks)

Update T3 and T4 as resolved. Add new security-related risks.

**Files:**
- Modify: `/home/progressio/lemonbrain/git/innovationsbox-dokumentation/src/product/arc42/11_technical_risks.typ`

- [ ] **Step 1: Read the current file**

Read `11_technical_risks.typ`.

- [ ] **Step 2: Update T3 description and measures**

Find T3 (fehlende automatisierte Tests). Update its Massnahmen column:

Replace T3's Massnahmen:
```typst
  Einführung von Unit- und Integrationstests,
  Aufbau einer Teststrategie sowie Integration
  von Tests in den Entwicklungsprozess.
```

With:
```typst
  *Umgesetzt:* Unit Tests (Pest), Integration Tests, E2E Tests (Playwright),
  Storybook Smoke Tests. 80% Coverage-Schwelle in CI/CD Pipeline enforced.
  Teststrategie dokumentiert in TESTING.md.
```

- [ ] **Step 3: Update T4 description and measures**

Find T4 (keine Deployment-Pipeline). Update its Massnahmen column:

Replace T4's Massnahmen:
```typst
  Aufbau einer CI/CD-Pipeline mit automatisierten
  Builds, Tests und Deployments.
```

With:
```typst
  *Umgesetzt:* Bitbucket Pipelines mit 4-Stage PR-Validierung
  (Lint → Type Check → Test + Coverage → Security),
  Tag/Staging-Deployment, wöchentlicher Dependency-Report.
  SSH Deployment Keys statt OAuth Tokens.
```

- [ ] **Step 4: Add new security risks after the existing entries**

Add these rows to the risk table (before the closing `)`):

```typst
  [R6],
  [mittel],
  [
  Schwachstellen in npm- oder Composer-Abhängigkeiten
  könnten die Plattform gefährden.
  ],
  [
  *Umgesetzt:* `npm audit` und `composer audit` laufen
  bei jedem PR. Semgrep SAST scannt PHP und TypeScript.
  Wöchentlicher Outdated-Report für proaktive Updates.
  ],

  [R7],
  [mittel],
  [
  Versehentliches Einchecken von Zugangsdaten oder
  API-Schlüsseln in das Repository.
  ],
  [
  *Umgesetzt:* `detect-secrets` Pre-Commit-Hook verhindert
  das Committen von erkannten Secrets. `.htpasswd` aus Git
  entfernt und in `.gitignore` aufgenommen. OAuth Tokens
  durch SSH Deployment Keys ersetzt.
  ],
```

- [ ] **Step 5: Commit**

```bash
cd /home/progressio/lemonbrain/git/innovationsbox-dokumentation
git add src/product/arc42/11_technical_risks.typ
git commit -m "docs: update T3/T4 as resolved, add R6/R7 security risks"
```

---

### Task 7: Update arc42 Section 08 (Concepts) — code quality concept

Add a new subsection about code quality and development process.

**Files:**
- Modify: `/home/progressio/lemonbrain/git/innovationsbox-dokumentation/src/product/arc42/08_concepts.typ`

- [ ] **Step 1: Read the current file to find the right location**

Read `08_concepts.typ`. There should already be a section "8.9 Frontend-Architektur und Design System" (added in the Storybook documentation update) and "8.10 Architektur- und Entwicklungsgrundsätze".

- [ ] **Step 2: Add section 8.11 after 8.10**

Find the `== Architektur- und Entwicklungsgrundsätze` section. After its last bullet point (before the `#if show-help` block), add:

```typst
// ---------------------------------------------------------
// 8.11 Code-Qualität und Entwicklungsprozess
// ---------------------------------------------------------

== Code-Qualität und Entwicklungsprozess

Alle Code-Beiträge durchlaufen automatisierte Qualitätsprüfungen in der CI/CD-Pipeline. Die folgenden Werkzeuge sind verbindlich:

*TypeScript/JavaScript:*
- *ESLint 9* (Flat Config) mit `@typescript-eslint/strict-type-checked` — strikte Typprüfung, Komplexitätslimit (max. 10), Importreihenfolge, Namenskonventionen
- *Prettier* — automatische Formatierung (Semikolons, einfache Anführungszeichen, 120 Zeichen Zeilenlänge)

*SCSS:*
- *Stylelint* mit `stylelint-config-standard-scss` — keine duplizierten Selektoren, konsistentes Farbformat

*PHP:*
- *PHPStan Level 6* — statische Typenanalyse mit Baseline-Datei für bestehende Violations
- *PHP CS Fixer* mit `@PER-CS2.0` — automatische Formatierung, `declare(strict_types=1)` erzwungen

*Pre-Commit-Hooks (husky + lint-staged):*
Beim Committen werden geänderte Dateien automatisch formatiert. Entwickler können mit `--no-verify` umgehen; die Pipeline fängt alles ab.

*Sicherheitsscanning:*
- `npm audit` und `composer audit` prüfen Abhängigkeiten auf bekannte Schwachstellen
- *Semgrep* führt statische Sicherheitsanalyse (SAST) auf PHP und TypeScript durch
- `detect-secrets` verhindert das versehentliche Einchecken von Zugangsdaten
```

- [ ] **Step 3: Commit**

```bash
cd /home/progressio/lemonbrain/git/innovationsbox-dokumentation
git add src/product/arc42/08_concepts.typ
git commit -m "docs: add code quality and development process concept (8.11)"
```
