# Phase 1: Docker Test Environment + Seed + Pipeline

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create an isolated Docker-based test environment that boots a full Concrete CMS instance, seeds E2E test data, and runs integration + E2E tests as Pipeline Stage 5.

**Architecture:** A `docker-compose.test.yml` orchestrates three services: MySQL 8.0, a PHP 8.3+Apache app built from `Dockerfile.test`, and Mailpit for email capture. An entrypoint script handles CMS installation, package installation, and data seeding on first boot. Playwright runs on the host (or CI runner) against the Dockerized CMS.

**Tech Stack:** Docker Compose, PHP 8.3, Apache, MySQL 8.0, Mailpit, Concrete CMS 9.5+, Playwright, Bitbucket Pipelines

**Spec:** `docs/superpowers/specs/2026-06-09-comprehensive-qa-infrastructure-design.md` (Phase 1)

---

## File Structure

| Action | File | Responsibility |
|--------|------|---------------|
| Create | `Dockerfile.test` | PHP 8.3 + Apache image with all CMS dependencies |
| Create | `docker-compose.test.yml` | Orchestrates db, app, mailpit services |
| Create | `docker/test-entrypoint.sh` | CMS install, package install, seed, Apache start |
| Create | `docker/test.env` | Environment variables for the test environment |
| Create | `docker/test.database.php` | Concrete CMS database config for test environment |
| Create | `scripts/seed-e2e-data.sh` | E2E-specific seed data (ideas in all statuses, spaces, members) |
| Create | `scripts/seed-e2e-users.php` | PHP script to create CMS users with roles via C5 API |
| Modify | `Makefile:177-179` | Add `seed-e2e` target |
| Modify | `tests/e2e/playwright.config.ts` | Add `e2e` project for Docker-based tests |
| Create | `tests/e2e/specs/00-smoke.spec.ts` | Smoke test validating the Docker environment |
| Modify | `bitbucket-pipelines.yml:96` | Add Stage 5: E2E & Integration Tests |

---

### Task 1: Create Dockerfile.test

**Files:**
- Create: `Dockerfile.test`

- [ ] **Step 1: Create the Dockerfile**

```dockerfile
FROM php:8.3-apache

# System dependencies for Concrete CMS
RUN apt-get update && apt-get install -y \
    git unzip curl default-mysql-client \
    libzip-dev libpng-dev libjpeg-dev libfreetype-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install zip gd intl pdo_mysql opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js 20 (needed for frontend build)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install dependencies
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-interaction --no-progress --ignore-platform-req=php
RUN npm ci && npm run tokens && npm run production

# Apache configuration — serve from /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}/!g' \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html/public/application/config \
    /var/www/html/public/application/files \
    /var/www/html/public/packages

# Copy test-specific files
COPY docker/test-entrypoint.sh /usr/local/bin/test-entrypoint.sh
RUN chmod +x /usr/local/bin/test-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["test-entrypoint.sh"]
```

- [ ] **Step 2: Verify the Dockerfile syntax**

Run: `docker build --check -f Dockerfile.test .` (or just validate syntax manually — the real build happens via docker-compose in Task 4)

Expected: No syntax errors

- [ ] **Step 3: Commit**

```bash
git add Dockerfile.test
git commit -m "build: add Dockerfile.test for E2E test environment"
```

---

### Task 2: Create test environment config files

**Files:**
- Create: `docker/test.env`
- Create: `docker/test.database.php`

- [ ] **Step 1: Create the docker directory**

Run: `mkdir -p docker`

- [ ] **Step 2: Create docker/test.env**

This file provides environment variables for the test containers. It mirrors `.env` but with test-specific values.

```env
DB_HOSTNAME=db
DB_DATABASE=innovationsbox_test
DB_USERNAME=innovationsbox_test
DB_PASSWORD=test_password_e2e
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

CONCRETE5_ENV=test
C5_CLI_ALLOW_AS_ROOT=1

ADMIN_EMAIL=admin@e2e.test
ADMIN_PASSWORD=E2eTest!2026

DEMO_USERNAME=demo
DEMO_PASSWORD=E2eTest!2026
DEMO_EMAIL=demo@e2e.test

LANGUAGE=de_DE
SITE_LOCALE=de_CH
STARTING_POINT=atomik_blank
TIMEZONE=Europe/Zurich

# Mailpit SMTP for email capture
MAIL_HOST=mailpit
MAIL_PORT=1025
```

- [ ] **Step 3: Create docker/test.database.php**

