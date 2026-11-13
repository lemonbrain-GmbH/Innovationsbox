#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Concrete CMS Projektupdate Script
# ==============================================================================
#
# Dieses Skript aktualisiert eine Concrete CMS Installation.
# Es führt schrittweise folgende Aktionen durch (jeweils mit Bestätigung):
#   - Composer-Abhängigkeiten aktualisieren
#   - Concrete CMS Version aktualisieren (c5:update)
#   - Doctrine Entities refreshen
#   - Konfigurationswerte aus concrete_config.json setzen
#
# Es unterstützt sowohl lokale als auch Docker-basierte Installationen.
#
# ------------------------------------------------------------------------------
# Nutzung:
#   ./update.sh <docker_flag> <project_name> <c5_version>
#
# Argumente:
#   $1 → docker_flag    ("true" oder "false")
#   $2 → project_name   (für Docker-Container Prefix)
#   $3 → c5_version     (z. B. "1.3.0" oder "9.2.0")
# ==============================================================================

# ----------------------------
# Argument-Validierung
# ----------------------------
if [ "$#" -ne 3 ]; then
  echo "❌ Usage: $0 <docker_flag> <project_name> <c5_version>"
  exit 1
else
  echo "✅ Arguments received for $0:"
  echo "  docker_flag          = $1"
  echo "  project_name         = $2"
  echo "  c5_version           = $3"
fi

docker_flag="$1"
project_name="$2"
c5_version="$3"

[ -f "$HOME/.profile" ] && . "$HOME/.profile"
[ -f "$HOME/.phprc" ] && . "$HOME/.phprc"

if [[ "$docker_flag" != "true" && "$docker_flag" != "false" ]]; then
  echo "❌ docker_flag must be 'true' or 'false'"
  exit 1
fi

# ----------------------------
# Skriptverzeichnis und Hilfsfunktionen laden
# ----------------------------
SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do
  DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
  SOURCE="$(readlink "$SOURCE")"
  [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE"
done
SCRIPT_DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
source "$SCRIPT_DIR/helpers/lib.sh"

# ----------------------------
# Composer Update
# ----------------------------
read -p "🚀 Proceed with composer update --dry-run? (y/n): " CONFIRM
if [[ "$CONFIRM" == "y" ]]; then
  if [[ "$docker_flag" == "true" ]]; then
    docker exec "${project_name}_php-fpm" \
      sh -c 'cd /usr/local/htdocs/project; sg www-data -c "composer update --dry-run"'
  else
    composer update --dry-run
  fi
fi

# ----------------------------
# Composer Update
# ----------------------------
read -p "🚀 Proceed with composer update? (y/n): " CONFIRM
if [[ "$CONFIRM" == "y" ]]; then
  if [[ "$docker_flag" == "true" ]]; then
    docker exec "${project_name}_php-fpm" \
      sh -c 'cd /usr/local/htdocs/project; sg www-data -c "composer update"'
  else
    composer update
  fi
fi

# ----------------------------
# Concrete CMS Update
# ----------------------------
read -p "⚙️  Proceed with concrete CMS update? (y/n): " CONFIRM
if [[ "$CONFIRM" == "y" ]]; then
  if [[ "$docker_flag" == "true" ]]; then
    docker exec "${project_name}_php-fpm" \
      sh -c 'cd /usr/local/htdocs/project/public; sg www-data -c "concrete/bin/concrete c5:update"'
  else
    ./public/concrete/bin/concrete c5:update
  fi

  if [[ "$docker_flag" == "false" ]]; then
    ./public/concrete/bin/concrete5 c5:clear-cache
  else
    docker exec "${project_name}_php-fpm" \
      sh -c "cd /usr/local/htdocs/project/public; sg www-data -c 'concrete/bin/concrete5 c5:clear-cache; chmod 0775 application/files/cache -R'"
  fi
fi

# ----------------------------
# Entities Refresh
# ----------------------------
read -p "🧠 Proceed with entities refresh? (y/n): " CONFIRM
if [[ "$CONFIRM" == "y" ]]; then
  if [[ "$docker_flag" == "true" ]]; then
    docker exec "${project_name}_php-fpm" \
      sh -c 'cd /usr/local/htdocs/project/public; sg www-data -c "concrete/bin/concrete c5:entities:refresh"'
  else
    ./public/concrete/bin/concrete c5:entities:refresh
  fi

  if [[ "$docker_flag" == "false" ]]; then
    ./public/concrete/bin/concrete5 c5:clear-cache
  else
    docker exec "${project_name}_php-fpm" \
      sh -c "cd /usr/local/htdocs/project/public; sg www-data -c 'concrete/bin/concrete5 c5:clear-cache; chmod 0775 application/files/cache -R'"
  fi
fi

# ----------------------------
# Concrete Config setzen
# ----------------------------
read -p "🛠 Proceed with setting concrete config? (y/n): " CONFIRM
if [[ "$CONFIRM" == "y" ]]; then
  CONFIG_FILE=$(find_concrete_config_upwards) || {
    echo "❌ concrete_config.json not found."
    exit 1
  }
  echo "✅ Found config at: $CONFIG_FILE"
  set_concrete_config_from_file "$CONFIG_FILE" "$docker_flag" "$project_name" "$c5_version"
fi
