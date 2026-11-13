#!/usr/bin/env bash
set -euo pipefail

# Creates E2E test users with correct password hashes and group memberships.
# Runs after c5:install has created the database schema.
#
# Usage: bash scripts/seed-e2e-users.sh
# Requires: DB_* env vars set, CMS installed, packages installed.

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$SCRIPT_DIR/helpers/lib.sh"
# Only load .env if DB_HOSTNAME is not already set (Docker containers set it via env_file)
if [[ -z "${DB_HOSTNAME:-}" ]]; then
  load_env_upwards
fi

DB_HOST="${DB_HOSTNAME:-127.0.0.1}"
DB_USER="${DB_USERNAME:?DB_USERNAME not set}"
DB_PASS="${DB_PASSWORD:?DB_PASSWORD not set}"
DB_NAME="${DB_DATABASE:?DB_DATABASE not set}"
DOCKER="${DOCKER:-false}"
MYSQL_SSL="${MYSQL_SSL:---skip-ssl}"

run_sql() {
  if [[ "$DOCKER" == "true" ]]; then
    docker exec -i "${PROJECT_NAME}_mysql" mysql "$@" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME"
  else
    mysql "$@" -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" $MYSQL_SSL "$DB_NAME"
  fi
}

# Generate bcrypt hash for E2eTest!2026 using PHP (available in the container)
PASSWORD_HASH=$(php -r "echo password_hash('E2eTest!2026', PASSWORD_BCRYPT);")

echo "=== Creating E2E Test Users ==="

# Create users in the Users table
# Concrete CMS Users table: uID (auto), uName, uEmail, uPassword, uIsActive,
# uIsValidated, uDateAdded, uLastLogin, uHasAvatar, uTimezone
run_sql <<SQL
INSERT INTO Users (uName, uEmail, uPassword, uIsActive, uIsFullRecord, uIsValidated, uDateAdded, uLastPasswordChange, uHasAvatar, uIsPasswordReset, uTimezone)
VALUES
  ('e2e-user', 'user@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich'),
  ('e2e-moderator', 'moderator@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich'),
  ('e2e-expert', 'expert@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich'),
  ('e2e-expert2', 'expert2@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich'),
  ('e2e-admin', 'admin@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich'),
  ('e2e-initiator', 'initiator@e2e.test', '$PASSWORD_HASH', 1, 1, 1, NOW(), NOW(), 0, 0, 'Europe/Zurich')
ON DUPLICATE KEY UPDATE uPassword='$PASSWORD_HASH', uIsActive=1, uIsValidated=1;
SQL

echo "Users created."

# Resolve user IDs
USER_ID=$(echo "SELECT uID FROM Users WHERE uEmail='user@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
MOD_ID=$(echo "SELECT uID FROM Users WHERE uEmail='moderator@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
EXPERT_ID=$(echo "SELECT uID FROM Users WHERE uEmail='expert@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
EXPERT2_ID=$(echo "SELECT uID FROM Users WHERE uEmail='expert2@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
ADMIN_ID=$(echo "SELECT uID FROM Users WHERE uEmail='admin@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
INIT_ID=$(echo "SELECT uID FROM Users WHERE uEmail='initiator@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')

echo "  user=$USER_ID moderator=$MOD_ID expert=$EXPERT_ID expert2=$EXPERT2_ID admin=$ADMIN_ID initiator=$INIT_ID"

# Resolve group IDs (created by inno_frontend or CMS core)
# Administrators group always exists (gID=3 in Concrete CMS)
ADMIN_GID=$(echo "SELECT gID FROM \`Groups\` WHERE gName='Administrators';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
MOD_GID=$(echo "SELECT gID FROM \`Groups\` WHERE gName='Moderator';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
EXPERT_GID=$(echo "SELECT gID FROM \`Groups\` WHERE gName='Experte';" | run_sql -N 2>/dev/null | tr -d '[:space:]')

echo "  Groups: Administrators=$ADMIN_GID Moderator=$MOD_GID Experte=$EXPERT_GID"

# Assign users to groups via UserGroups table
# Only insert if the group exists (frontend package creates Moderator and Experte groups)
if [[ -n "$ADMIN_GID" && -n "$ADMIN_ID" ]]; then
  echo "INSERT IGNORE INTO UserGroups (uID, gID, ugEntered) VALUES ($ADMIN_ID, $ADMIN_GID, NOW());" | run_sql
  echo "  admin@e2e.test → Administrators"
fi

if [[ -n "$MOD_GID" && -n "$MOD_ID" ]]; then
  echo "INSERT IGNORE INTO UserGroups (uID, gID, ugEntered) VALUES ($MOD_ID, $MOD_GID, NOW());" | run_sql
  echo "  moderator@e2e.test → Moderator"
fi

if [[ -n "$EXPERT_GID" && -n "$EXPERT_ID" ]]; then
  echo "INSERT IGNORE INTO UserGroups (uID, gID, ugEntered) VALUES ($EXPERT_ID, $EXPERT_GID, NOW());" | run_sql
  echo "  expert@e2e.test → Experte"
fi

if [[ -n "$EXPERT_GID" && -n "$EXPERT2_ID" ]]; then
  echo "INSERT IGNORE INTO UserGroups (uID, gID, ugEntered) VALUES ($EXPERT2_ID, $EXPERT_GID, NOW());" | run_sql
  echo "  expert2@e2e.test → Experte"
fi

# Set display_name user attribute (required for profile completion)
# Find the attribute key ID for display_name
ATTR_KEY_ID=$(echo "SELECT akID FROM AttributeKeys WHERE akHandle='display_name';" | run_sql -N 2>/dev/null | tr -d '[:space:]')

if [[ -n "$ATTR_KEY_ID" ]]; then
  echo "Setting display_name attributes (akID=$ATTR_KEY_ID)..."
  for pair in "$USER_ID:User" "$MOD_ID:Moderator" "$EXPERT_ID:Expert" "$ADMIN_ID:Admin" "$INIT_ID:Initiator"; do
    UID_VAL="${pair%%:*}"
    NAME_VAL="${pair##*:}"
    if [[ -n "$UID_VAL" ]]; then
      # Concrete CMS stores text attributes in atDefault with avID referencing AttributeValues
      # For simplicity, we'll skip attribute values — the profile completion check can be
      # tested separately, and the login flow doesn't strictly require display_name
      :
    fi
  done
  echo "  (Skipped — attribute values require CMS API. Profile completion tests will handle this.)"
else
  echo "  display_name attribute not found (inno_frontend not installed). Skipping."
fi

echo ""
echo "=== E2E Users Seeded ==="
echo "All users: password E2eTest!2026"
