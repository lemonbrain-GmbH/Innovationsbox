<?php

declare(strict_types=1);

/**
 * Sets up test data for manual UI testing of project spaces.
 *
 * Creates:
 * - 2 Active spaces (one with collaboards/updates/members, one minimal)
 * - 1 Completed space
 * - 1 Draft space (not visible on public overview)
 *
 * Users:
 * - admin (ID 1) — Administrator
 * - Initiator (ID 3) — initiator of Space 1 and Draft space
 * - Entwickler (ID 4) — member of Space 1, can request to join Space 2
 * - Moderator (ID 5) — not a member of any space
 */

$DIR_BASE = dirname(__DIR__) . '/public';
define('DIR_BASE', $DIR_BASE);

require $DIR_BASE . '/concrete/bootstrap/configure.php';
require $DIR_BASE . '/concrete/bootstrap/autoload.php';

$cms = require $DIR_BASE . '/concrete/bootstrap/start.php';
$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();

// Boot inno packages
$service = $app->make(\Concrete\Core\Package\PackageService::class);
foreach ($service->getInstalledList() as $pkg) {
    if (str_starts_with($pkg->getPackageHandle(), 'inno_')) {
        $pkg->getController()->on_start();
    }
}

$db = $app->make(\Concrete\Core\Database\Connection\Connection::class);
$repo = $app->make(\Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface::class);

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;

echo "=== SETTING UP TEST DATA ===\n\n";

// Clean up any existing test data
$db->executeStatement("DELETE FROM ib_project_spaces WHERE id LIKE 'test_%'");
echo "Cleaned up old test data.\n\n";

// --- Space 1: Active, fully populated (Initiator = user 3) ---
echo "Creating Space 1: Solarenergie für Schulen (Active, fully populated)...\n";
$space1 = ProjectSpace::createFromApprovedIdea(
    'test_space_1',
    'test_idea_1',
    'Solarenergie für Schulen',
    'Installation von Solarpanels auf Schulgebäuden im Glarnerland zur nachhaltigen Energieversorgung. Gemeinsam mit lokalen Energieversorgern und Gemeinden planen wir eine nachhaltige Lösung.',
    '3', // Initiator user
);
$space1->activate();

// Add collaboard links
$space1->addCollaboardLink('test_link_1', 'Brainstorming Board', 'https://web.collaboard.app/board/brainstorming-solar');
$space1->addCollaboardLink('test_link_2', 'Projektplan', 'https://web.collaboard.app/board/projektplan-solar');
$space1->addCollaboardLink('test_link_3', 'Umsetzungsplan', 'https://web.collaboard.app/board/umsetzung-solar');

// Add status updates
$space1->addStatusUpdate('test_upd_1', 'Innovation eröffnet', '<p>Der Innovation ist nun aktiv. Wir suchen Entwicklerinnen und Entwickler mit Erfahrung in erneuerbaren Energien und Gebäudetechnik.</p>', '3');
$space1->addStatusUpdate('test_upd_2', 'Team zusammengestellt', '<p>5 Entwicklerinnen und Entwickler haben sich dem Projekt angeschlossen. Wir starten mit einem Kickoff nächste Woche.</p><ul><li>Fokus: Dachflächenanalyse</li><li>Nächster Meilenstein: Erstgespräche mit Gemeinden</li></ul>', '3');
$space1->addStatusUpdate('test_upd_3', 'Erstgespräche mit Gemeinden abgeschlossen', '<p>Wir haben alle drei Gemeinden im Glarnerland kontaktiert und <strong>positive Rückmeldungen</strong> erhalten. Nächster Schritt: Dachflächen-Analyse mit dem Energieversorger.</p>', '3');

// Add Entwickler as member
$space1->addMember('4');

$repo->save($space1);
echo "  ID: test_space_1\n";
echo "  Status: active\n";
echo "  Initiator: user 3 (Initiator)\n";
echo "  Members: user 3 (initiator), user 4 (Entwickler)\n";
echo "  Collaboards: 3\n";
echo "  Updates: 3\n\n";

// --- Space 2: Active, minimal (Initiator = user 1 admin) ---
echo "Creating Space 2: Velonetz Glarus Nord (Active, minimal)...\n";
$space2 = ProjectSpace::createFromApprovedIdea(
    'test_space_2',
    'test_idea_2',
    'Velonetz Glarus Nord',
    'Ausbau sicherer Velowege zwischen den Gemeinden in Glarus Nord. Ziel ist ein zusammenhängendes Netz für den Alltagsverkehr.',
    '1', // Admin is initiator
);
$space2->activate();
$space2->addStatusUpdate('test_upd_4', 'Kickoff-Workshop geplant', '<p>Am 20. Mai findet der erste Workshop statt. Alle Interessierten sind willkommen!</p>', '1');

