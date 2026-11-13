#!/usr/bin/env bash
set -euo pipefail

# ------------------------------------------------------------------------------
# load_env_upwards
# ------------------------------------------------------------------------------
# Sucht im aktuellen Verzeichnis und aufwärts nach einer .env-Datei und lädt sie.
# 🔧 Verwendung:
#   load_env_upwards
# (nutzt automatisch das aktuelle $PWD)
load_env_upwards() {
  local dir="$PWD"
  while [[ "$dir" != "/" ]]; do
    if [[ -f "$dir/.env" ]]; then
      echo "✅ Found and loading .env at: $dir/.env"
      set -a
      source "$dir/.env"
      set +a
      return 0
    fi
    dir="$(dirname "$dir")"
  done
  echo "⚠️ No .env found in any parent directory."
  return 1
}

# ------------------------------------------------------------------------------
# find_concrete_config_upwards
# ------------------------------------------------------------------------------
# Sucht im aktuellen Verzeichnis und aufwärts nach concrete_config.json und gibt den Pfad zurück.
# 🔧 Verwendung:
#   CONFIG_FILE=$(find_concrete_config_upwards)
find_concrete_config_upwards() {
  local dir="$PWD"
  while [[ "$dir" != "/" ]]; do
    if [[ -f "$dir/concrete_config.json" ]]; then
      echo "$dir/concrete_config.json"
      return 0
    fi
    dir="$(dirname "$dir")"
  done
  echo "⚠️ No concrete_config.json found."
  return 1
}

# ------------------------------------------------------------------------------
# flatten_concrete_config (jq-based)
# ------------------------------------------------------------------------------
# Emits all scalar leaves as:  <dot.path>\t<type>\t<value>
# Example: concrete.seo.url_rewriting  bool    true
#          site.sites.default.multilingual.default_source_locale string  de_CH
# ------------------------------------------------------------------------------
flatten_concrete_config() {
  local file="$1"
  jq -r '
    paths(scalars) as $p
    | [
        ($p | map(tostring) | join(".")),
        (getpath($p) | type),
        # keep strings as-is; others to their literal text form
        (getpath($p) | (if type=="string" then . else tostring end))
      ]
    | @tsv
  ' "$file"
}

# ------------------------------------------------------------------------------
# set_concrete_config_from_file <config_file> <docker_flag> <project_name> <c5_version>
# ------------------------------------------------------------------------------
# Wendet alle Werte aus concrete_config.json über c5:config set auf das Projekt an.
# 🔧 Verwendung mit globalen Variablen:
#   set_concrete_config_from_file "$CONFIG_FILE" "$DOCKER_FLAG" "$PROJECT_NAME" "$C5_VERSION"
set_concrete_config_from_file() {
  local config_file="$1"
  local docker_flag="$2" # "true" or "false"
  local project_name="$3"
  local c5_version="$4"

  local c5_bin="concrete"
  [ "$c5_version" = "1.2.0" ] && c5_bin="concrete5"

  # runner: pass args directly (no bash -c) to avoid quoting issues
  if [ "$docker_flag" = "true" ]; then
    run_cfg() {
      local key="$1" val="$2"
      docker exec "${project_name}_php-fpm" sh -lc '
        # Expand c5_bin from outer shell
        c5_bin='"$c5_bin"'

        # Sanity checks
        command -v sg >/dev/null 2>&1 || { echo "sg not found in container"; exit 127; }
        bin="/usr/local/htdocs/project/public/concrete/bin/${c5_bin}"
        [ -x "$bin" ] || { echo "Concrete bin not found: $bin"; exit 127; }

        # Run Concrete CLI as group www-data
        sg www-data -c "$bin c5:config set -g \"$1\" \"$2\""
      ' -- "$key" "$val"
    }
  else
    run_cfg() {
      "./public/concrete/bin/${c5_bin}" c5:config set -g "$1" "$2"
    }
  fi

  flatten_concrete_config "$config_file" | while IFS=$'\t' read -r key jtype val; do
    [ -z "$key" ] && continue
    if [ "$jtype" = "null" ]; then
      echo "↩️  Skipping null: $key"
      continue
    fi
    echo "➡️  Setting: $key ($jtype) → $val"
    # No --type flags; just pass the literal value token
    run_cfg "$key" "$val" || {
      echo "❌ Failed: $key" >&2
      return 1
    }
  done
}