This file gets copied to `public/application/config/test.database.php` so that `CONCRETE5_ENV=test` picks it up.

```php
<?php

return [
    'default-connection' => 'concrete',
    'connections' => [
        'concrete' => [
            'driver' => 'c5_pdo_mysql',
            'server' => $_ENV['DB_HOSTNAME'] ?? 'db',
            'database' => $_ENV['DB_DATABASE'] ?? 'innovationsbox_test',
            'username' => $_ENV['DB_USERNAME'] ?? 'innovationsbox_test',
            'password' => $_ENV['DB_PASSWORD'] ?? 'test_password_e2e',
            'character_set' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
        ],
    ],
];
```

- [ ] **Step 4: Commit**

```bash
git add docker/
git commit -m "build: add test environment config (env + database)"
```

---

### Task 3: Create the entrypoint script

**Files:**
- Create: `docker/test-entrypoint.sh`

The entrypoint handles first-run CMS installation, package installation, seeding, and then starts Apache. On subsequent runs (if the container is restarted), it detects the existing installation and skips to Apache.

- [ ] **Step 1: Create docker/test-entrypoint.sh**

```bash
#!/usr/bin/env bash
set -euo pipefail

echo "=== Innovationsbox Test Environment Entrypoint ==="

# Copy test database config
cp /var/www/html/docker/test.database.php \
   /var/www/html/public/application/config/test.database.php

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
for i in $(seq 1 30); do
  if mysqladmin ping -h "$DB_HOSTNAME" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; then
    echo "MySQL is ready."
    break
  fi
  if [ "$i" -eq 30 ]; then
    echo "ERROR: MySQL did not become ready in time."
    exit 1
  fi
  sleep 1
done

# Check if CMS is already installed
INSTALLED_MARKER="/var/www/html/public/application/config/generated_overrides"
if [ ! -d "$INSTALLED_MARKER" ]; then
  echo "=== Installing Concrete CMS ==="
  /var/www/html/public/concrete/bin/concrete c5:install --allow-as-root \
    --db-server="$DB_HOSTNAME" \
    --db-username="$DB_USERNAME" \
    --db-password="$DB_PASSWORD" \
    --db-database="$DB_DATABASE" \
    --site="Innovationsbox Test" \
    --starting-point="$STARTING_POINT" \
    --admin-email="$ADMIN_EMAIL" \
    --admin-password="$ADMIN_PASSWORD" \
    --demo-username="$DEMO_USERNAME" \
    --demo-password="$DEMO_PASSWORD" \
    --demo-email="$DEMO_EMAIL" \
    --language="$LANGUAGE" \
    --site-locale="$SITE_LOCALE" \
    --timezone="$TIMEZONE"

  echo "=== Installing packages ==="
  PACKAGES="inno_shared inno_frontend inno_moderation inno_project_idea inno_project_space inno_notification"
  for pkg in $PACKAGES; do
    echo "Installing package: $pkg"
    /var/www/html/public/concrete/bin/concrete c5:package:install "$pkg" --allow-as-root
  done

  echo "=== Creating E2E test users ==="
  php /var/www/html/scripts/seed-e2e-users.php

  echo "=== Seeding test data ==="
  export DOCKER=false
  bash /var/www/html/scripts/seed-testdata.sh

  echo "=== Setup complete ==="
else
  echo "CMS already installed, skipping setup."
fi

echo "Starting Apache..."
exec apache2-foreground
```

- [ ] **Step 2: Commit**

```bash
git add docker/test-entrypoint.sh
git commit -m "build: add test entrypoint script (CMS install + seed)"
```

---

### Task 4: Create the E2E user seed script

**Files:**
- Create: `scripts/seed-e2e-users.php`

This PHP script runs inside the CMS environment (after `c5:install`) to create test users with the correct roles. It uses the Concrete CMS API directly because user creation requires password hashing and group membership that raw SQL can't easily replicate.

- [ ] **Step 1: Create scripts/seed-e2e-users.php**

