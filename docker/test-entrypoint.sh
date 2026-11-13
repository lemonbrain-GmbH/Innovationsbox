#!/usr/bin/env bash
set -euo pipefail

echo "=== Innovationsbox Test Environment Entrypoint ==="

# Wait for MySQL to be ready (up to 90 seconds)
# --ssl-mode=DISABLED required because MySQL 8 client defaults to SSL
echo "Waiting for MySQL at $DB_HOSTNAME..."
MYSQL_SSL="--skip-ssl"
MAX_RETRIES=90
for i in $(seq 1 $MAX_RETRIES); do
  if mysqladmin ping -h "$DB_HOSTNAME" -u root -proot_e2e_password $MYSQL_SSL --silent 2>/dev/null; then
    if mysql -h "$DB_HOSTNAME" -u"$DB_USERNAME" -p"$DB_PASSWORD" $MYSQL_SSL -e "SELECT 1" "$DB_DATABASE" >/dev/null 2>&1; then
      echo "MySQL is ready (attempt $i)."
      break
    fi
  fi
  if [ "$i" -eq "$MAX_RETRIES" ]; then
    echo "ERROR: MySQL did not become ready after $MAX_RETRIES seconds."
    echo "Debug: trying root ping..."
    mysqladmin ping -h "$DB_HOSTNAME" -u root -proot_e2e_password $MYSQL_SSL 2>&1 || true
    echo "Debug: trying user connect..."
    mysql -h "$DB_HOSTNAME" -u"$DB_USERNAME" -p"$DB_PASSWORD" $MYSQL_SSL -e "SELECT 1" "$DB_DATABASE" 2>&1 || true
    exit 1
  fi
  sleep 1
done

