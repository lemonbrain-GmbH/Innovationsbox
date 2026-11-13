#!/usr/bin/env bash
set -euo pipefail

# ------------------------------------------------------------------------------
# Script: install.sh
# Description:
#   Installs Concrete CMS in a local or Docker environment.
#   This script checks the database, recreates it if necessary,
#   and runs the Concrete CMS installation with the provided settings.
#   It also applies configuration overrides from concrete_config.json.
#
# Usage:
#   ./install.sh \
#     <docker_flag> \
#     <project_name> \
#     <db_username> <db_password> <db_hostname> <db_database> \
#     <c5_version> <starting_point> \
#     <admin_email> <admin_password> \
#     <demo_username> <demo_password> <demo_email> \
#     <language> <site_locale> <timezone>
#
# Example:
#   ./install.sh true lemonbrain root rootpass mysql lemoncms 1.3.0 blank \
#     admin@example.com adminpass demo demopass demo@example.com de_CH de_CH Europe/Zurich
# ------------------------------------------------------------------------------

# Parse arguments
if [ "$#" -ne 16 ]; then
  echo "❌ Usage: $0 <docker_flag> <project_name> <db_username> <db_password> <db_hostname> <db_database> <c5_version> <starting_point> <admin_email> <admin_password> <demo_username> <demo_password> <demo_email> <language> <site_locale> <timezone>"
  exit 1
else
  echo "✅ Arguments received for $0:"
  echo "  docker_flag      = $1"
  echo "  project_name     = $2"
  echo "  DB_USERNAME      = $3"
  echo "  db_password      = ********"
  echo "  db_hostname      = $5"
  echo "  db_database      = $6"
  echo "  c5_version       = $7"
  echo "  starting_point   = $8"
  echo "  admin_email      = $9"
  echo "  admin_password   = ********"
  echo "  demo_username    = ${11}"
  echo "  demo_password    = ********"
  echo "  demo_email       = ${13}"
  echo "  language         = ${14}"
  echo "  site_locale      = ${15}"
  echo "  timezone         = ${16}"
fi

docker_flag="$1"
project_name="$2"
DB_USERNAME="$3"
db_password="$4"
db_hostname="$5"
db_database="$6"
c5_version="$7"
starting_point="$8"
admin_email="$9"
admin_password="${10}"
demo_username="${11}"
demo_password="${12}"
demo_email="${13}"
language="${14}"
site_locale="${15}"
timezone="${16}"

if [[ "$docker_flag" != "true" && "$docker_flag" != "false" ]]; then
  echo "❌ docker_flag must be either 'true' or 'false'"
  exit 1
fi

# Resolve script dir
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

if [ ! -d "$APP_ROOT/config/generated_overrides" ]; then
  echo "⚠️  If something goes wrong, check your env settings on the Concrete Hub or ensure the database server is properly configured."
  if check_db "$docker_flag" "$project_name" "$DB_USERNAME" "$db_password" "$db_hostname" "$db_database"; then
    echo "Database '$db_database' exists."
    if [ -n "$(check_tables "$docker_flag" "$project_name" "$DB_USERNAME" "$db_password" "$db_hostname" "$db_database")" ]; then
      echo "⚠️ Database is not empty."
      read -p "🗑 Delete the database '$db_database'? (y/n): " RESP
      if [ "$RESP" = "y" ]; then
        drop_db "$docker_flag" "$project_name" "$DB_USERNAME" "$db_password" "$db_hostname" "$db_database"
        echo "✅ Database deleted."
      else
        echo "❌ Aborting. Database not deleted." >&2
        exit 1
      fi
    else
      echo "✅ Database exists and is empty. Continuing..."
    fi
  else
    echo "⚠️ Database '$db_database' does not exist."
    read -p "Create it? (y/n): " RESP
    if [ "$RESP" = "y" ]; then
      create_db "$docker_flag" "$project_name" "$DB_USERNAME" "$db_password" "$db_hostname" "$db_database"
      echo "✅ Database created."
    else
      echo "❌ Aborting. Database not created." >&2
      exit 1
    fi
  fi

  if [ "$c5_version" == "1.2.0" ]; then
    c5_bin="concrete5"
  else
    c5_bin="concrete"
  fi

  if [ "$docker_flag" = "true" ]; then
    docker exec "${project_name}_php-fpm" bash -c \
      "sg www-data -c '/usr/local/htdocs/project/public/concrete/bin/${c5_bin} c5:install --allow-as-root \
      --db-server ${db_hostname} --db-username ${DB_USERNAME} --db-password ${db_password} \
      --db-database ${db_database} --site ${project_name} --starting-point ${starting_point} \
      --admin-email ${admin_email} --admin-password ${admin_password} \
      --demo-username ${demo_username} --demo-password ${demo_password} \
      --demo-email ${demo_email} --language ${language} \
      --site-locale ${site_locale} --timezone ${timezone}'"
  else
    "$PUBLIC_ROOT/concrete/bin/${c5_bin}" c5:install --allow-as-root \
      --db-server ${db_hostname} --db-username ${DB_USERNAME} --db-password ${db_password} \
      --db-database ${db_database} --site ${project_name} --starting-point ${starting_point} \
      --admin-email ${admin_email} --admin-password ${admin_password} \
      --demo-username ${demo_username} --demo-password ${demo_password} \
      --demo-email ${demo_email} --language ${language} \
      --site-locale ${site_locale} --timezone ${timezone}
  fi

  CONFIG_FILE=$(find_concrete_config_upwards) || exit 1
  set_concrete_config_from_file "$CONFIG_FILE" "$docker_flag" "$project_name" "$c5_version"
else
  echo "❌ There is already an version installed"
  exit 1
fi

echo "Checking for database.php..."
DATABASE_PHP_EXISTS=$(test -f "$APP_ROOT/database.php" && echo y || echo n)
if [[ "$DATABASE_PHP_EXISTS" == "y" ]]; then
  echo "Is existing, so deleting 🗑"
  rm "$APP_ROOT/database.php"
  echo "✅ database.php deleted."
else
  echo "does not exist"
fi
