#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Concrete CMS Project Export Script
# ==============================================================================
#
# What it does:
#   - Exports the MySQL database from local or Docker environment
#   - Exports configured directories and files
#   - Packages everything into a timestamped tar archive
#
# Usage:
#   ./export.sh <docker_flag> <project_name> <mysql_root_password> <mysql_database>
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
# Example:
#   ./export.sh true lemonbrain2024 secret123 lemonbrain2024_db
# ==============================================================================

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

# Groups of acceptable variable names
required_var_groups=(
  "DB_USERNAME exigo_db_username"
  "DB_PASSWORD exigo_db_password"
  "DB_HOSTNAME exigo_db_server"
  "DB_DATABASE exigo_db_name"
  "FILES"
  "DIRECTORIES"
)

for group in "${required_var_groups[@]}"; do
  found=false
  for var in $group; do
    if [[ -n "${!var:-}" ]]; then
      found=true
      break
    fi
  done

  if [[ $found == false ]]; then
    echo "❌ Required env variable not set: one of ($group)"
    exit 1
  fi
done

PROJECT_ROOT="."
PUBLIC_ROOT="$PROJECT_ROOT/public"
APP_ROOT="$PUBLIC_ROOT/application"
EXPORT_DIR="./export"
DATE=$(date +%Y%m%d_%H%M%S)
ARCHIVE_NAME="$DATE.tar.gz"
ARCHIVE_PATH="$EXPORT_DIR/$ARCHIVE_NAME"

echo "Check if export folder exists..."
if [ ! -d "$EXPORT_DIR" ]; then
  echo "Creating export folder"
  mkdir -p "$EXPORT_DIR"
fi

if [[ -n "$(ls -A "$EXPORT_DIR")" ]]; then
  echo "⚠️  Export folder ($EXPORT_DIR) is not empty."
  read -p "Do you want to delete its contents and proceed? (y/n): " CONFIRM
  if [[ "$CONFIRM" != "y" ]]; then
    echo "❌ Aborting as requested."
    exit 1
  fi
  echo "🗑 Deleting all contents in $EXPORT_DIR..."
  rm -rf "$EXPORT_DIR"
  mkdir -p "$EXPORT_DIR"
  echo "✅ Export folder cleaned."
else
  echo "✅ Export folder is empty. Proceeding..."
fi

read -p "Proceed with exporting database from this environment? (y/n): " CONFIRM
if [[ "$CONFIRM" != "y" ]]; then
  echo "Exporting cancelled."
  exit 0
fi

echo "---------------------------------------"
echo "Start exporting database..."
DB_EXPORT_NAME="db-$DATE.sql"

if [[ "$docker_flag" != "true" ]]; then
  if [[ "$CONCRETE5_ENV" == "exigo" ]]; then
    mysqldump --no-tablespaces -u "$exigo_db_username" -p"$exigo_db_password" -h "$exigo_db_server" "$exigo_db_name" >"$EXPORT_DIR/$DB_EXPORT_NAME"
  else
    mysqldump --no-tablespaces -u "$DB_USERNAME" -p"$DB_PASSWORD" -h "$DB_HOSTNAME" "$DB_DATABASE" >"$EXPORT_DIR/$DB_EXPORT_NAME"
  fi
else
  docker exec -e mysql_root_password="$mysql_root_password" -e mysql_database="$mysql_database" "${project_name}_mysql" \
    sh -c 'mysqldump --no-tablespaces -u root -p"$mysql_root_password" "$mysql_database"' \
    >"$EXPORT_DIR/$DB_EXPORT_NAME"
fi

DB_EXPORT_SIZE="$(LC_ALL=C wc -c <"$EXPORT_DIR/$DB_EXPORT_NAME" | awk '{print $1}')"
if [[ "$DB_EXPORT_SIZE" -eq 0 ]]; then
  echo "❌ Database export has size 0. Aborting."
  exit 1
fi

echo "✅ Database export size: $DB_EXPORT_SIZE bytes"

read -p "Proceed with exporting directories from this environment? (y/n): " CONFIRM
if [[ "$CONFIRM" != "y" ]]; then
  echo "Exporting cancelled."
  exit 0
fi

echo "---------------------------------------"
echo "Start exporting directories..."

IFS=',' read -ra DIRS <<<"$DIRECTORIES"
for dir in "${DIRS[@]}"; do
  SRC="$APP_ROOT/$dir"
  DEST="$EXPORT_DIR/$dir"

  echo "Exporting $dir..."
  if [[ ! -d "$SRC" ]]; then
    echo "⚠️ Source directory not found: $SRC"
    continue
  fi

  if [[ "$dir" == "files" && -d "$SRC/cache" ]]; then
    echo "🧹 Removing $SRC/cache before export..."
    rm -rf "$SRC/cache"
    echo "✅ Removed"
  fi

  mkdir -p "$DEST"
  cp -a "$SRC/." "$DEST"
  echo "✅ Exported $dir to $DEST"
  du -sh "$DEST"
done

echo "✅ Export completed for: $DIRECTORIES"

read -p "Proceed with exporting $FILES from this environment? (y/n): " CONFIRM
if [[ "$CONFIRM" != "y" ]]; then
  echo "Exporting cancelled."
  exit 0
fi

echo "---------------------------------------"
echo "Start exporting single files..."

IFS=',' read -ra FILES_ARRAY <<<"$FILES"
for file in "${FILES_ARRAY[@]}"; do
  case "$file" in
  composer.json | composer.lock | .env | auth.json)
    SRC="$PROJECT_ROOT/$file"
    ;;
  *)
    SRC="$PUBLIC_ROOT/$file"
    ;;
  esac

  DEST="$EXPORT_DIR/$file"
  echo "Exporting $file..."
  if [[ ! -f "$SRC" ]]; then
    echo "⚠️ Source file not found: $SRC"
    continue
  fi

  cp -a "$SRC" "$DEST"
  echo "✅ Exported $file to $DEST"
  du -sh "$DEST"
done

echo "✅ Export completed for: $FILES"

read -p "Proceed with creating tar file for this export? (y/n): " CONFIRM
if [[ "$CONFIRM" != "y" ]]; then
  echo "Exporting cancelled."
  exit 0
fi

echo "---------------------------------------"
echo "Creating tarball: $ARCHIVE_PATH"
(
  cd "$EXPORT_DIR"
  if tar --help 2>&1 | grep -q ' --remove-files'; then
    tar --remove-files -czf "$ARCHIVE_NAME" * .??*
  else
    tar -czf "$ARCHIVE_NAME" * .??*
    find . -mindepth 1 -maxdepth 1 ! -name "$(basename "$ARCHIVE_NAME")" -exec rm -rf {} +
  fi
)

echo "✅ Archive created: $ARCHIVE_PATH"
du -sh "$ARCHIVE_PATH"
echo "✅ Export folder now contains only: $ARCHIVE_NAME"