```php
<?php
/**
 * Creates E2E test users with roles.
 *
 * Run inside the CMS environment (after c5:install and package install):
 *   php scripts/seed-e2e-users.php
 *
 * Requires: CONCRETE5_ENV set, CMS installed, packages installed.
 */
declare(strict_types=1);

// Boot Concrete CMS
define('DIR_BASE', dirname(__DIR__) . '/public');
require DIR_BASE . '/concrete/bootstrap/configure.php';
require DIR_BASE . '/concrete/bootstrap/start.php';

use Concrete\Core\User\UserInfoRepository;
use Concrete\Core\User\Group\Group;

$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
$userInfoRepo = $app->make(UserInfoRepository::class);

$users = [
    [
        'email'    => 'user@e2e.test',
        'username' => 'e2e-user',
        'password' => 'E2eTest!2026',
        'groups'   => [],
    ],
    [
        'email'    => 'moderator@e2e.test',
        'username' => 'e2e-moderator',
        'password' => 'E2eTest!2026',
        'groups'   => ['Moderator'],
    ],
    [
        'email'    => 'expert@e2e.test',
        'username' => 'e2e-expert',
        'password' => 'E2eTest!2026',
        'groups'   => ['Experte'],
    ],
    [
        'email'    => 'admin@e2e.test',
        'username' => 'e2e-admin',
        'password' => 'E2eTest!2026',
        'groups'   => ['Administrators'],
    ],
    [
        'email'    => 'initiator@e2e.test',
        'username' => 'e2e-initiator',
        'password' => 'E2eTest!2026',
        'groups'   => [],
    ],
];

foreach ($users as $userData) {
    $existing = $userInfoRepo->getByEmail($userData['email']);
    if ($existing) {
        echo "User {$userData['email']} already exists (uID={$existing->getUserID()}), skipping.\n";
        continue;
    }

    $data = [
        'uName'            => $userData['username'],
        'uEmail'           => $userData['email'],
        'uPassword'        => $userData['password'],
        'uPasswordConfirm' => $userData['password'],
        'uIsValidated'     => true,
    ];

    $ui = \Concrete\Core\User\UserInfo::add($data);
    if (!$ui) {
        echo "ERROR: Failed to create user {$userData['email']}\n";
        continue;
    }

    // Activate the user
    $u = $ui->getUserObject();
    $u->activate();

    // Set display_name attribute (required for profile completion)
    $ui->setAttribute('display_name', ucfirst(str_replace('e2e-', '', $userData['username'])));

    // Assign groups
    foreach ($userData['groups'] as $groupName) {
        $group = Group::getByName($groupName);
        if ($group) {
            $u->enterGroup($group);
            echo "  Added to group: {$groupName}\n";
        } else {
            echo "  WARNING: Group '{$groupName}' not found\n";
        }
    }

    echo "Created user: {$userData['email']} (uID={$ui->getUserID()})\n";
}

echo "\nE2E users seeded successfully.\n";
```

- [ ] **Step 2: Commit**

```bash
git add scripts/seed-e2e-users.php
git commit -m "build: add E2E user seed script (creates users with roles)"
```

---

### Task 5: Extend seed script for E2E data

**Files:**
- Modify: `scripts/seed-testdata.sh`
- Modify: `Makefile:177-179`

The existing seed script creates data with hardcoded `user_id` values (1, 2, 3...) that work with the demo data. For E2E, we need ideas in all 6 statuses and spaces in all 4 statuses, owned by the E2E users whose IDs are resolved dynamically.

- [ ] **Step 1: Add `seed-e2e` target to Makefile**

Add after line 179 of `Makefile`:

```makefile
seed-e2e:
	@echo "Seeding E2E test data into database..."
	@bash scripts/seed-e2e-data.sh
```

- [ ] **Step 2: Create scripts/seed-e2e-data.sh**

This is a separate seed script specifically for E2E that references the E2E users by email lookup. It runs _after_ `seed-e2e-users.php` has created the users.

