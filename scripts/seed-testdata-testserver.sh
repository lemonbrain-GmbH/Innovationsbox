#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$SCRIPT_DIR/helpers/lib.sh"
load_env_upwards

DB_HOST="${DB_HOSTNAME:-127.0.0.1}"
DB_USER="${DB_USERNAME:?DB_USERNAME not set}"
DB_PASS="${DB_PASSWORD:?DB_PASSWORD not set}"
DB_NAME="${DB_DATABASE:?DB_DATABASE not set}"
DOCKER="${DOCKER:-false}"

run_sql() {
  if [[ "$DOCKER" == "true" ]]; then
    docker exec -i "${PROJECT_NAME}_mysql" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME"
  else
    mysql -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME"
  fi
}

echo "=== Innovationsbox Test Data Seed (Testserver) ==="
echo "User IDs: initiator=8, entwickler=9, moderator=10, admin=7"
echo "Database: $DB_NAME @ $DB_HOST"
echo ""

echo "1/9 Truncating ib_* tables..."
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

echo "2/9 Seeding categories..."
run_sql <<'SQL'
INSERT INTO ib_categories (id, name, slug, sort_order, created_at, updated_at) VALUES
('cat_bauen', 'Bauen', 'bauen', 0, NOW(), NOW()),
('cat_wohnen', 'Wohnen', 'wohnen', 1, NOW(), NOW()),
('cat_landwirtschaft', 'Landwirtschaft', 'landwirtschaft', 2, NOW(), NOW()),
('cat_tourismus', 'Tourismus', 'tourismus', 3, NOW(), NOW()),
('cat_mobilitaet', 'Mobilität', 'mobilitaet', 4, NOW(), NOW());
SQL

echo "3/9 Seeding project spaces..."
run_sql <<'SQL'
INSERT INTO ib_project_spaces (id, project_idea_id, title, description, status, initiator_id, category_id, created_at, updated_at) VALUES
('test_space_1', 'idea_1', 'Solarenergie für Schulen', 'Installation von Photovoltaik-Anlagen auf Schulgebäuden im Kanton Glarus. Ziel: 100% erneuerbare Energie für alle Schulen bis 2030.', 'active', 8, 'cat_bauen', DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
('test_space_2', 'idea_2', 'Velonetz Glarus Nord', 'Konzepte für sichere Veloverbindungen zwischen Glarus, Näfels und Mollis. Fokus auf Pendlerrouten und Schulwege.', 'active', 8, 'cat_mobilitaet', DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
('test_space_3', 'idea_3', 'Biodiversität im Siedlungsraum', 'Förderung der Artenvielfalt in Wohngebieten durch naturnahe Gestaltung von Grünflächen und Gärten.', 'completed', 8, 'cat_landwirtschaft', DATE_SUB(NOW(), INTERVAL 240 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),
('test_space_4', 'idea_4', 'Fernwärmenetz Glarus Süd', 'Planung eines Fernwärmenetzes für die Gemeinden Glarus Süd mit erneuerbaren Energiequellen.', 'active', 8, 'cat_wohnen', DATE_SUB(NOW(), INTERVAL 45 DAY), NOW()),
('test_space_5', 'idea_5', 'Wanderwege Digitalisierung', 'Digitale Erfassung und Beschilderung aller Wanderwege im Kanton mit QR-Codes und App-Integration.', 'archived', 8, 'cat_tourismus', DATE_SUB(NOW(), INTERVAL 365 DAY), DATE_SUB(NOW(), INTERVAL 60 DAY)),
('test_space_6', 'idea_6', 'Energetische Sanierung Rathaus', 'Sanierungskonzept für das historische Rathaus unter Berücksichtigung des Denkmalschutzes.', 'draft', 8, 'cat_bauen', DATE_SUB(NOW(), INTERVAL 7 DAY), NOW());
SQL

echo "4/9 Seeding members..."
run_sql <<'SQL'
INSERT INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at, decided_at) VALUES
('mem_1_1', 'test_space_1', 8, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 90 DAY), NULL),
('mem_1_2', 'test_space_1', 9, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 80 DAY), DATE_SUB(NOW(), INTERVAL 79 DAY)),
('mem_1_3', 'test_space_1', 10, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 70 DAY), DATE_SUB(NOW(), INTERVAL 69 DAY)),
('mem_1_4', 'test_space_1', 7, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 50 DAY), DATE_SUB(NOW(), INTERVAL 49 DAY)),
('mem_2_1', 'test_space_2', 8, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 60 DAY), NULL),
('mem_2_2', 'test_space_2', 9, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 55 DAY), DATE_SUB(NOW(), INTERVAL 54 DAY)),
('mem_2_3', 'test_space_2', 10, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 39 DAY)),
('mem_3_1', 'test_space_3', 8, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 240 DAY), NULL),
('mem_3_2', 'test_space_3', 9, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 230 DAY), DATE_SUB(NOW(), INTERVAL 229 DAY)),
('mem_3_3', 'test_space_3', 10, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 200 DAY), DATE_SUB(NOW(), INTERVAL 199 DAY)),
('mem_3_4', 'test_space_3', 7, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 180 DAY), DATE_SUB(NOW(), INTERVAL 179 DAY)),
('mem_4_1', 'test_space_4', 8, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 45 DAY), NULL),
('mem_4_2', 'test_space_4', 9, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY)),
('mem_4_3', 'test_space_4', 7, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY)),
('mem_5_1', 'test_space_5', 8, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 365 DAY), NULL),
('mem_5_2', 'test_space_5', 10, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 350 DAY), DATE_SUB(NOW(), INTERVAL 349 DAY)),
('mem_5_3', 'test_space_5', 9, 'developer', 'accepted', DATE_SUB(NOW(), INTERVAL 300 DAY), DATE_SUB(NOW(), INTERVAL 299 DAY)),
('mem_6_1', 'test_space_6', 8, 'initiator', 'accepted', DATE_SUB(NOW(), INTERVAL 7 DAY), NULL);
SQL

