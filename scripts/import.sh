#!/usr/bin/env bash

set -euo pipefail

# ==============================================================================
# Concrete CMS Project Import Script
# ==============================================================================
#
# What it does:
#   - Imports a .tar.gz backup archive containing:
#       - SQL database dump
#       - Concrete5/ConcreteCMS files and config
#   - Supports local and Docker environments
#   - Cleans previous import/tmp state and confirms each step with the user
#
# Usage:
#   ./import.sh <docker_flag> <project_name> <mysql_root_password> <mysql_database>
#
# Args:
#   $1 → docker_flag           ("true" to use Docker, "false" for local execution)
#   $2 → project_name          (used for Docker container prefix)
#   $3 → mysql_root_password   (used inside Docker)
#   $4 → mysql_database        (name of DB inside Docker)
#
# Other variables like DB_USERNAME, DB_PASSWORD, DB_HOSTNAME, FILES, DIRECTORIES
# are expected in a .env file, loaded via load_env_upwards.
#
# ==============================================================================

# ---------------------------------------------
# Parse and validate script arguments
# ---------------------------------------------
if [ "$#" -ne 4 ]; then
  echo "❌ Usage: $0 <docker_flag> <project_name> <mysql_root_password> <mysql_database>"
  exit 1
else
  echo "✅ Arguments received for $0:"
  echo "  docker_flag         = $1"
  echo "  project_name        = $2"
  echo "  mysql_root_password = ********"
  echo "  mysql_database      = $4"
fi

docker_flag="$1"
project_name="$2"
mysql_root_password="$3"
mysql_database="$4"

if [[ "$docker_flag" != "true" && "$docker_flag" != "false" ]]; then
  echo "❌ docker_flag must be either 'true' or 'false'"
  exit 1
fi