```bash
#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
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

echo "=== Innovationsbox E2E Test Data Seed ==="
echo "Database: $DB_NAME @ $DB_HOST"
echo ""

echo "1/10 Resolving E2E user IDs..."
# Resolve user IDs from email addresses
INITIATOR_ID=$(echo "SELECT uID FROM Users WHERE uEmail='initiator@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
USER_ID=$(echo "SELECT uID FROM Users WHERE uEmail='user@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
MODERATOR_ID=$(echo "SELECT uID FROM Users WHERE uEmail='moderator@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
EXPERT_ID=$(echo "SELECT uID FROM Users WHERE uEmail='expert@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')

if [[ -z "$INITIATOR_ID" || -z "$USER_ID" || -z "$MODERATOR_ID" || -z "$EXPERT_ID" ]]; then
  echo "ERROR: Could not resolve E2E user IDs. Run seed-e2e-users.php first."
  echo "  initiator=$INITIATOR_ID user=$USER_ID moderator=$MODERATOR_ID expert=$EXPERT_ID"
  exit 1
fi

echo "  initiator=$INITIATOR_ID user=$USER_ID moderator=$MODERATOR_ID expert=$EXPERT_ID"

echo "2/10 Truncating ib_* tables..."
run_sql <<'SQL'
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE ib_project_space_collaboards;
TRUNCATE TABLE ib_project_space_updates;
TRUNCATE TABLE ib_project_space_members;
TRUNCATE TABLE ib_project_spaces;
TRUNCATE TABLE ib_moderation_reviews;
TRUNCATE TABLE ib_project_ideas;
TRUNCATE TABLE ib_categories;
TRUNCATE TABLE ib_expert_requests;
SET FOREIGN_KEY_CHECKS = 1;
SQL

echo "3/10 Seeding categories..."
run_sql <<'SQL'
INSERT INTO ib_categories (id, name, slug, sort_order, created_at, updated_at) VALUES
('cat_energie', 'Energie', 'energie', 0, NOW(), NOW()),
('cat_mobilitaet', 'Mobilität', 'mobilitaet', 1, NOW(), NOW()),
('cat_biodiversitaet', 'Biodiversität', 'biodiversitaet', 2, NOW(), NOW());
SQL

echo "4/10 Seeding project ideas (all 6 statuses)..."
run_sql <<SQL
INSERT INTO ib_project_ideas (id, title, description, category_id, status, submitter_id, rejection_reason, project_space_id, created_at, updated_at) VALUES
('e2e_idea_draft', 'Entwurf: Solarenergie-Projekt', '<p>Ein Entwurf für ein Solarenergie-Projekt auf dem Schulhaus.</p>', 'cat_energie', 'draft', $INITIATOR_ID, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
('e2e_idea_submitted', 'Veloverleih-Netzwerk', '<p>Ein gemeinschaftlicher Veloverleih mit Stationen in verschiedenen Gemeinden.</p>', 'cat_mobilitaet', 'submitted', $INITIATOR_ID, NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
('e2e_idea_under_review', 'Wildtierkorridore kartieren', '<p>Erfassung und Schutz der Wildtierkorridore im Kanton Glarus.</p>', 'cat_biodiversitaet', 'under_review', $INITIATOR_ID, NULL, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY)),
('e2e_idea_approved', 'Fernwärmenetz Glarus Süd', '<p>Planung eines Fernwärmenetzes für die Gemeinden Glarus Süd.</p>', 'cat_energie', 'approved', $INITIATOR_ID, NULL, 'e2e_space_active', DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY)),
('e2e_idea_rejected', 'Klimawanderweg', '<p>Ein thematischer Wanderweg zu Klimawandel-Auswirkungen.</p>', 'cat_biodiversitaet', 'rejected', $INITIATOR_ID, 'Passt nicht zum aktuellen Themenschwerpunkt.', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY)),
('e2e_idea_feedback', 'Regenwassernutzung in Schulen', '<p>Konzept zur Regenwassernutzung für Schulgebäude.</p>', 'cat_energie', 'feedback_requested', $INITIATOR_ID, NULL, NULL, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY));
SQL

echo "5/10 Seeding moderation reviews..."
run_sql <<SQL
INSERT INTO ib_moderation_reviews (id, idea_id, decision, reviewer_id, notes, title_override, description_override, category_override, created_at, reviewed_at, feedback_deadline) VALUES
('e2e_review_submitted', 'e2e_idea_submitted', 'pending', NULL, '', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY), NULL, NULL),
('e2e_review_under_review', 'e2e_idea_under_review', 'pending', NULL, '', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY), NULL, NULL),
('e2e_review_approved', 'e2e_idea_approved', 'approved', $MODERATOR_ID, 'Sehr gute Idee!', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY), NULL),
('e2e_review_rejected', 'e2e_idea_rejected', 'rejected', $MODERATOR_ID, 'Passt nicht zum aktuellen Themenschwerpunkt.', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY), NULL),
('e2e_review_feedback', 'e2e_idea_feedback', 'feedback_requested', $MODERATOR_ID, 'Bitte ergänzen Sie Details zur Kostenplanung.', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_ADD(NOW(), INTERVAL 14 DAY));
SQL

echo "6/10 Seeding project spaces (all 4 statuses)..."
run_sql <<SQL
INSERT INTO ib_project_spaces (id, project_idea_id, title, description, status, initiator_id, category_id, created_at, updated_at) VALUES
('e2e_space_draft', 'e2e_idea_draft', 'Entwurf Solarenergie', 'Solarenergie-Projektraum im Entwurf.', 'draft', $INITIATOR_ID, 'cat_energie', DATE_SUB(NOW(), INTERVAL 5 DAY), NOW()),
('e2e_space_active', 'e2e_idea_approved', 'Fernwärmenetz Glarus Süd', 'Aktiver Projektraum für Fernwärmenetz.', 'active', $INITIATOR_ID, 'cat_energie', DATE_SUB(NOW(), INTERVAL 50 DAY), NOW()),
('e2e_space_completed', NULL, 'Biodiversität Siedlungsraum', 'Abgeschlossenes Pilotprojekt.', 'completed', $INITIATOR_ID, 'cat_biodiversitaet', DATE_SUB(NOW(), INTERVAL 200 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),
('e2e_space_archived', NULL, 'Wanderwege Digital', 'Archiviertes Projekt.', 'archived', $INITIATOR_ID, 'cat_mobilitaet', DATE_SUB(NOW(), INTERVAL 365 DAY), DATE_SUB(NOW(), INTERVAL 60 DAY));
SQL

echo "7/10 Seeding members..."
run_sql <<SQL
INSERT INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at, decided_at) VALUES
-- Active space: initiator + 1 accepted member + 1 pending join request
('e2e_mem_active_init', 'e2e_space_active', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 50 DAY), NULL),
('e2e_mem_active_user', 'e2e_space_active', $USER_ID, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 39 DAY)),
('e2e_mem_active_pending', 'e2e_space_active', $EXPERT_ID, 'developer', 'pending', DATE_SUB(NOW(), INTERVAL 2 DAY), NULL),
-- Draft space: initiator only
('e2e_mem_draft_init', 'e2e_space_draft', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 5 DAY), NULL),
-- Completed space: initiator + member
('e2e_mem_compl_init', 'e2e_space_completed', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 200 DAY), NULL),
('e2e_mem_compl_user', 'e2e_space_completed', $USER_ID, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 190 DAY), DATE_SUB(NOW(), INTERVAL 189 DAY)),
-- Archived space: initiator only
('e2e_mem_arch_init', 'e2e_space_archived', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 365 DAY), NULL);
SQL

echo "8/10 Seeding status updates..."
run_sql <<SQL
INSERT INTO ib_project_space_updates (id, space_id, title, body, author_id, created_at) VALUES
('e2e_upd_1', 'e2e_space_active', 'Kickoff durchgeführt', '<p>Erstes Treffen mit allen Beteiligten.</p>', $INITIATOR_ID, DATE_SUB(NOW(), INTERVAL 45 DAY)),
('e2e_upd_2', 'e2e_space_active', 'Machbarkeitsstudie beauftragt', '<p>Die Studie wird vom Ingenieurbüro durchgeführt.</p>', $INITIATOR_ID, DATE_SUB(NOW(), INTERVAL 20 DAY));
SQL

echo "9/10 Seeding collaboard links..."
run_sql <<SQL
INSERT INTO ib_project_space_collaboards (id, space_id, title, url, added_at) VALUES
('e2e_cb_1', 'e2e_space_active', 'Brainstorming Board', 'https://web.collaboard.app/board/e2e-brainstorm', DATE_SUB(NOW(), INTERVAL 40 DAY));
SQL

echo "10/10 Seeding expert requests..."
run_sql <<SQL
INSERT INTO ib_expert_requests (id, space_id, expert_user_id, requested_by_user_id, message, status, requested_at, decided_at) VALUES
('e2e_ereq_pending', 'e2e_space_active', $EXPERT_ID, $INITIATOR_ID, 'Wir brauchen Expertise im Bereich Energie.', 'pending', DATE_SUB(NOW(), INTERVAL 3 DAY), NULL);
SQL

echo ""
echo "=== E2E Seed Complete ==="
echo "Users: user@e2e.test (ID=$USER_ID), moderator@e2e.test (ID=$MODERATOR_ID), expert@e2e.test (ID=$EXPERT_ID), admin@e2e.test, initiator@e2e.test (ID=$INITIATOR_ID)"
echo "Categories: Energie, Mobilität, Biodiversität"
echo "Ideas: draft, submitted, under_review, approved, rejected, feedback_requested"
echo "Spaces: draft, active, completed, archived"
echo "Active space has: 1 accepted member, 1 pending join request, 1 pending expert request, 2 updates, 1 collaboard link"
```