echo "5/9 Seeding status updates..."
run_sql <<'SQL'
INSERT INTO ib_project_space_updates (id, space_id, title, body, author_id, created_at) VALUES
('upd_1_1', 'test_space_1', 'Kickoff-Meeting durchgeführt', '<p>Erstes Treffen mit allen Interessierten. Arbeitsgruppen gebildet und nächste Schritte definiert.</p>', 8, DATE_SUB(NOW(), INTERVAL 80 DAY)),
('upd_1_2', 'test_space_1', 'Umfrage gestartet', '<p>Online-Umfrage zur Priorisierung der Schulgebäude wurde an alle Gemeinden verschickt.</p>', 8, DATE_SUB(NOW(), INTERVAL 45 DAY)),
('upd_1_3', 'test_space_1', 'Ergebnisse der Umfrage ausgewertet', '<p>Die Umfrage hat 127 Antworten erhalten. Wichtigste Erkenntnis: 68% wünschen sich Photovoltaik auf dem Schulhaus Glarus.</p>', 9, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('upd_2_1', 'test_space_2', 'Bestandsaufnahme abgeschlossen', '<p>Alle bestehenden Velowege kartiert und Gefahrenstellen identifiziert.</p>', 8, DATE_SUB(NOW(), INTERVAL 40 DAY)),
('upd_2_2', 'test_space_2', 'Variantenstudie in Arbeit', '<p>Drei Routenvarianten werden derzeit vom Ingenieurbüro geprüft.</p>', 10, DATE_SUB(NOW(), INTERVAL 10 DAY)),
('upd_3_1', 'test_space_3', 'Pilotprojekt gestartet', '<p>In zwei Quartieren werden Blühwiesen und Insektenhotels installiert.</p>', 8, DATE_SUB(NOW(), INTERVAL 180 DAY)),
('upd_3_2', 'test_space_3', 'Projekt erfolgreich abgeschlossen', '<p>Alle geplanten Massnahmen umgesetzt. Monitoring zeigt 40% mehr Insektenarten.</p>', 8, DATE_SUB(NOW(), INTERVAL 35 DAY)),
('upd_4_1', 'test_space_4', 'Machbarkeitsstudie beauftragt', '<p>Die Studie zur Wärmequelle wird vom Ingenieurbüro Müller & Partner durchgeführt.</p>', 8, DATE_SUB(NOW(), INTERVAL 30 DAY)),
('upd_4_2', 'test_space_4', 'Infoveranstaltung geplant', '<p>Am 15. Juni findet eine öffentliche Informationsveranstaltung im Gemeindesaal statt.</p>', 7, DATE_SUB(NOW(), INTERVAL 5 DAY));
SQL

echo "6/9 Seeding collaboard links..."
run_sql <<'SQL'
INSERT INTO ib_project_space_collaboards (id, space_id, title, url, added_at) VALUES
('cb_1_1', 'test_space_1', 'Brainstorming Board', 'https://web.collaboard.app/board/solar-brainstorm', DATE_SUB(NOW(), INTERVAL 75 DAY)),
('cb_1_2', 'test_space_1', 'Massnahmenplan', 'https://web.collaboard.app/board/solar-plan', DATE_SUB(NOW(), INTERVAL 40 DAY)),
('cb_2_1', 'test_space_2', 'Routenplanung', 'https://web.collaboard.app/board/velo-routes', DATE_SUB(NOW(), INTERVAL 50 DAY)),
('cb_4_1', 'test_space_4', 'Konzeptentwurf', 'https://web.collaboard.app/board/waerme-konzept', DATE_SUB(NOW(), INTERVAL 25 DAY));
SQL

echo "7/9 Seeding project ideas..."
run_sql <<'SQL'
INSERT INTO ib_project_ideas (id, title, description, category_id, status, submitter_id, rejection_reason, project_space_id, created_at, updated_at) VALUES
('test_idea_1', 'Solaranlage auf dem Schulhaus', '<p>Eine Solaranlage auf dem Schulhausdach könnte die Schule mit erneuerbarer Energie versorgen und gleichzeitig als Lernprojekt dienen.</p>', 'cat_bauen', 'approved', 9, NULL, 'test_space_1', DATE_SUB(NOW(), INTERVAL 100 DAY), DATE_SUB(NOW(), INTERVAL 90 DAY)),
('test_idea_2', 'Veloverleih-System für Glarus', '<p>Ein gemeinschaftlicher Veloverleih mit Stationen in verschiedenen Gemeinden würde nachhaltige Mobilität fördern.</p>', 'cat_mobilitaet', 'submitted', 8, NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
('test_idea_3', 'Klimawanderweg im Glarnerland', '<p>Ein thematischer Wanderweg, der die Auswirkungen des Klimawandels auf die Alpenregion zeigt.</p>', 'cat_tourismus', 'rejected', 8, 'Passt nicht zum aktuellen Themenschwerpunkt. Bitte im nächsten Quartal erneut einreichen.', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY));
SQL

echo "8/9 Seeding moderation reviews..."
run_sql <<'SQL'
INSERT INTO ib_moderation_reviews (id, idea_id, decision, reviewer_id, notes, title_override, description_override, category_override, created_at, reviewed_at) VALUES
('test_review_1', 'test_idea_1', 'approved', '10', 'Sehr gute Idee! Passt perfekt zur Initiative.', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 100 DAY), DATE_SUB(NOW(), INTERVAL 90 DAY)),
('test_review_2', 'test_idea_2', 'pending', NULL, '', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY), NULL),
('test_review_3', 'test_idea_3', 'rejected', '10', 'Passt nicht zum aktuellen Themenschwerpunkt. Bitte im nächsten Quartal erneut einreichen.', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY));
SQL

echo "9/9 Seeding expert requests..."
run_sql <<'SQL'
-- Expert Requests
TRUNCATE TABLE ib_expert_requests;
INSERT INTO ib_expert_requests (id, space_id, expert_user_id, requested_by_user_id, message, status, requested_at, decided_at) VALUES
('test_ereq_1', 'test_space_1', '11', '8', 'Wir brauchen deine Expertise im Bereich Solarenergie.', 'pending', DATE_SUB(NOW(), INTERVAL 5 DAY), NULL),
('test_ereq_2', 'test_space_2', '11', '9', NULL, 'accepted', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY));
SQL

echo ""
echo "=== Done ==="
echo "Seeded: 5 categories, 6 spaces, 18 members, 9 updates, 4 collaboards, 3 ideas, 3 reviews, 2 expert requests"
echo "Active spaces: test_space_1, test_space_2, test_space_4"
echo "Completed: test_space_3 | Archived: test_space_5 | Draft: test_space_6"
echo "Ideas: test_idea_1 (approved), test_idea_2 (submitted), test_idea_3 (rejected)"
