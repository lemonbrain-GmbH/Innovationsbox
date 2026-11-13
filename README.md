# Innovationsbox

[![CI](https://github.com/lemonbrain-GmbH/Innovationsbox/actions/workflows/ci.yml/badge.svg)](https://github.com/lemonbrain-GmbH/Innovationsbox/actions/workflows/ci.yml)
[![Storybook](https://img.shields.io/badge/Storybook-Component%20Library-ff4785)](https://lemonbrain-gmbh.github.io/Innovationsbox/)

A moderated platform where climate-relevant project ideas can be submitted, reviewed, published, and collaboratively developed in protected project spaces. Built for [klimaglarus](https://klimaglarus.ch) / [arento](https://arento.ch).

**Flow:** idea submitted → moderation review → published publicly → interested members join → collaborate in a project space.

## Tech Stack

- **CMS:** Concrete CMS 9.4+
- **Backend:** PHP 8.3+, hexagonal architecture (Ports & Adapters), DDD bounded contexts
- **Frontend:** SCSS + vanilla TypeScript, Atomic Design component library, Storybook
- **Testing:** Pest (PHP), Playwright (E2E + Storybook smoke)
- **Build:** Laravel Mix (webpack)

## Prerequisites

- [Nix](https://nixos.org/download.html) with flakes enabled (provides Node.js 20, PHP 8.3, semgrep, detect-secrets)
- Docker + Docker Compose (for MySQL + PHP-FPM in development)

## Local Setup

```bash
# 1. Clone and enter the project
git clone git@github.com:lemonbrain-GmbH/Innovationsbox.git && cd Innovationsbox

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
| `make seed` | Seed test data (TRUNCATES existing ib_* data) |
| `make seed-e2e` | Seed E2E test data (users + ideas + spaces in all statuses) |

## Testing

### Unit & Integration Tests
```bash
composer test              # Run PHP unit tests (Pest)
composer test:coverage     # Unit tests + 80% coverage report
composer test:integration  # Integration tests (requires local DB)
npm run test:e2e           # Playwright E2E tests (requires running CMS)
npm run test:ci            # Full CI test suite locally
```

### E2E Tests (Docker)
```bash
# Build and start the test environment
docker compose -f docker-compose.test.yml up -d --build --wait

# Run integration tests
docker compose -f docker-compose.test.yml exec -T app composer test:integration

# Run E2E tests
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e

# Tear down
docker compose -f docker-compose.test.yml down -v
```

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

## Design System

The component library is built on Atomic Design and is tokens-driven: all colours, typography, spacing, radii, and shadows are defined in `resources/tokens/tokens.json` and generated into SCSS variables via `npm run tokens`.

**a11y-first:** every component uses the `focus-visible-ring`, `respect-motion`, and `touch-target` SCSS mixins. The Playwright test suite includes 180 axe-core runs (one per Storybook story) plus 44 smoke tests for interactive components — 224 tests total.

| Tier | Count | Components |
|------|-------|------------|
| Atoms | 24 | avatar, badge, button, caption, checkbox, code, container, divider, email-link, heading, icon, image, input, label, link, overlay, paragraph, radio, select, skip-link, spinner, textarea, toggle, tooltip |
| Molecules | 26 | accent-text, alert, auth-card, avatar-upload, breadcrumb, card, collaboard-item, dropdown, empty-state, feature-button, form-field, join-request-card, member-card, modal, nav-item, page-card, pagination, partner-logo, password-field, project-card, question-text, search-bar, skeleton, status-update-entry, tabs, user-menu |
| Organisms | 9 | collaboard-panel, content-block, footer, header, hero-image, navigation-drawer, page-list-grid, project-space-sidebar, status-update-timeline |

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

Full architecture documentation is available in `docs/` (Typst format).

## Quality Checks

Before submitting a PR, run:

```bash
npm run lint:all      # ESLint + Prettier + Stylelint + TypeScript
composer lint:php     # PHPStan + PHP CS Fixer
composer test         # PHP unit tests (80% coverage enforced)
```

Pre-commit hooks (husky + lint-staged) auto-format your code on commit.

## Contributing

We welcome contributions! Please read [CONTRIBUTING.md](CONTRIBUTING.md) before submitting a pull request.

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Make your changes and ensure all checks pass
4. Submit a pull request

## Security

See [SECURITY.md](SECURITY.md) for our vulnerability disclosure process.

## License

[MIT](LICENSE.txt) — Copyright 2026 lemonbrain GmbH