- [ ] **Step 3: Commit**

```bash
git add scripts/seed-e2e-data.sh Makefile
git commit -m "build: add E2E data seed script and Makefile target"
```

---

### Task 6: Create docker-compose.test.yml

**Files:**
- Create: `docker-compose.test.yml`

- [ ] **Step 1: Create docker-compose.test.yml**

```yaml
# Docker Compose for E2E and Integration testing.
# Usage:
#   docker compose -f docker-compose.test.yml up -d --wait
#   docker compose -f docker-compose.test.yml exec app composer test:integration
#   npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e
#   docker compose -f docker-compose.test.yml down -v

services:
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root_e2e_password
      MYSQL_DATABASE: innovationsbox_test
      MYSQL_USER: innovationsbox_test
      MYSQL_PASSWORD: test_password_e2e
    ports:
      - "13306:3306"
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-proot_e2e_password"]
      interval: 5s
      timeout: 5s
      retries: 10
    tmpfs:
      - /var/lib/mysql

  mailpit:
    image: axllent/mailpit:latest
    ports:
      - "8025:8025"   # Web UI
      - "1025:1025"   # SMTP
    healthcheck:
      test: ["CMD", "wget", "-q", "--spider", "http://localhost:8025/api/v1/info"]
      interval: 5s
      timeout: 3s
      retries: 5

  app:
    build:
      context: .
      dockerfile: Dockerfile.test
    env_file:
      - docker/test.env
    ports:
      - "8080:80"
    depends_on:
      db:
        condition: service_healthy
      mailpit:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/api/health"]
      interval: 10s
      timeout: 5s
      retries: 30
      start_period: 120s
```

