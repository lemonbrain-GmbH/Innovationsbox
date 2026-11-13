#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Concrete CMS Maintenance Script
# ==============================================================================
#
# What it does:
#   - Optionally executes Concrete CMS commands via CLI or Docker:
#     - Set database charset
#     - Fix foreign keys
#     - Refresh entities
#     - Clear cache
#     - Set file/directory permissions
#
# Usage:
#   ./clean.sh <docker_flag> <project_name> <local_user_and_group>
#
# Args:
#   $1: docker_flag            → "true" to run in Docker, "false" for native CLI
#   $2: project_name           → Docker project name prefix (e.g. lemonbrain2024)
#   $3: local_user_and_group   → User:group for chown (e.g. root:www-data)
#
# Example:
#   ./clean.sh lemonbrain2024 root:www-data true
# ==============================================================================

# ------------------------------------------------------------------------------
# Parse and validate arguments
# ------------------------------------------------------------------------------

if [ "$#" -ne 3 ]; then
  echo "❌ Usage: $0 <docker_flag> <project_name> <local_user_and_group>"
  exit 1
else
  echo "✅ Arguments received for $0:"
  echo "  docker_flag          = $1"
  echo "  project_name         = $2"
  echo "  local_user_and_group = $3"
fi

docker_flag="$1"
project_name="$2"
local_user_and_group="$3"

if [ "$docker_flag" = "true" ]; then
  if [[ -z "$project_name" ]]; then
    echo "❌ project_name must not be empty."
    exit 1
  fi
fi

if [[ -z "$local_user_and_group" ]]; then
  echo "❌ local_user_and_group must not be empty."
  exit 1
fi

if [[ "$docker_flag" != "true" && "$docker_flag" != "false" ]]; then
  echo "❌ docker_flag must be either 'true' or 'false'."
  exit 1
fi

# ------------------------------------------------------------------------------
# Optional: Load DB_COLLATION from .env if needed
# ------------------------------------------------------------------------------
# Resolve script dir for relative includes (e.g. helpers/lib.sh)
SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do
  DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
  SOURCE="$(readlink "$SOURCE")"
  [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE"
done
SCRIPT_DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
source "$SCRIPT_DIR/helpers/lib.sh"
load_env_upwards

# ------------------------------------------------------------------------------
# Step 1: Set DB charset
# ------------------------------------------------------------------------------
read -p "Proceed with setting database charset? (y/n): " CONFIRM
if [[ "$CONFIRM" == "y" ]]; then
  if [[ -z "${DB_COLLATION:-}" ]]; then
    echo "❌ DB_COLLATION is not set. Please define it in your .env file."
    exit 1
  fi

  if [[ "$docker_flag" == "false" ]]; then
    ./public/concrete/bin/concrete c5:database:charset:set "$DB_COLLATION"
  else
    docker exec "${project_name}_php-fpm" \
      sh -c "cd /usr/local/htdocs/project/public; sg www-data -c 'concrete/bin/concrete c5:database:charset:set $DB_COLLATION'"
  fi
fi

# ------------------------------------------------------------------------------
# Step 2: Foreign key fix
# ------------------------------------------------------------------------------
read -p "Proceed with setting database foreignkey fix? (y/n): " CONFIRM
if [[ "$CONFIRM" == "y" ]]; then
  if [[ "$docker_flag" == "false" ]]; then
    ./public/concrete/bin/concrete c5:database:foreignkey:fix
  else
    docker exec "${project_name}_php-fpm" \
      sh -c "cd /usr/local/htdocs/project/public; sg www-data -c 'concrete/bin/concrete c5:database:foreignkey:fix'"
  fi
fi

# ------------------------------------------------------------------------------
# Step 3: Refresh entities
# ------------------------------------------------------------------------------
read -p "Proceed with entities refresh? (y/n): " CONFIRM
if [[ "$CONFIRM" == "y" ]]; then
  if [[ "$docker_flag" == "false" ]]; then
    ./public/concrete/bin/concrete5 c5:entities:refresh
  else
    docker exec "${project_name}_php-fpm" \
      sh -c "cd /usr/local/htdocs/project/public; sg www-data -c 'concrete/bin/concrete5 c5:entities:refresh'"
  fi
fi

# ------------------------------------------------------------------------------
# Step 4: Set file permissions
# ------------------------------------------------------------------------------
read -p "Proceed with setting user permissions on the project? (y/n): " CONFIRM
if [[ "$CONFIRM" == "y" ]]; then
  # Escalation helper (run only if root, sudo, or doas exists)
  if [ "$(id -u)" -eq 0 ]; then
    SUDO=""
  elif command -v sudo >/dev/null 2>&1; then
    SUDO="sudo"
  elif command -v doas >/dev/null 2>&1; then
    SUDO="doas"
  else
    SUDO=""
  fi

  OWNER="${LOCAL_USER_AND_GROUP:-$local_user_and_group}"

  if [ -n "$SUDO" ] || [ "$(id -u)" -eq 0 ]; then
    # chown current directory (repo root)
    if [ -n "$OWNER" ]; then
      ${SUDO:+$SUDO }chown -R "$OWNER" .
    fi

    # chmod only if dirs exist
    for d in ./public/application/files ./public/application/config ./public/packages; do
      [ -d "$d" ] && ${SUDO:+$SUDO }chmod -R 0775 "$d"
    done
  else
    echo "Skipping chown/chmod: no sudo/doas and not root."
  fi
fi

# ------------------------------------------------------------------------------
# Step 5: Clear cache
# ------------------------------------------------------------------------------
read -p "Proceed with clearing cache? (y/n): " CONFIRM
if [[ "$CONFIRM" == "y" ]]; then
  if [[ "$docker_flag" == "false" ]]; then
    ./public/concrete/bin/concrete5 c5:clear-cache
  else
    docker exec "${project_name}_php-fpm" \
      sh -c "cd /usr/local/htdocs/project/public; sg www-data -c 'concrete/bin/concrete5 c5:clear-cache; chmod 0775 application/files/cache -R'"
  fi
fi
