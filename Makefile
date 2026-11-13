.PHONY: export import update clean create install seed

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
export:
	./scripts/export.sh "$(DOCKER)" "$(PROJECT_NAME)" "$(MYSQL_ROOT_PASSWORD)" "$(MYSQL_DATABASE)"

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
import:
	./scripts/import.sh "$(DOCKER)" "$(PROJECT_NAME)" "$(MYSQL_ROOT_PASSWORD)" "$(MYSQL_DATABASE)"

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
#   ./update.sh <docker_flag> <project_name> <c5_version> /home/web/123456/bin
#
# Argumente:
#   $1 → docker_flag    ("true" oder "false")
#   $2 → project_name   (für Docker-Container Prefix)
#   $3 → c5_version     (z. B. "1.3.0" oder "9.2.0")
# ==============================================================================
update:
	./scripts/update.sh "$(DOCKER)" "$(PROJECT_NAME)" "$(C5_VERSION)"

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
#   ./clean.sh <project_name> <local_user_and_group> <docker_flag>
#
# Args:
#   $1: docker_flag            → "true" to run in Docker, "false" for native CLI
#   $2: project_name           → Docker project name prefix (e.g. lemonbrain2024)
#   $3: local_user_and_group   → User:group for chown (e.g. root:www-data)
#
# Example:
#   ./clean.sh true lemonbrain2024 root:www-data
# ==============================================================================
clean:
	./scripts/clean.sh "$(DOCKER)" "$(PROJECT_NAME)" "$(LOCAL_USER_AND_GROUP)"

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
#   ./create.sh true false lemonbrain2024 root:www-data /home/web/123456/bin
#
# ==============================================================================
create:
	./scripts/create.sh "$(DOCKER)" "$(TEMPLATE)" "$(PROJECT_NAME)" "$(LOCAL_USER_AND_GROUP)"

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
install:
	./scripts/install.sh \
		"$(DOCKER)" \
		"$(PROJECT_NAME)" \
		"$(DB_USERNAME)" \
		"$(DB_PASSWORD)" \
		"$(DB_HOSTNAME)" \
		"$(DB_DATABASE)" \
		"$(C5_VERSION)" \
		"$(STARTING_POINT)" \
		"$(ADMIN_EMAIL)" \
		"$(ADMIN_PASSWORD)" \
		"$(DEMO_USERNAME)" \
		"$(DEMO_PASSWORD)" \
		"$(DEMO_EMAIL)" \
		"$(LANGUAGE)"	\
		"$(SITE_LOCALE)" \
		"$(TIMEZONE)"

# DEV_ENV START
docker_build:
	../../../scripts/docker_build.sh ../../../
docker_destroy:
	../../../scripts/docker_destroy.sh

seed:
	@echo "Seeding test data into database..."
	@bash scripts/seed-testdata.sh

seed-e2e:
	@echo "Seeding E2E test data into database..."
	@bash scripts/seed-e2e-data.sh

snapshot-e2e:
	@echo "Snapshotting dev DB for E2E (excluding ib_* tables)..."
	@bash scripts/snapshot-dev-for-e2e.sh
