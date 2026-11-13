# CI/CD Plan 3: Testing in CI

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Configure Pest with 80% coverage enforcement, add Storybook smoke tests to CI, verify the asset build, and fill in pipeline Stage 3 with real test commands.

**Architecture:** Stage 3 runs three parallel sub-checks: PHP unit tests with coverage, asset build verification, and Storybook component smoke tests. Coverage uses Pest's built-in `--min` flag to fail the build below 80%. Storybook smoke starts the PHP story server as a background process and runs Playwright against it.

**Tech Stack:** Pest v1 (+ xdebug/pcov for coverage), Playwright, PHP 8.3 built-in server, Laravel Mix (webpack)

**Spec:** `docs/superpowers/specs/2026-04-13-code-quality-cicd-design.md`

**Plan series:** Plan 3 of 5. Plans 1-2 are complete (pipeline skeleton + linting).

---

### Task 1: Configure Pest coverage with 80% minimum

**Files:**
- Modify: `phpunit.xml` (add coverage config)
- Modify: `composer.json` (update test scripts)

- [ ] **Step 1: Read the current `phpunit.xml`**

Read `phpunit.xml` to understand the current structure.

- [ ] **Step 2: Add coverage configuration to `phpunit.xml`**

Replace the entire file with:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/integration</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./public/packages/inno_project_idea/src</directory>
            <directory suffix=".php">./public/packages/inno_moderation/src</directory>
            <directory suffix=".php">./public/packages/inno_notification/src</directory>
            <directory suffix=".php">./public/packages/inno_project_space/src</directory>
            <directory suffix=".php">./public/packages/inno_shared/src</directory>
        </include>
    </coverage>
</phpunit>
```

- [ ] **Step 3: Update composer scripts for coverage**

Read `composer.json` and add a coverage script to the `scripts` section (keep all existing scripts):

```json
"test:coverage": "pest --testsuite=Unit --coverage --min=80"
```

- [ ] **Step 4: Verify unit tests still pass**

```bash
./vendor/bin/pest --testsuite=Unit --no-coverage
```

Expected: All tests pass (15+ tests from the hexagonal refactor).

- [ ] **Step 5: Run coverage to check current level**

```bash
./vendor/bin/pest --testsuite=Unit --coverage 2>&1 | tail -20
```

This shows the current coverage percentage. If it's below 80%, we may need to adjust the threshold or add tests — but first let's see where we are.

- [ ] **Step 6: Commit**

```bash
git add phpunit.xml composer.json
git commit -m "feat(test): add coverage config to phpunit.xml and test:coverage script"
```

---

### Task 2: Add a CI test runner script

Create a shell script that runs all CI test steps in sequence. This makes the pipeline step cleaner and allows local testing of the full CI test suite.

**Files:**
- Create: `scripts/ci-test.sh`

- [ ] **Step 1: Create `scripts/ci-test.sh`**

```bash
#!/usr/bin/env bash
set -euo pipefail

echo "=== PHP Unit Tests + Coverage ==="
./vendor/bin/pest --testsuite=Unit --coverage --min=80

echo ""
echo "=== Asset Build Check ==="
npm run production

echo ""
echo "=== Storybook Smoke Tests ==="
# Start PHP story server in background
php -S localhost:6007 storybook-server/index.php &
PHP_PID=$!

# Wait for server to be ready
for i in $(seq 1 10); do
    if curl -s http://localhost:6007/?id=atoms/button > /dev/null 2>&1; then
        break
    fi
    sleep 0.5
done

# Run Playwright storybook tests
npx playwright test tests/e2e/specs/storybook/ --config=tests/e2e/playwright.config.ts

# Clean up
kill $PHP_PID 2>/dev/null || true

echo ""
echo "=== All CI Tests Passed ==="
```

- [ ] **Step 2: Make it executable**

```bash
chmod +x scripts/ci-test.sh
```

- [ ] **Step 3: Add npm script**

Add to `package.json` scripts:

```json
"test:ci": "bash scripts/ci-test.sh"
```

- [ ] **Step 4: Commit**

```bash
git add scripts/ci-test.sh package.json
git commit -m "feat(test): add ci-test.sh runner for pipeline Stage 3"
```

---

### Task 3: Install Playwright browsers for CI

Playwright needs Chromium installed in the CI environment. Add a setup step.

**Files:**
- Modify: `package.json` (add postinstall for Playwright)

- [ ] **Step 1: Add Playwright browser install script**

Add to `package.json` scripts:

```json
"playwright:install": "playwright install chromium"
```

This is called explicitly in CI rather than via postinstall (which would slow down every `npm ci`).

- [ ] **Step 2: Commit**

```bash
git add package.json
git commit -m "feat(test): add playwright:install script for CI browser setup"
```

---

### Task 4: Fill in pipeline Stage 3

Replace the Stage 3 stub in `bitbucket-pipelines.yml` with real test commands.

**Files:**
- Modify: `bitbucket-pipelines.yml`

- [ ] **Step 1: Read the current pipeline**

Read `bitbucket-pipelines.yml` to find the Stage 3 stub.

- [ ] **Step 2: Replace Stage 3 stub**

Find the step named `"Stage 3: Test & Coverage"` and replace its entire `script` block:

```yaml
      - step:
          name: "Stage 3: Test & Coverage"
          image: php:8.3-cli
          caches:
            - npm-cache
            - composer-cache
          script:
            # Install system dependencies
            - apt-get update && apt-get install -y git unzip curl libzip-dev
            - docker-php-ext-install zip pcntl
            - pecl install pcov && docker-php-ext-enable pcov

            # Install Node.js 20
            - curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
            - apt-get install -y nodejs

            # Install Composer
            - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

            # Install dependencies
            - npm ci
            - composer install --no-interaction --no-progress

            # PHP Unit Tests with Coverage (80% minimum)
            - ./vendor/bin/pest --testsuite=Unit --coverage --min=80

            # Asset Build Check
            - npm run tokens
            - npm run production

            # Storybook Smoke Tests
            - npm run playwright:install
            - php -S localhost:6007 storybook-server/index.php &
            - sleep 2
            - npx playwright test tests/e2e/specs/storybook/ --config=tests/e2e/playwright.config.ts
```

- [ ] **Step 3: Validate YAML**

```bash
python3 -c "import yaml; yaml.safe_load(open('bitbucket-pipelines.yml'))" && echo "YAML valid"
```

Expected: `YAML valid`

- [ ] **Step 4: Commit**

```bash
git add bitbucket-pipelines.yml
git commit -m "feat(ci): fill pipeline Stage 3 with PHP tests, coverage, asset build, Storybook smoke"
```

---

### Task 5: Update CLAUDE.md with CI test info

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Update the Testing section**

Find the `### Testing` section in CLAUDE.md. Replace its content with:

```markdown
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

All tests live under `tests/` at the repo root:
- `tests/unit/` — pure PHP, no CMS bootstrap; mocks `Port/Out/` interfaces via Mockery
- `tests/integration/` — real DB/email; CMS bootstrapped (not run in PR pipeline)
- `tests/e2e/` — Playwright targeting `http://$PROJECT_NAME.local:$APP_PORT`
- `tests/e2e/specs/storybook/` — Storybook component smoke tests (run in PR pipeline)

Coverage threshold: 80% line coverage on `public/packages/inno_*/src/`, enforced in CI.
```

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: update testing section in CLAUDE.md with coverage and CI info"
```
