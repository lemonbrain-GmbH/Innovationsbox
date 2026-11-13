# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Innovationsbox** is a moderated platform for klimaglarus/Arento where climate-relevant project ideas can be submitted, reviewed, published, and collaboratively developed in protected project spaces. The flow is: idea submitted → moderation review → published publicly → interested members join → collaborate in a project space.

This repo is a **Concrete CMS** (v9.4+) composer-based project, managed by lemonbrain. It is a "mother repo" that contains five DDD-aligned packages in `public/packages/`, each following **hexagonal architecture**. Frontend assets are built with Laravel Mix (webpack) from `resources/` into the `frontend` package.

The codebase is developed as **open-source**. The full arc42 architecture documentation lives in `docs/typst/innovationsbox-dokumentation/` (Typst format).

### Packages and communication

Each package is a self-contained Concrete CMS package with handle = folder name. Packages communicate **event-driven** (Symfony EventDispatcher via `Events` facade) or via **REST API** (`/api/*`). They never import each other's code — only `inno_shared` event contracts.

| Package (`public/packages/`) | Handle | REST API | Emits events (via `inno_shared`) | Listens to events |
|---|---|---|---|---|
| `inno_frontend` | `inno_frontend` | — | — | — |
| `inno_shared` | `inno_shared` | — | — (provides event classes + `CategoryReaderPort`) | — |
| `inno_project_idea` | `inno_project_idea` | `/api/project-idea` | `ProjectIdeaSubmitted`, `ProjectIdeaApproved`, `ProjectIdeaRejected`, `ProjectIdeaResubmitted` | `ModerationDecisionMade`, `ModerationFeedbackRequested`, `ProjectSpaceCreated` |
| `inno_project_space` | `inno_project_space` | `/api/project-space` | `ProjectSpaceCreated`, `ProjectSpaceActivated`, `ProjectSpaceCompleted`, `JoinRequestReceived`, `JoinRequestAccepted`, `JoinRequestRejected`, `MemberInvited`, `MemberLeft`, `MemberRemoved`, `ExpertRequested`, `ExpertRequestAccepted`, `ExpertRequestDeclined`, `StatusUpdatePublished` | `ProjectIdeaApproved` |
| `inno_moderation` | `inno_moderation` | `/api/moderation`, `/api/moderation/category`, `/api/moderation/user`, `/api/user/account`, `/api/user/counts` | `ModerationDecisionMade`, `ModerationFeedbackRequested`, `UserFlaggedAsExpert`, `UserUnflaggedAsExpert`, `UserAccountDeactivated` | `ProjectIdeaSubmitted`, `ProjectIdeaResubmitted` |
| `inno_notification` | `inno_notification` | `/api/notification` | — | `ProjectIdeaSubmitted`, `ProjectIdeaApproved`, `ProjectIdeaRejected`, `MemberLeft`, `MemberRemoved`, `UserFlaggedAsExpert`, `UserUnflaggedAsExpert`, `UserAccountDeactivated` |

All event classes live in `public/packages/inno_shared/src/Domain/Event/`. Each class has a `NAME` constant (e.g. `ProjectIdeaSubmitted::NAME = 'innovationsbox.project_idea.submitted'`). Listeners are registered in each package's `on_start()`.

### Key Features

**User account deactivation:** Users can delete their own account from `/account/edit_profile` (password confirmation required). Moderators can deactivate users from `/moderation/benutzer` (reason required, included in email notification). Deactivated users are removed from all project spaces automatically. Soft delete only (`uIsActive = 0`).

**Notification badges:** The user menu trigger (avatar) shows a red badge with the total count of pending actions. Individual menu items show per-category counts: "Moderation (N)" for pending reviews, "Meine Anfragen (N)" for pending expert requests, "Meine Projekträume (N)" for pending join requests. Counts are rendered server-side via `UserCountsQuery`. API endpoint: `GET /api/user/counts`.

**Approved ideas create active spaces:** When a moderator approves an idea, the project space is created with `active` status (not `draft`), making it immediately visible on `/projektraeume`.

### Hexagonal architecture per package