# Check if CMS is already installed by querying the database for a core table.
# We cannot rely on filesystem markers (generated_overrides/) because COPY in the
# Dockerfile bakes local files into the image even when the DB is empty.
TABLE_COUNT=$(mysql -h "$DB_HOSTNAME" -u"$DB_USERNAME" -p"$DB_PASSWORD" $MYSQL_SSL \
  -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_DATABASE' AND table_name='Users';" 2>/dev/null || echo "0")

if [ "$TABLE_COUNT" -eq 0 ]; then
  DEV_DUMP="/var/www/html/docker/dev-db-dump.sql"

  if [ -f "$DEV_DUMP" ]; then
    # ── Fast path: import dev DB dump for realistic content ──
    echo "=== Importing dev database dump ==="
    mysql -h "$DB_HOSTNAME" -u"$DB_USERNAME" -p"$DB_PASSWORD" $MYSQL_SSL "$DB_DATABASE" < "$DEV_DUMP"
    echo "Dev dump imported ($(wc -c < "$DEV_DUMP" | xargs) bytes)"

    # Overlay test database config so the CMS connects to the Docker DB
    echo "Cleaning stale config artifacts..."
    rm -rf /var/www/html/public/application/config/generated_overrides
    rm -f  /var/www/html/public/application/config/database.php
    cp /var/www/html/docker/test.database.php \
       /var/www/html/public/application/config/test.database.php

    # Rewrite the ApplicationURL so page links point to localhost:8880
    mysql -h "$DB_HOSTNAME" -u"$DB_USERNAME" -p"$DB_PASSWORD" $MYSQL_SSL "$DB_DATABASE" -e \
      "UPDATE Config SET configValue='http://localhost:8880' WHERE configItem='concrete.seo.canonical_url';" 2>/dev/null || true

    # Install missing packages and update all to re-register theme paths,
    # single page overrides, auth view symlinks, and generated config
    echo "=== Installing/updating packages ==="
    PACKAGES="inno_shared inno_frontend inno_moderation inno_project_idea inno_project_space inno_notification"
    for pkg in $PACKAGES; do
      PKG_COUNT=$(mysql -h "$DB_HOSTNAME" -u"$DB_USERNAME" -p"$DB_PASSWORD" $MYSQL_SSL \
        -N -e "SELECT COUNT(*) FROM Packages WHERE pkgHandle='$pkg';" "$DB_DATABASE" 2>/dev/null || echo "0")
      if [ "$PKG_COUNT" -eq 0 ]; then
        echo "Installing package: $pkg"
        /var/www/html/public/concrete/bin/concrete c5:package:install "$pkg" --allow-as-root
      else
        echo "Updating package: $pkg"
        /var/www/html/public/concrete/bin/concrete c5:package:update "$pkg" --allow-as-root 2>/dev/null || true
      fi
    done
    /var/www/html/public/concrete/bin/concrete c5:entities:refresh --allow-as-root 2>/dev/null || true

    # Ensure member pages and public profiles are set up (may be missing from dev dump)
    echo "=== Ensuring member pages ==="
    /var/www/html/public/concrete/bin/concrete c5:exec /var/www/html/scripts/create-member-pages.php || true

  else
    # ── Clean install path (no dev dump) ──
    echo "Cleaning stale config artifacts..."
    rm -rf /var/www/html/public/application/config/generated_overrides
    rm -f  /var/www/html/public/application/config/database.php
    rm -f  /var/www/html/public/application/config/test.database.php

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
      --timezone="$TIMEZONE" \
      --ignore-warnings

    cp /var/www/html/docker/test.database.php \
       /var/www/html/public/application/config/test.database.php

    # Create member pages BEFORE package install so that inno_frontend's
    # registerSinglePageOverrides() finds them and assigns the innovationsbox theme
    echo "=== Creating CMS member pages ==="
    /var/www/html/public/concrete/bin/concrete c5:exec /var/www/html/scripts/create-member-pages.php || true

    echo "=== Installing packages ==="
    PACKAGES="inno_shared inno_frontend inno_moderation inno_project_idea inno_project_space inno_notification"
    for pkg in $PACKAGES; do
      echo "Installing package: $pkg"
      /var/www/html/public/concrete/bin/concrete c5:package:install "$pkg" --allow-as-root
    done
  fi

  # ── Common setup for both paths ──

  echo "=== Activating innovationsbox theme ==="
  /var/www/html/public/concrete/bin/concrete concrete:theme:activate innovationsbox

  echo "=== Setting application config ==="
  /var/www/html/public/concrete/bin/concrete c5:config set innovationsbox.notification.admin_email admin@e2e.test
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.user.registration.email_registration true
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.user.registration.enabled true
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.user.registration.type validate_email

  # Configure SMTP to use Mailpit for email capture
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.mail.method smtp
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.mail.methods.smtp.server "$MAIL_HOST"
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.mail.methods.smtp.port "$MAIL_PORT"

  # Set default sender address (required for MailService::sendMail to work)
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.email.default.address noreply@innovationsbox.test
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.email.default.name "Innovationsbox Test"

  # Disable captcha for registration (browser E2E tests can't solve it)
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.user.registration.captcha false

  # Disable all caching for E2E tests (prevents stale API responses after mutations)
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.cache.enabled false
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.cache.pages false
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.cache.blocks false
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.cache.assets false
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.cache.theme_css false
  /var/www/html/public/concrete/bin/concrete c5:config set concrete.cache.overrides false

  echo "=== Creating E2E test users ==="
  export MYSQL_SSL
  bash /var/www/html/scripts/seed-e2e-users.sh

  echo "=== Seeding test data ==="
  export DOCKER=false
  bash /var/www/html/scripts/seed-e2e-data.sh

  echo "=== Setting display_name for E2E users ==="
  /var/www/html/public/concrete/bin/concrete c5:exec /var/www/html/scripts/set-e2e-display-names.php || true

  # Fix permissions on directories created during install
  chown -R www-data:www-data /var/www/html/public/application/files \
    /var/www/html/public/application/config

  echo "=== Setup complete ==="
else
  echo "CMS already installed, skipping setup."
  # Ensure test database config is in place for restarts
  cp /var/www/html/docker/test.database.php \
     /var/www/html/public/application/config/test.database.php
fi

echo "Starting Apache..."
exec apache2-foreground