- [ ] **Step 2: Commit**

```bash
git add docker-compose.test.yml
git commit -m "build: add docker-compose.test.yml for E2E test environment"
```

---

### Task 7: Update Playwright config for Docker-based tests

**Files:**
- Modify: `tests/e2e/playwright.config.ts:28-66`

The current Playwright config uses `$PROJECT_NAME.local:$APP_PORT` as baseURL. We need to add an `e2e` project that targets the Docker container at `localhost:8080`, and update email helpers to use Mailpit's API instead of the custom PHP email-log-server.

- [ ] **Step 1: Read the current config**

Run: Read `tests/e2e/playwright.config.ts` (already read above — lines 1-66)

- [ ] **Step 2: Add the e2e project targeting Docker**

Add a second project to the `projects` array and a conditional baseURL:

```typescript
// In tests/e2e/playwright.config.ts, replace the existing defineConfig:

const isDocker = process.env.E2E_DOCKER === 'true';
const MAILPIT_URL = isDocker
  ? 'http://localhost:8025/api/v1'
  : null;

export default defineConfig({
  testDir: './specs',
  timeout: 30_000,
  retries: 1,
  use: {
    headless: true,
    extraHTTPHeaders: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
  },
  projects: [
    {
      name: 'chromium',
      use: {
        baseURL: BASE_URL,
        browserName: 'chromium',
        launchOptions: {
          executablePath: chromiumPath,
          args: ['--no-sandbox', '--disable-setuid-sandbox'],
        },
      },
    },
    {
      name: 'e2e',
      use: {
        baseURL: 'http://localhost:8080',
        browserName: 'chromium',
        launchOptions: {
          args: ['--no-sandbox', '--disable-setuid-sandbox'],
        },
      },
    },
  ],
  webServer: isDocker ? [] : [
    {
      command: 'php -S localhost:6007 storybook-server/index.php',
      url: 'http://localhost:6007',
      reuseExistingServer: true,
      timeout: 10_000,
    },
    {
      command: 'php -S localhost:6008 tests/e2e/email-log-server/index.php',
      url: 'http://localhost:6008',
      reuseExistingServer: true,
      timeout: 10_000,
    },
  ],
});
```

Key changes:
- Added `E2E_DOCKER` env var to detect Docker mode
- Added `e2e` project with `baseURL: 'http://localhost:8080'`
- In Docker mode, no webServers are started (CMS and Mailpit run in containers)

- [ ] **Step 3: Run typecheck to verify**

Run: `npx tsc --noEmit`
Expected: No type errors

- [ ] **Step 4: Commit**

```bash
git add tests/e2e/playwright.config.ts
git commit -m "test: add e2e project to Playwright config for Docker tests"
```

---

### Task 8: Add Mailpit helpers to fixtures

**Files:**
- Modify: `tests/e2e/fixtures/helpers.ts:95-118`

Replace the custom email-log-server helpers with Mailpit API helpers. Keep backwards compatibility with the existing email-log-server for the `chromium` project (local dev).

- [ ] **Step 1: Add Mailpit helpers**

