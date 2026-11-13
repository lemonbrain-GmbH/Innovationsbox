#!/usr/bin/env bash
set -euo pipefail

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

echo "=== Innovationsbox E2E Test Data Seed ==="
echo "Database: $DB_NAME @ $DB_HOST"
echo ""

echo "1/10 Resolving E2E user IDs..."
# Resolve user IDs from email addresses
INITIATOR_ID=$(echo "SELECT uID FROM Users WHERE uEmail='initiator@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
USER_ID=$(echo "SELECT uID FROM Users WHERE uEmail='user@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
MODERATOR_ID=$(echo "SELECT uID FROM Users WHERE uEmail='moderator@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
EXPERT_ID=$(echo "SELECT uID FROM Users WHERE uEmail='expert@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')
EXPERT2_ID=$(echo "SELECT uID FROM Users WHERE uEmail='expert2@e2e.test';" | run_sql -N 2>/dev/null | tr -d '[:space:]')

if [[ -z "$INITIATOR_ID" || -z "$USER_ID" || -z "$MODERATOR_ID" || -z "$EXPERT_ID" || -z "$EXPERT2_ID" ]]; then
  echo "ERROR: Could not resolve E2E user IDs. Run seed-e2e-users.sh first."
  echo "  initiator=$INITIATOR_ID user=$USER_ID moderator=$MODERATOR_ID expert=$EXPERT_ID expert2=$EXPERT2_ID"
  exit 1
fi

echo "  initiator=$INITIATOR_ID user=$USER_ID moderator=$MODERATOR_ID expert=$EXPERT_ID expert2=$EXPERT2_ID"

echo "2/10 Truncating ib_* tables..."
run_sql <<'SQL'
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE ib_project_space_collaboards;
TRUNCATE TABLE ib_project_space_updates;
TRUNCATE TABLE ib_project_space_members;
TRUNCATE TABLE ib_project_spaces;
TRUNCATE TABLE ib_moderation_reviews;
TRUNCATE TABLE ib_project_ideas;
TRUNCATE TABLE ib_categories;
TRUNCATE TABLE ib_expert_requests;
SET FOREIGN_KEY_CHECKS = 1;
SQL

echo "3/10 Seeding categories..."
run_sql <<'SQL'
INSERT INTO ib_categories (id, name, slug, sort_order, created_at, updated_at) VALUES
('cat_energie', 'Energie', 'energie', 0, NOW(), NOW()),
('cat_mobilitaet', 'Mobilität', 'mobilitaet', 1, NOW(), NOW()),
('cat_biodiversitaet', 'Biodiversität', 'biodiversitaet', 2, NOW(), NOW());
SQL

echo "4/10 Seeding project ideas (all 5 statuses)..."
run_sql <<SQL
INSERT INTO ib_project_ideas (id, title, description, category_id, status, submitter_id, rejection_reason, project_space_id, created_at, updated_at) VALUES
('e2e_idea_draft', 'Entwurf: Solarenergie-Projekt', '<p>Ein Entwurf für ein Solarenergie-Projekt auf dem Schulhaus.</p>', 'cat_energie', 'submitted', $INITIATOR_ID, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
('e2e_idea_submitted', 'Veloverleih-Netzwerk', '<p>Ein gemeinschaftlicher Veloverleih mit Stationen in verschiedenen Gemeinden.</p>', 'cat_mobilitaet', 'submitted', $INITIATOR_ID, NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
('e2e_idea_under_review', 'Wildtierkorridore kartieren', '<p>Erfassung und Schutz der Wildtierkorridore im Kanton Glarus.</p>', 'cat_biodiversitaet', 'under_review', $INITIATOR_ID, NULL, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY)),
('e2e_idea_approved', 'Fernwärmenetz Glarus Süd', '<p>Planung eines Fernwärmenetzes für die Gemeinden Glarus Süd.</p>', 'cat_energie', 'approved', $INITIATOR_ID, NULL, 'e2e_space_active', DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY)),
('e2e_idea_rejected', 'Klimawanderweg', '<p>Ein thematischer Wanderweg zu Klimawandel-Auswirkungen.</p>', 'cat_biodiversitaet', 'rejected', $INITIATOR_ID, 'Passt nicht zum aktuellen Themenschwerpunkt.', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY)),
('e2e_idea_feedback', 'Regenwassernutzung in Schulen', '<p>Konzept zur Regenwassernutzung für Schulgebäude.</p>', 'cat_energie', 'feedback_requested', $INITIATOR_ID, NULL, NULL, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY));
SQL

echo "5/10 Seeding moderation reviews..."
run_sql <<SQL
INSERT INTO ib_moderation_reviews (id, idea_id, decision, reviewer_id, notes, title_override, description_override, category_override, created_at, reviewed_at) VALUES
('e2e_review_submitted', 'e2e_idea_submitted', 'pending', NULL, '', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY), NULL),
('e2e_review_under_review', 'e2e_idea_under_review', 'pending', NULL, '', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY), NULL),
('e2e_review_approved', 'e2e_idea_approved', 'approved', $MODERATOR_ID, 'Sehr gute Idee!', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY)),
('e2e_review_rejected', 'e2e_idea_rejected', 'rejected', $MODERATOR_ID, 'Passt nicht zum aktuellen Themenschwerpunkt.', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY)),
('e2e_review_feedback', 'e2e_idea_feedback', 'feedback_requested', $MODERATOR_ID, 'Bitte ergänzen Sie Details zur Kostenplanung.', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY));
SQL