$repo->save($space2);
echo "  ID: test_space_2\n";
echo "  Status: active\n";
echo "  Initiator: user 1 (admin)\n";
echo "  Members: user 1 only\n";
echo "  → Entwickler (user 4) can request to join this one!\n\n";

// --- Space 3: Completed ---
echo "Creating Space 3: Biodiversität im Siedlungsraum (Completed)...\n";
$space3 = ProjectSpace::createFromApprovedIdea(
    'test_space_3',
    'test_idea_3',
    'Biodiversität im Siedlungsraum',
    'Förderung der Artenvielfalt in Wohngebieten durch naturnahe Gestaltung von Grünflächen und Gärten.',
    '3',
);
$space3->activate();
$space3->addStatusUpdate('test_upd_5', 'Projekt erfolgreich umgesetzt!', '<p>Nach 6 Monaten intensiver Arbeit konnten wir in 12 Gemeinden naturnahe Gärten anlegen. <strong>Vielen Dank an alle Beteiligten!</strong></p>', '3');
$space3->addMember('4');
$space3->addMember('5');
$space3->complete();

$repo->save($space3);
echo "  ID: test_space_3\n";
echo "  Status: completed\n";
echo "  Members: user 3, 4, 5\n\n";

// --- Space 4: Draft (should NOT appear on public overview) ---
echo "Creating Space 4: Fernwärmenetz (Draft — invisible to public)...\n";
$space4 = ProjectSpace::createFromApprovedIdea(
    'test_space_4',
    'test_idea_4',
    'Fernwärmenetz Glarus Süd',
    'Planung eines Fernwärmenetzes für die Gemeinden in Glarus Süd unter Nutzung von Abwärme aus der Industrie.',
    '3',
);
// Stays in Draft — not activated
$repo->save($space4);
echo "  ID: test_space_4\n";
echo "  Status: draft\n";
echo "  → Only visible to initiator (user 3), NOT on public overview\n\n";

// --- Add a pending join request to Space 1 from Moderator ---
echo "Adding pending join request from Moderator (user 5) to Space 1...\n";
$space1 = $repo->findById('test_space_1');
$space1->requestToJoin('test_req_1', '5');
$repo->save($space1);
echo "  Request ID: test_req_1\n";
echo "  → Initiator (user 3) should see this in the sidebar and can accept/reject\n\n";

echo "=== TEST DATA READY ===\n\n";
echo "Test scenarios per user:\n\n";
echo "LOGIN: https://innovationsbox.local/login\n\n";
echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ admin@lemonbrain.ch (pw: 123456) — Administrator           │\n";
echo "│  → /innovationen: sees 2 active + 1 completed space       │\n";
echo "│  → /projektraum/view/test_space_2: is initiator, can manage│\n";
echo "│  → Can archive completed spaces                            │\n";
echo "├─────────────────────────────────────────────────────────────┤\n";
echo "│ initiator@lemonbrain.ch (pw: 123456) — Initiator           │\n";
echo "│  → /innovationen: sees 2 active + 1 completed             │\n";
echo "│  → /projektraum/view/test_space_1: is initiator            │\n";
echo "│    • Can add/remove collaboard links                       │\n";
echo "│    • Can write status updates                              │\n";
echo "│    • Sees pending join request from Moderator → accept/rej │\n";
echo "│    • Can activate draft space (test_space_4)               │\n";
echo "│  → /projektraum/view/test_space_2: is NOT initiator        │\n";
echo "│    • Can only view, cannot manage                          │\n";
echo "├─────────────────────────────────────────────────────────────┤\n";
echo "│ entwickler@lemonbrain.ch (pw: 123456) — Entwickler         │\n";
echo "│  → /innovationen: sees 2 active + 1 completed             │\n";
echo "│  → /projektraum/view/test_space_1: is member               │\n";
echo "│    • Sees collaboards + status updates                     │\n";
echo "│    • Cannot manage (no add/remove/write buttons)           │\n";
echo "│  → /projektraum/view/test_space_2: NOT a member            │\n";
echo "│    • Can click 'Mitmachen' to request to join              │\n";
echo "├─────────────────────────────────────────────────────────────┤\n";
echo "│ moderator@lemonbrain.ch (pw: 123456) — Moderator           │\n";
echo "│  → /innovationen: sees 2 active + 1 completed             │\n";
echo "│  → /projektraum/view/test_space_1: NOT a member            │\n";
echo "│    • Has a pending join request                            │\n";
echo "│    • Can click 'Mitmachen' on Space 2                     │\n";
echo "└─────────────────────────────────────────────────────────────┘\n";