Add the following after the existing `emailLogsContain` function at the end of `tests/e2e/fixtures/helpers.ts`:

```typescript
// --- Mailpit API helpers (for Docker-based E2E tests) ---

const MAILPIT_API = process.env.E2E_DOCKER === 'true'
  ? 'http://localhost:8025/api/v1'
  : null;

export interface MailpitMessage {
  ID: string;
  From: { Name: string; Address: string };
  To: Array<{ Name: string; Address: string }>;
  Subject: string;
  Snippet: string;
  Created: string;
}

/**
 * Search Mailpit for messages matching a query.
 * Query syntax: https://mailpit.axllent.org/docs/usage/search-filters/
 */
export async function searchEmails(
  request: APIRequestContext,
  query: string,
  limit = 50,
): Promise<MailpitMessage[]> {
  if (!MAILPIT_API) {
    throw new Error('Mailpit helpers require E2E_DOCKER=true');
  }
  const res = await request.get(
    `${MAILPIT_API}/search?query=${encodeURIComponent(query)}&limit=${limit}`,
  );
  if (!res.ok()) {
    throw new Error(`Mailpit search failed: ${res.status()}`);
  }
  const body = await res.json();
  return body.messages ?? [];
}

/**
 * Wait for an email matching the query to arrive in Mailpit.
 * Polls every 500ms up to the timeout.
 */
export async function waitForEmail(
  request: APIRequestContext,
  query: string,
  timeoutMs = 10_000,
): Promise<MailpitMessage> {
  const start = Date.now();
  while (Date.now() - start < timeoutMs) {
    const messages = await searchEmails(request, query);
    if (messages.length > 0) {
      return messages[0];
    }
    await new Promise((r) => setTimeout(r, 500));
  }
  throw new Error(`Timed out waiting for email matching: ${query}`);
}

/**
 * Delete all messages in Mailpit (call in beforeAll/beforeEach).
 */
export async function clearMailpit(request: APIRequestContext): Promise<void> {
  if (!MAILPIT_API) {
    throw new Error('Mailpit helpers require E2E_DOCKER=true');
  }
  await request.delete(`${MAILPIT_API}/messages`);
}
```

- [ ] **Step 2: Run typecheck**

Run: `npx tsc --noEmit`
Expected: No type errors

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/fixtures/helpers.ts
git commit -m "test: add Mailpit API helpers for Docker-based E2E tests"
```

---

### Task 9: Create the smoke test

**Files:**
- Create: `tests/e2e/specs/00-smoke.spec.ts`

This test validates that the Docker environment is fully functional: CMS is running, all packages are installed, all E2E users can log in, and the API responds correctly.

- [ ] **Step 1: Create tests/e2e/specs/00-smoke.spec.ts**

```typescript
import { test, expect } from '@playwright/test';
import { loginAs } from '../fixtures/helpers';

test.describe('Smoke: Docker environment health', () => {
  test('health endpoint responds', async ({ request }) => {
    const response = await request.get('/api/health');
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(body.status).toBe('ok');
  });

  test('homepage loads', async ({ page }) => {
    await page.goto('/');
    expect(page.url()).not.toContain('/install');
    await expect(page.locator('body')).toBeVisible();
  });

  const testUsers = [
    { email: 'user@e2e.test', label: 'registered user' },
    { email: 'moderator@e2e.test', label: 'moderator' },
    { email: 'expert@e2e.test', label: 'expert' },
    { email: 'admin@e2e.test', label: 'admin' },
    { email: 'initiator@e2e.test', label: 'initiator' },
  ];

  for (const { email, label } of testUsers) {
    test(`login as ${label} (${email})`, async ({ page }) => {
      const api = await loginAs(page, email, 'E2eTest!2026');
      expect(api).toBeTruthy();
    });
  }

  test('API: project ideas endpoint returns data', async ({ page }) => {
    const api = await loginAs(page, 'initiator@e2e.test', 'E2eTest!2026');
    const response = await api.get('/api/project-idea/mine');
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(body.data.length).toBeGreaterThanOrEqual(1);
  });

  test('API: moderation endpoint accessible to moderator', async ({ page }) => {
    const api = await loginAs(page, 'moderator@e2e.test', 'E2eTest!2026');
    const response = await api.get('/api/moderation');
    expect(response.ok()).toBeTruthy();
  });

  test('API: project spaces endpoint returns data', async ({ request }) => {
    const response = await request.get('/api/project-space');
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(body.data.length).toBeGreaterThanOrEqual(1);
  });

  test('API: categories endpoint returns data', async ({ request }) => {
    const response = await request.get('/api/project-space/categories');
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(body.data.length).toBe(3);
  });
});
```

- [ ] **Step 2: Commit**

```bash
git add tests/e2e/specs/00-smoke.spec.ts
git commit -m "test: add E2E smoke test for Docker environment validation"
```

---

### Task 10: Add Pipeline Stage 5

**Files:**
- Modify: `bitbucket-pipelines.yml`

- [ ] **Step 1: Read current pipeline end of PR section**

The PR pipeline currently has 4 stages (lines 21-127). Stage 5 goes after Stage 4 (Security).

- [ ] **Step 2: Add Stage 5 after Stage 4**

Insert after the closing of Stage 4's step (after line 127, before the `tags:` section at line 129):

```yaml
      - step:
          name: "Stage 5: E2E & Integration Tests"
          size: 2x
          caches:
            - npm-cache
            - docker
          services:
            - docker
          script:
            # Build and start the test environment
            - docker compose -f docker-compose.test.yml up -d --build --wait

            # Run integration tests inside the container
            - docker compose -f docker-compose.test.yml exec -T app ./vendor/bin/pest --configuration=phpunit.integration.xml --testsuite=Integration

            # Install Playwright and run E2E tests from the host
            - curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
            - apt-get install -y nodejs
            - npm ci
            - npx playwright install --with-deps chromium
            - E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --reporter=line

            # Tear down
            - docker compose -f docker-compose.test.yml down -v