echo "6/10 Seeding project spaces (all 4 statuses)..."
run_sql <<SQL
INSERT INTO ib_project_spaces (id, project_idea_id, title, description, status, initiator_id, category_id, created_at, updated_at) VALUES
('e2e_space_draft', 'e2e_idea_draft', 'Entwurf Solarenergie', 'Solarenergie-Innovation im Entwurf.', 'draft', $INITIATOR_ID, 'cat_energie', DATE_SUB(NOW(), INTERVAL 5 DAY), NOW()),
('e2e_space_active', 'e2e_idea_approved', 'Fernwärmenetz Glarus Süd', 'Aktiver Innovation für Fernwärmenetz.', 'active', $INITIATOR_ID, 'cat_energie', DATE_SUB(NOW(), INTERVAL 50 DAY), NOW()),
('e2e_space_completed', 'e2e_idea_rejected', 'Biodiversität Siedlungsraum', 'Abgeschlossenes Pilotprojekt.', 'completed', $INITIATOR_ID, 'cat_biodiversitaet', DATE_SUB(NOW(), INTERVAL 200 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),
('e2e_space_archived', 'e2e_idea_under_review', 'Wanderwege Digital', 'Archiviertes Projekt.', 'archived', $INITIATOR_ID, 'cat_mobilitaet', DATE_SUB(NOW(), INTERVAL 365 DAY), DATE_SUB(NOW(), INTERVAL 60 DAY));
SQL

echo "7/10 Seeding members..."
run_sql <<SQL
INSERT INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at, decided_at) VALUES
-- Active space: initiator + 1 accepted member
('e2e_mem_active_init', 'e2e_space_active', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 50 DAY), NULL),
('e2e_mem_active_user', 'e2e_space_active', $USER_ID, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 39 DAY)),
-- Draft space: initiator only
('e2e_mem_draft_init', 'e2e_space_draft', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 5 DAY), NULL),
-- Completed space: initiator + member
('e2e_mem_compl_init', 'e2e_space_completed', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 200 DAY), NULL),
('e2e_mem_compl_user', 'e2e_space_completed', $USER_ID, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 190 DAY), DATE_SUB(NOW(), INTERVAL 189 DAY)),
-- Archived space: initiator only
('e2e_mem_arch_init', 'e2e_space_archived', $INITIATOR_ID, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 365 DAY), NULL);
SQL

echo "8/10 Seeding status updates..."
run_sql <<SQL
INSERT INTO ib_project_space_updates (id, space_id, title, body, author_id, created_at) VALUES
('e2e_upd_1', 'e2e_space_active', 'Kickoff durchgeführt', '<p>Erstes Treffen mit allen Beteiligten.</p>', $INITIATOR_ID, DATE_SUB(NOW(), INTERVAL 45 DAY)),
('e2e_upd_2', 'e2e_space_active', 'Machbarkeitsstudie beauftragt', '<p>Die Studie wird vom Ingenieurbüro durchgeführt.</p>', $INITIATOR_ID, DATE_SUB(NOW(), INTERVAL 20 DAY));
SQL

echo "9/10 Seeding collaboard links..."
run_sql <<SQL
INSERT INTO ib_project_space_collaboards (id, space_id, title, url, added_at) VALUES
('e2e_cb_1', 'e2e_space_active', 'Brainstorming Board', 'https://web.collaboard.app/board/e2e-brainstorm', DATE_SUB(NOW(), INTERVAL 40 DAY));
SQL

echo "10/10 Seeding expert requests..."
run_sql <<SQL
INSERT INTO ib_expert_requests (id, space_id, expert_user_id, requested_by_user_id, message, status, requested_at, decided_at) VALUES
('e2e_ereq_pending', 'e2e_space_active', $EXPERT2_ID, $INITIATOR_ID, 'Wir brauchen Expertise im Bereich Energie.', 'pending', DATE_SUB(NOW(), INTERVAL 3 DAY), NULL);
SQL

echo ""
echo "=== E2E Seed Complete ==="
echo "Users: user@e2e.test (ID=$USER_ID), moderator@e2e.test (ID=$MODERATOR_ID), expert@e2e.test (ID=$EXPERT_ID), expert2@e2e.test (ID=$EXPERT2_ID), admin@e2e.test, initiator@e2e.test (ID=$INITIATOR_ID)"
echo "Categories: Energie, Mobilität, Biodiversität"
echo "Ideas: draft, submitted, under_review, approved, rejected, feedback_requested"
echo "Spaces: draft, active, completed, archived"
echo "Active space has: 1 accepted member, 1 pending expert request (expert2), 2 updates, 1 collaboard link"