Each domain package (`inno_project_idea`, `inno_project_space`, `inno_moderation`, `inno_notification`) follows genuine Hexagonal Architecture (Ports & Adapters). `Application/` is the hexagon centre.

    src/
      Domain/          ← Pure PHP: entities, enums, value objects (no framework dependencies, no events)
      Application/
        UseCase/       ← Command + Handler pairs; handlers implement Port/In/ interfaces
        EventHandler/  ← Cross-package event listeners (import events from inno_shared only)
      Port/
        In/            ← Primary ports: one interface per use case (e.g. SubmitProjectIdeaPort)
        Out/           ← Secondary ports: repository + service interfaces
      Adapter/
        In/
          Http/        ← REST controllers (extend AbstractController, inject Port/In interfaces)
        Out/
          Persistence/ ← Concrete CMS DB implementations (inject Connection)
          Email/       ← Email adapter (notification package only)

Dependency rule: `Adapter/In → Port/In → Application/ → Domain/` and `Adapter/Out ← Port/Out ← Application/`.
No package imports from another domain package. All cross-package events are in `inno_shared`.

DI bindings (interface → implementation) are set up in each package's `on_start()` via `$this->app->bind()` — both `Port/Out/` and `Port/In/` interfaces are bound.

The CMS handles: user management, roles/permissions, page structure, content blocks, routing, caching, security (CSRF tokens, sanitising, reCAPTCHA).

## Commands

### Frontend Assets
```bash
npm install          # Install JS dependencies
npm run watch        # Build and watch for changes (development)
npm run development  # Single development build
npm run production   # Minified production build (also triggered by CI pipeline)
```
BrowserSync proxies `http://$PROJECT_NAME.local:$APP_PORT` — set these in `.env`.

### PHP / Composer
```bash
composer install                            # Install PHP dependencies
./vendor/bin/concrete c5:install -i         # Interactive Concrete CMS install
./vendor/bin/concrete c5:update             # Update Concrete CMS
composer test                               # Run unit tests with coverage (Pest, 80% minimum enforced)
```

### Linting & Formatting
```bash
npm run lint             # ESLint for TypeScript
npm run lint:fix         # ESLint auto-fix
npm run format           # Prettier check
npm run format:fix       # Prettier auto-fix
npm run lint:scss        # Stylelint for SCSS
npm run lint:scss:fix    # Stylelint auto-fix
npm run typecheck        # TypeScript type check (tsc --noEmit)
npm run lint:all         # Run all JS/TS/SCSS checks
composer lint:php        # PHPStan + PHP CS Fixer (dry-run)
composer lint:cs:fix     # PHP CS Fixer auto-fix
```
Pre-commit hooks (husky + lint-staged) auto-format on commit. CI runs all checks in the PR pipeline Stage 1 and 2.

### OpenAPI
```bash
composer openapi:generate   # Regenerate OpenAPI spec from code attributes
npm run lint:api            # Lint OpenAPI spec with Spectral
```
Swagger UI available at `/api/docs` in development (controlled by `api.docs_enabled` config).

### Security
```bash
npm run audit:npm          # npm dependency vulnerability scan (fails on high/critical)
composer audit             # Composer dependency vulnerability scan
npm run audit:sast         # Semgrep SAST scan (PHP + TypeScript)
npm run license:check      # License compliance check (fails on GPL-3.0/AGPL-3.0)
```
Pre-commit hook runs `detect-secrets` to prevent accidental credential commits.
CI runs npm audit + composer audit + semgrep in pipeline Stage 4.
See `SECURITY.md` for the vulnerability disclosure process.

### Storybook
```bash
npm run tokens           # Regenerate SCSS from tokens.json
npm run storybook        # Start Storybook (port 6006) + PHP story server (port 6007)
npm run storybook:build  # Build static Storybook site
```
Component library using Atomic Design (atoms → molecules → organisms → templates → pages).
Components are PHP partials rendered by a lightweight PHP server. Design tokens live in `resources/tokens/tokens.json`.