```

- [ ] **Step 3: Commit**

```bash
git add bitbucket-pipelines.yml
git commit -m "ci: add Pipeline Stage 5 for E2E and integration tests"
```

---

### Task 11: Verify the Docker environment locally

**Files:** None (manual verification)

- [ ] **Step 1: Build and start the environment**

Run: `docker compose -f docker-compose.test.yml up -d --build --wait`
Expected: All 3 services start, `app` health check passes (may take 2-3 minutes on first build)

Timeout: 300s (5 minutes for build + CMS install)

- [ ] **Step 2: Verify CMS is running**

Run: `curl -s http://localhost:8080/api/health | python3 -m json.tool`
Expected: `{"status": "ok", ...}`

- [ ] **Step 3: Verify seed data**

Run: `docker compose -f docker-compose.test.yml exec -T app mysql -h db -u innovationsbox_test -ptest_password_e2e innovationsbox_test -e "SELECT id, status FROM ib_project_ideas ORDER BY id;"`
Expected: 6 rows with statuses: draft, submitted, under_review, approved, rejected, feedback_requested

- [ ] **Step 4: Run the smoke test**

Run: `E2E_DOCKER=true npx playwright test tests/e2e/specs/00-smoke.spec.ts --config=tests/e2e/playwright.config.ts --project=e2e`
Expected: All tests pass

- [ ] **Step 5: Tear down**

Run: `docker compose -f docker-compose.test.yml down -v`
Expected: All containers stopped and volumes removed

- [ ] **Step 6: Commit any fixes**

If any adjustments were needed during verification, commit them:
```bash
git add -A
git commit -m "fix: adjustments from Docker environment verification"
```

---

### Task 12: Update .gitignore and documentation

**Files:**
- Modify: `.gitignore` (if needed)
- Modify: `README.md`

- [ ] **Step 1: Add Docker test artifacts to .gitignore if not already covered**

Check if these are already ignored. If not, add:
```
# Docker test environment
docker/test.env is committed intentionally (no real secrets)
```

No actual changes needed since `docker/test.env` contains test-only credentials that are meant to be committed.

- [ ] **Step 2: Add E2E test commands to README.md**

Add a new section in the Testing area of README.md:

```markdown
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
```

- [ ] **Step 3: Commit**

```bash
git add README.md
git commit -m "docs: add Docker E2E test environment instructions to README"
```

---

## Summary

After completing all 12 tasks, you will have:

- A `Dockerfile.test` that builds a complete CMS image with all dependencies
- A `docker-compose.test.yml` that orchestrates DB + App + Mailpit
- An entrypoint that auto-installs CMS, packages, users, and seed data
- 5 E2E test users (one per role) with known credentials
- Seed data covering all idea statuses and space statuses
- A Playwright `e2e` project targeting the Docker environment
- Mailpit helpers for email verification in tests
- A smoke test validating the full stack
- Pipeline Stage 5 running integration + E2E tests in CI
- Updated README with new commands

**Next:** Phase 2 plan (E2E test coverage for all 32 flows) builds on this foundation.
