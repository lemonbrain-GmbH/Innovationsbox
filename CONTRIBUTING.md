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
3. **Test & Coverage** — Pest unit tests (80% line coverage minimum enforced by `--min=80`), asset build, Storybook smoke tests (all 63 components)
4. **Security** — npm audit (fails on high/critical), composer audit, semgrep SAST (PHP + TypeScript), license compliance check

### Security tooling overview

| Tool | Command | What it checks |
|------|---------|---------------|
| `npm audit` | `npm run audit:npm` | npm dependency CVEs (high/critical = fail) |
| `composer audit` | `composer audit` | Composer dependency CVEs |
| Semgrep | `npm run audit:sast` | PHP + TypeScript vulnerability patterns (SAST) |
| detect-secrets | pre-commit hook | Prevents accidental credential commits |
| License check | `npm run license:check` | Rejects GPL-3.0/AGPL-3.0 dependencies |

Application-level security utilities (CSRF validation, security headers, input validation, DOMPurify) are documented in [docs/cross-cutting-concerns/04-security-auth.md](docs/cross-cutting-concerns/04-security-auth.md). Known issues and accepted risks are tracked in [SECURITY-KNOWN-ISSUES.md](SECURITY-KNOWN-ISSUES.md).

## Adding Components (Atomic Design)

Components live in `public/packages/inno_frontend/components/` following Atomic Design:

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