### Component Library (Atomic Design)
```
public/packages/inno_frontend/components/
  atoms/         ← 25 components: avatar, badge, button, caption, checkbox, code, container, divider,
                                  email-link, fab, heading, icon, image, input, label, link, overlay,
                                  paragraph, radio, select, skip-link, spinner, textarea, toggle, tooltip
  molecules/     ← 26 components: accent-text, alert, auth-card, avatar-upload, breadcrumb, card,
                                  collaboard-item, dropdown, empty-state, feature-button, form-field,
                                  join-request-card, member-card, modal, nav-item, page-card, pagination,
                                  partner-logo, password-field, project-card, question-text, search-bar,
                                  skeleton, status-update-entry, tabs, user-menu
  organisms/     ← 9 components: collaboard-panel, content-block, footer, header, hero-image,
                                 navigation-drawer, page-list-grid, project-space-sidebar,
                                 status-update-timeline
  templates/     ← 2 templates: default-page, view-page
  pages/         ← 2 examples: homepage, inner-page
```
Each component has: `<name>.php` (partial), `<name>.variants.php` (props/defaults), `<name>.stories.json` (Storybook), `<name>.scss` (styles).
CMS block templates in `public/packages/inno_frontend/blocks/` are thin wrappers that extract CMS data into `$props` and `include` the component partial via `DIR_COMPONENTS`.

### Auth & Profile Pages
Custom Single Page templates for auth flows override the Concrete CMS defaults. Templates in `inno_frontend/single_pages/` and `inno_frontend/authentication/concrete/` (symlinked to `application/authentication/concrete/` on install/upgrade). See `docs/auth-pages.md` for details.

### Testing

```bash
composer test              # Run unit tests (Pest, tests/unit/)
composer test:unit         # Same as above
composer test:coverage     # Unit tests + coverage report (80% minimum enforced)
composer test:integration  # Run integration tests (tests/integration/)
npm run test:e2e           # Run Playwright E2E tests (tests/e2e/) — requires local CMS running
npm run test:ci            # Run full CI test suite locally (unit + coverage + build + Storybook smoke)
npm run playwright:install # Install Chromium for Playwright (needed before first E2E run)
```

#### E2E with Docker test environment
```bash
docker compose -f docker-compose.test.yml up -d --wait   # Start CMS + MySQL + Mailpit
docker compose -f docker-compose.test.yml down -v         # Stop and reset (wipes DB)
```

Run E2E tests against the Docker environment:
```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1
```

Interactive Playwright UI (browse and run tests visually):
```bash
PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH=$(which chromium) E2E_DOCKER=true \
  npx playwright test --config=tests/e2e/playwright.config.ts --ui-host=0.0.0.0 --ui-port=9323
```
Opens at `http://localhost:9323`. Use the Projects dropdown to switch between `chromium` and `e2e` projects. Add `--project=e2e` to filter to E2E tests only.

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

All tests live under `tests/` at the repo root:
- `tests/unit/` — pure PHP, no CMS bootstrap; mocks `Port/Out/` interfaces via Mockery
- `tests/integration/` — real DB/email; CMS bootstrapped (not run in PR pipeline)
- `tests/e2e/` — Playwright targeting `http://$PROJECT_NAME.local:$APP_PORT`
- `tests/e2e/specs/storybook/` — Storybook component smoke tests (run in PR pipeline)

Coverage threshold: 80% line coverage on `public/packages/inno_*/src/`, enforced in CI.

For test data setup details, see `docs/testing.md`.

### Makefile helpers (require `.env` vars)
```bash
make create   # composer install + permissions setup (+ optionally wipe packages/)
make install  # Install Concrete CMS from scratch with config
make export   # Export DB + files to ./export/ as tar archive
make import   # Import tar archive from ./import/
make update   # Composer update + c5:update + entity refresh
make clean    # Maintenance: charset fix, foreign keys, entity refresh, cache clear, permissions
make seed     # Seed test data for development/testing (TRUNCATES ib_* tables)
make seed-e2e # Seed E2E test users, ideas, and spaces (used by Docker CI environment)
```
Pass environment variables inline, e.g.:
```bash
make import DOCKER=false PROJECT_NAME=innovationsbox MYSQL_ROOT_PASSWORD=... MYSQL_DATABASE=...
```

## Architecture

