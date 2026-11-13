#!/usr/bin/env bash
set -euo pipefail

# Snapshots the local dev database for the E2E Docker test environment.
# Excludes all ib_* tables (these are seeded by scripts/seed-e2e-data.sh).
#
# Usage: bash scripts/snapshot-dev-for-e2e.sh
# Requires: .env with DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_HOSTNAME

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
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
DUMP_FILE="$REPO_ROOT/docker/dev-db-dump.sql"

echo "=== Innovationsbox Dev → E2E Snapshot ==="
echo "Source: $DB_NAME @ $DB_HOST"
echo "Target: $DUMP_FILE"
echo ""

# Discover all ib_* tables to exclude
echo "Discovering ib_* tables to exclude..."
if [[ "$DOCKER" == "true" ]]; then
  IB_TABLES=$(docker exec -i "${PROJECT_NAME}_mysql" \
    mysql -u"$DB_USER" -p"$DB_PASS" -N -e \
    "SELECT table_name FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name LIKE 'ib_%';" "$DB_NAME" 2>/dev/null)
else
  IB_TABLES=$(mysql -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" $MYSQL_SSL -N -e \
    "SELECT table_name FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name LIKE 'ib_%';" "$DB_NAME" 2>/dev/null)
fi

IGNORE_ARGS=""
for table in $IB_TABLES; do
  echo "  Excluding: $table"
  IGNORE_ARGS="$IGNORE_ARGS --ignore-table=$DB_NAME.$table"
done

echo ""
echo "Dumping database (excluding ib_* tables)..."
if [[ "$DOCKER" == "true" ]]; then
  docker exec -i "${PROJECT_NAME}_mysql" \
    mysqldump -u"$DB_USER" -p"$DB_PASS" --no-tablespaces $IGNORE_ARGS "$DB_NAME" > "$DUMP_FILE"
else
  mysqldump -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" $MYSQL_SSL --no-tablespaces $IGNORE_ARGS "$DB_NAME" > "$DUMP_FILE"
fi

# Also dump the ib_* table STRUCTURES (CREATE TABLE only, no data)
# so the seed scripts can INSERT into them
echo "Dumping ib_* table structures (no data)..."
if [[ -n "$IB_TABLES" ]]; then
  IB_TABLE_LIST=$(echo "$IB_TABLES" | tr '\n' ' ')
  if [[ "$DOCKER" == "true" ]]; then
    docker exec -i "${PROJECT_NAME}_mysql" \
      mysqldump -u"$DB_USER" -p"$DB_PASS" --no-tablespaces --no-data "$DB_NAME" $IB_TABLE_LIST >> "$DUMP_FILE"
  else
    mysqldump -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" $MYSQL_SSL --no-tablespaces --no-data "$DB_NAME" $IB_TABLE_LIST >> "$DUMP_FILE"
  fi
fi

DUMP_SIZE=$(wc -c < "$DUMP_FILE" | xargs)
echo ""
echo "=== Snapshot Complete ==="
echo "File: $DUMP_FILE ($DUMP_SIZE bytes)"
echo ""
echo "Next steps:"
echo "  1. git add docker/dev-db-dump.sql"
echo "  2. git commit -m 'chore: update dev DB snapshot for E2E'"
echo "  3. docker compose -f docker-compose.test.yml up -d --build --wait"