# ------------------------------------------------------------------------------
# check_db <docker_flag> <project_name> <db_user> <db_pass> <db_host> <db_name>
# ------------------------------------------------------------------------------
# Prüft, ob die Datenbank erreichbar ist.
# 🔧 Verwendung:
#   check_db "$DOCKER_FLAG" "$PROJECT_NAME" "$DB_USERNAME" "$DB_PASSWORD" "$DB_HOSTNAME" "$DB_DATABASE"
check_db() {
  local docker_flag="$1"
  local project_name="$2"
  local db_user="$3"
  local db_pass="$4"
  local db_host="$5"
  local db_name="$6"

  if [ "$docker_flag" = "true" ]; then
    docker exec "${project_name}_mysql" \
      sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e ";" "'"$db_name"'" 2>/dev/null'
  else
    mysql -u "$db_user" -p"$db_pass" -h "$db_host" -e ";" "$db_name" 2>/dev/null
  fi
}

# ------------------------------------------------------------------------------
# check_tables <docker_flag> <project_name> <db_user> <db_pass> <db_host> <db_name>
# ------------------------------------------------------------------------------
# Gibt Tabellen aus einer bestehenden Datenbank aus.
# 🔧 Verwendung:
#   check_tables "$DOCKER_FLAG" "$PROJECT_NAME" "$DB_USERNAME" "$DB_PASSWORD" "$DB_HOSTNAME" "$DB_DATABASE"
check_tables() {
  local docker_flag="$1"
  local project_name="$2"
  local db_user="$3"
  local db_pass="$4"
  local db_host="$5"
  local db_name="$6"

  if [ "$docker_flag" = "true" ]; then
    docker exec "${project_name}_mysql" \
      sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -Nse "SHOW TABLES;" "'"$db_name"'"'
  else
    mysql -u "$db_user" -p"$db_pass" -h "$db_host" -Nse "SHOW TABLES;" "$db_name"
  fi
}

# ------------------------------------------------------------------------------
# create_db <docker_flag> <project_name> <db_user> <db_pass> <db_host> <db_name>
# ------------------------------------------------------------------------------
# Erstellt eine neue Datenbank.
# 🔧 Verwendung:
#   create_db "$DOCKER_FLAG" "$PROJECT_NAME" "$DB_USERNAME" "$DB_PASSWORD" "$DB_HOSTNAME" "$DB_DATABASE"
create_db() {
  local docker_flag="$1"
  local project_name="$2"
  local db_user="$3"
  local db_pass="$4"
  local db_host="$5"
  local db_name="$6"

  if [ "$docker_flag" = "true" ]; then
    docker exec "${project_name}_mysql" \
      sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE \`'"$db_name"'\`;"'
  else
    mysql -u "$db_user" -p"$db_pass" -h "$db_host" -e "CREATE DATABASE \`$db_name\`;"
  fi
}

# ------------------------------------------------------------------------------
# drop_db <docker_flag> <project_name> <db_user> <db_pass> <db_host> <db_name>
# ------------------------------------------------------------------------------
# Löscht eine bestehende Datenbank.
# 🔧 Verwendung:
#   drop_db "$DOCKER_FLAG" "$PROJECT_NAME" "$DB_USERNAME" "$DB_PASSWORD" "$DB_HOSTNAME" "$DB_DATABASE"
drop_db() {
  local docker_flag="$1"
  local project_name="$2"
  local db_user="$3"
  local db_pass="$4"
  local db_host="$5"
  local db_name="$6"

  if [ "$docker_flag" = "true" ]; then
    docker exec "${project_name}_mysql" \
      sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE \`'"$db_name"'\`;"'
  else
    mysql -u "$db_user" -p"$db_pass" -h "$db_host" -e "DROP DATABASE \`$db_name\`;"
  fi
}