### Repository / Package split
This repo is the **source**. The Bitbucket pipeline strips the `.git` folder, builds production assets, and pushes `public/packages/inno_frontend/` to a separate composer-installable package repo (`bitbucket.org/externelemonbrain/innovationsbox`). Production sites install the package via `packagist.lemonbrain.ch`.

### File layout
```
resources/
  css/           ← SCSS source (main entry: main.scss, global styles, helper functions)
  js/            ← TypeScript source (main entry: main.ts)
    single_pages/← Single-page TS → compiled to themes/innovationsbox/js/single_pages/
  tokens/        ← Design tokens (tokens.json → generated SCSS variables)
public/
  concrete/      ← Concrete CMS core (composer-managed, not in git)
  packages/
    inno_frontend/        ← Theme + UI assets + component library (handle: inno_frontend)
    inno_shared/          ← Shared event contracts (handle: inno_shared)
    inno_project_idea/    ← DDD: project idea lifecycle (handle: inno_project_idea)
    inno_project_space/   ← DDD: collaboration spaces (handle: inno_project_space)
    inno_moderation/      ← DDD: review queue (handle: inno_moderation)
    inno_notification/    ← DDD: email/system notifications (handle: inno_notification)
  application/
    blocks/      ← Core block overrides (autonav, page_list, etc.)
    config/      ← Site config (live.database.php, concrete.php, app.php)
    bootstrap/   ← CMS bootstrap overrides
tests/
  unit/            ← Pest unit tests per package (pure PHP, no CMS bootstrap)
  integration/     ← Pest integration tests per package (real DB, CMS bootstrapped)
  e2e/             ← Playwright E2E tests (API-level + browser, requires running CMS)
```

### Package: `inno_frontend`
- **Handle:** `inno_frontend` — **Requires:** `block_developer >= 1.4.4`, `lemonbrain_services >= 5.0.13` (not in this repo — must be cloned manually into `public/packages/`)
- **Package controller** (`public/packages/inno_frontend/controller.php`): registers JS asset group `inno_frontend` (`manifest.js`, `vendor.js`, `main.js`) on `on_start()`, installs/activates the theme and block type set on `install()`/`upgrade()`
- **Theme handle:** `innovationsbox` (folder `public/packages/inno_frontend/themes/innovationsbox/`) — theme handle and package handle intentionally differ
- **Asset build:** `mix.extract()` splits output into `main.js`, `manifest.js`, and `vendor.js` (all three must be in footer)

### Webpack / Laravel Mix (`webpack.mix.js`)
- Entry: `resources/js/main.ts` → `public/packages/inno_frontend/themes/innovationsbox/js/main.js`
- Entry: `resources/css/main.scss` → `public/packages/inno_frontend/themes/innovationsbox/css/skins/default.css`
- Block-specific SCSS/TS are compiled from `public/packages/inno_frontend/components/` via an explicit mapping table in `webpack.mix.js` — each component maps to a CMS block output path (e.g. `organisms/navigation-drawer/` → `blocks/autonav/templates/innovationsbox/view.css`). Output filenames must be `view.css`/`view.js` (Concrete CMS convention). Component SCSS uses a `~@helpers` webpack alias to import shared functions from `resources/css/helper/`.
- Single-page JS/CSS in `resources/js/single_pages/` and `resources/css/single_pages/` are auto-discovered and compiled into the theme

### Environment (`.env`)
Copy `.env.dist` to `.env`. Key variables: `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`, `DB_HOSTNAME`, `CONCRETE5_ENV` (set to `"live"` to activate `application/config/live.database.php`), `PROJECT_NAME`, `APP_PORT`.

### Pipeline
- **PR validation** → 5 stages: lint & format → type check → test & coverage → security → E2E & integration tests. Fail-fast (cheapest checks first).
- **Tag push** → production build → push to `main` branch of 5 package repos + same tag
- **`staging` branch push** → production build → push to `staging` branch of package repos
- **Weekly schedule** → dependency outdated report (npm + composer)
- Pipeline uses SSH deployment keys for pushing to package repos (see `docs/ssh-key-setup.md`).
- Do not commit generated JS/CSS unless you want a diff — the pipeline regenerates them — but the README encourages running `npm run production` before pushing to avoid unnecessary pipeline rebuilds.
