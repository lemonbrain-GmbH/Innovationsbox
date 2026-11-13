#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Concrete CMS Setup Script
# ==============================================================================
#
# What it does:
#   - Runs `composer install` inside or outside Docker
#   - Sets necessary file and folder permissions
#   - Optionally deletes `public/packages/` unless --template flag is set
#
# Arguments (in order):
#   $1 → docker_flag:            "true" to use Docker, "false" to run locally
#   $2 → template_flag:          "true" to keep packages folder, "false" to delete it
#   $3 → project_name:           Name of the Docker project (e.g. "lemonbrain2024")
#   $4 → local_user_and_group:   User and group for `chown` (e.g. "root:www-data")
#
# Example:
#   ./create.sh true false lemonbrain2024 root:www-data
#
# ==============================================================================

# ------------------------------------------------------------------------------
# Parse & validate arguments
# ------------------------------------------------------------------------------
if [ "$#" -ne 4 ]; then
  echo "❌ Usage: $0 <docker_flag> <template_flag> <project_name> <local_user_and_group>"
  echo "Example: $0 true false lemonbrain2024 root:www-data"
  exit 1
else
  echo "✅ Arguments received for $0:"
  echo "  docker_flag          = $1"
  echo "  template_flag        = $2"
  echo "  project_name         = $3"
  echo "  local_user_and_group = $4"
fi

docker_flag="$1"
template_flag="$2"
project_name="$3"
local_user_and_group="$4"

[ -f "$HOME/.profile" ] && . "$HOME/.profile"

if [[ "$docker_flag" != "true" && "$docker_flag" != "false" ]]; then
  echo "❌ docker_flag must be 'true' or 'false'"
  exit 1
fi

if [[ "$template_flag" != "true" && "$template_flag" != "false" ]]; then
  echo "❌ template_flag must be 'true' or 'false'"
  exit 1
fi

if [ "$docker_flag" = "true" ]; then
  if [[ -z "$project_name" ]]; then
    echo "❌ project_name must not be empty"
    exit 1
  fi
fi

if [[ -z "$local_user_and_group" ]]; then
  echo "❌ local_user_and_group must not be empty"
  exit 1
fi

# ------------------------------------------------------------------------------
# Define paths
# ------------------------------------------------------------------------------
PROJECT_ROOT="."
PUBLIC_ROOT="$PROJECT_ROOT/public"
APP_ROOT="$PUBLIC_ROOT/application"
PACKAGES_ROOT="$PUBLIC_ROOT/packages"

# ------------------------------------------------------------------------------
# Composer install
# ------------------------------------------------------------------------------
if [ ! -f "$PROJECT_ROOT/composer.json" ]; then
  echo "❌ composer.json not found in \$PROJECT_ROOT ($PROJECT_ROOT)"
  exit 1
fi

if [ -d "$PROJECT_ROOT/vendor" ]; then
  echo "❌ vendor folder already exists in \$PROJECT_ROOT ($PROJECT_ROOT)"
  exit 1
fi

echo "📦 Running composer install..."
if [ "$docker_flag" = "true" ]; then
  docker exec "${project_name}_php-fpm" bash -c "
    cd /usr/local/htdocs/project;
    sg www-data -c 'composer config --no-plugins allow-plugins.composer/installers true';
    sg www-data -c 'composer config --no-plugins allow-plugins.mlocati/composer-patcher true';
    sg www-data -c 'composer config --no-plugins allow-plugins.kylekatarnls/update-helper true';
    sg www-data -c 'composer install'"
else
  echo "⚙️  Configuring composer plugins..."
  composer config --no-plugins allow-plugins.composer/installers true
  composer config --no-plugins allow-plugins.mlocati/composer-patcher true
  composer config --no-plugins allow-plugins.kylekatarnls/update-helper true
  echo "📥 Installing dependencies..."
  composer install
fi
echo "✅ composer install complete."

# ------------------------------------------------------------------------------
# Set permissions
# ------------------------------------------------------------------------------
echo "🔐 Setting permissions..."
# Detect privilege escalator
if [ "$(id -u)" -eq 0 ]; then
  SUDO="" # already root
elif command -v sudo >/dev/null 2>&1; then
  SUDO="sudo"
elif command -v doas >/dev/null 2>&1; then
  SUDO="doas"
else
  SUDO="" # none available
fi

# If we have sudo/doas or are root, do the permission fixes; otherwise skip.
if [ -n "$SUDO" ] || [ "$(id -u)" -eq 0 ]; then
  # chown project root if it exists
  if [ -e "$PROJECT_ROOT" ]; then
    ${SUDO:+$SUDO }chown -R "$local_user_and_group" "$PROJECT_ROOT"
  fi

  # chmod only existing dirs
  for d in "$APP_ROOT/files" "$APP_ROOT/config" "$PACKAGES_ROOT"; do
    if [ -d "$d" ]; then
      ${SUDO:+$SUDO }chmod -R 0775 "$d"
    fi
  done
else
  echo "Skipping chown/chmod: no sudo/doas and not root."
fi
echo "✅ Permissions set."

# ------------------------------------------------------------------------------
# Optionally delete packages folder
# ------------------------------------------------------------------------------
if [ "$template_flag" != "true" ]; then
  echo "🗑 Deleting existing packages..."
  rm -rf "$PACKAGES_ROOT"
  mkdir -p "$PACKAGES_ROOT"
  echo "✅ packages/ folder reset."
else
  echo "📦 Keeping existing packages/ folder (template mode)."
fi