# ---------------------------------------------
# Resolve script directory and load env
# ---------------------------------------------
SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do
  DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
  SOURCE="$(readlink "$SOURCE")"
  [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE"
done
SCRIPT_DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
source "$SCRIPT_DIR/helpers/lib.sh"
load_env_upwards
[ -f "$HOME/.phprc" ] && . "$HOME/.phprc"

PROJECT_ROOT="."
PUBLIC_ROOT="$PROJECT_ROOT/public"
APP_ROOT="$PUBLIC_ROOT/application"
IMPORT_DIR="./import"

echo "Check if import folder exists..."
if [ ! -d $IMPORT_DIR ]; then
  echo "❌ No import folder."
  exit 1
fi

COUNT=$(find ./$IMPORT_DIR -maxdepth 1 -type f -name '*.tar.gz' | wc -l)

if [[ "$COUNT" -eq 1 ]]; then
  echo "✅ Exactly one .tar.gz file found in import/"
else
  echo "❌ Expected exactly one .tar.gz file in $IMPORT_DIR/, but found $COUNT"
  exit 1
fi

TAR_FILE=$(find ./import -maxdepth 1 -type f -name '*.tar.gz' | head -n1)
TAR_NAME=$(basename "$TAR_FILE")

echo "Check tar size..."
du -sh "$TAR_FILE"

echo "---------------------------------------"
echo "Found backup archive: $TAR_NAME"
read -p "Proceed with importing this archive? (y/n): " CONFIRM

if [[ "$CONFIRM" != "y" ]]; then
  echo "❌ Import cancelled by user."
  exit 0
fi

echo "✅ Proceeding with import: $TAR_NAME"

IMPORT_DIR_TMP="$IMPORT_DIR/tmp"

echo "---------------------------------------"
echo "Cleaning import target directory: $IMPORT_DIR_TMP"
rm -rf "$IMPORT_DIR_TMP"
mkdir -p "$IMPORT_DIR_TMP"

echo "Extracting $TAR_NAME into $IMPORT_DIR_TMP..."

tar -xzf "$TAR_FILE" -C "$IMPORT_DIR_TMP"

echo "✅ Extraction complete."

echo "---------------------------------------"
echo "Proceed with importing the database from archive"

DB_IMPORT_FILE=$(find $IMPORT_DIR_TMP -maxdepth 1 -type f -name '*.sql' | head -n1)

if [[ -z "$DB_IMPORT_FILE" ]]; then
  echo "❌ No *.sql file found in $IMPORT_DIR_TMP"
  exit 1
fi

DB_IMPORT_SIZE="$(LC_ALL=C wc -c <"$DB_IMPORT_FILE" | awk '{print $1}')"

if [[ "$DB_IMPORT_SIZE" -eq 0 ]]; then
  echo "❌ SQL file $DB_IMPORT_FILE has size 0. Aborting."
  exit 1
fi

echo "Found SQL file: $(basename "$DB_IMPORT_FILE")"
echo "Size: $DB_IMPORT_SIZE bytes"
echo "---------------------------------------"

read -p "⚠️Do you want to import the database? (y/n): " CONFIRM
if [[ "$CONFIRM" != "y" ]]; then
  echo "⏭️ Skipped database"
else
  # Drop and recreate database (optional safety prompt)
  read -p "⚠️ This will DELETE all current data in the database. Proceed? (y/n): " DROP_CONFIRM

  if [[ "$DROP_CONFIRM" != "y" ]]; then
    echo "❌ Import cancelled."
    exit 0
  fi

  # Drop and recreate database inside container
  echo "Dropping and recreating database..."

  if [[ "$docker_flag" != "true" ]]; then
    if [[ "$CONCRETE5_ENV" == "exigo" ]]; then
      mysql -u "$exigo_db_username" -p"$exigo_db_password" -h "$exigo_db_server" -e "DROP DATABASE IF EXISTS \`$exigo_db_name\`; CREATE DATABASE \`$exigo_db_name\`;"
    else
      mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -h "$DB_HOSTNAME" -e "DROP DATABASE IF EXISTS \`$DB_DATABASE\`; CREATE DATABASE \`$DB_DATABASE\`;"
    fi
  else
    docker exec \
      -e mysql_root_password="$mysql_root_password" \
      -e mysql_database="$mysql_database" \
      "${project_name}_mysql" \
      sh -c 'mysql -u root -p"$mysql_root_password" -e "DROP DATABASE IF EXISTS \`$mysql_database\`; CREATE DATABASE \`$mysql_database\`;"'
  fi

  # Import new SQL dump
  echo "Importing SQL..."
  if [[ "$docker_flag" != "true" ]]; then
    if [[ "$CONCRETE5_ENV" == "exigo" ]]; then
      mysql -u "$exigo_db_username" -p"$exigo_db_password" -h "$exigo_db_server" "$exigo_db_name" <"$DB_IMPORT_FILE"
    else
      mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -h "$DB_HOSTNAME" "$DB_DATABASE" <"$DB_IMPORT_FILE"
    fi
  else
    docker exec -i \
      -e mysql_root_password="$mysql_root_password" \
      -e mysql_database="$mysql_database" \
      "${project_name}_mysql" \
      sh -c 'mysql -u root -p"$mysql_root_password" "$mysql_database"' \
      <"$DB_IMPORT_FILE"
  fi
  echo "✅ Database import completed successfully."
fi

echo "---------------------------------------"
echo "Start importing directories..."

IFS=',' read -ra DIRS <<<"$DIRECTORIES"

for dir in "${DIRS[@]}"; do
  SRC="$IMPORT_DIR_TMP/$dir"
  DEST="$APP_ROOT/$dir"

  echo "Importing $dir..."

  if [[ ! -d "$SRC" ]]; then
    echo "⚠️ Source directory not found: $SRC"
    continue
  fi

  read -p "⚠️ Do you want to import the $dir directory to $DEST? (y/n): " CONFIRM
  if [[ "$CONFIRM" != "y" ]]; then
    echo "⏭️ Skipped $dir"
    continue
  fi

  # Optional: Remove existing target directory before import
  if [[ -d "$DEST" ]]; then
    echo "🧹 Removing existing destination: $DEST"
    rm -rf "$DEST"
  fi

  mkdir -p "$(dirname "$DEST")"
  cp -a "$SRC" "$DEST"

  echo "✅ Imported $dir to $DEST"

  echo "Check import size..."
  du -sh "$DEST"
  echo
done

echo "---------------------------------------"
echo "Import completed for: $DIRECTORIES"

echo "---------------------------------------"
echo "Start importing single files..."

IFS=',' read -ra FILES_ARRAY <<<"$FILES"

for file in "${FILES_ARRAY[@]}"; do
  SRC="$IMPORT_DIR_TMP/$file"

  case "$file" in
  composer.json | composer.lock | .env | auth.json)
    DEST="$PROJECT_ROOT/$file"
    ;;
  *)
    DEST="$PUBLIC_ROOT/$file"
    ;;
  esac

  echo "Importing $file..."

  if [[ ! -f "$SRC" ]]; then
    echo "⚠️ Source file not found: $SRC"
    continue
  fi

  read -p "⚠️ Do you want to import $file to $DEST? (y/n): " CONFIRM
  if [[ "$CONFIRM" != "y" ]]; then
    echo "⏭️ Skipped $file"
    continue
  fi

  cp -a "$SRC" "$DEST"

  echo "✅ Imported $file to $DEST"

  echo "Check import size..."
  du -sh "$DEST"
  echo
done

echo "---------------------------------------"
echo "Import completed for: $FILES"

echo "---------------------------------------"
echo "Cleaning up temporary import directory..."

rm -rf $IMPORT_DIR_TMP

echo "✅ Removed $IMPORT_DIR_TMP"
