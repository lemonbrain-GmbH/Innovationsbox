#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Test helper: Exercises all project space domain operations directly.
 *
 * Usage:
 *   php scripts/test-project-space-api.php
 *
 * Tests the full flow: create space via event, activate, add collaboards,
 * write updates, join requests, status transitions — all at the domain layer
 * (bypasses HTTP auth since there's no logged-in user in CLI).
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

$passed = 0;
$failed = 0;

function pass(string $msg): void
{
    global $passed;
    $passed++;
    echo "  \033[32mPASS\033[0m: {$msg}\n";
}

function fail(string $msg): void
{
    global $failed;
    $failed++;
    echo "  \033[31mFAIL\033[0m: {$msg}\n";
}

echo "=== PROJECT SPACE INTEGRATION TEST ===\n\n";

// --- Phase 2: Create space via event ---
echo "Phase 2: Auto-create space via ProjectIdeaApproved event\n";
$ideaId = 'idea_apitest_' . uniqid();
$event = new \Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved($ideaId, '1');
$app->make('director')->dispatch('innovationsbox.project_idea.approved', $event);

$space = $repo->findByProjectIdeaId($ideaId);
if ($space !== null) {
    pass("Space created (id: {$space->getId()}, status: {$space->getStatus()->value})");
    $spaceId = $space->getId();
} else {
    fail("Space not created for idea {$ideaId}");
    exit(1);
}

if ($space->getStatus() === \Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus::Draft) {
    pass('Initial status is Draft');
} else {
    fail("Expected Draft, got {$space->getStatus()->value}");
}

if ($space->getInitiatorId() === '1') {
    pass('Initiator is user 1');
} else {
    fail("Expected initiator 1, got {$space->getInitiatorId()}");
}

if (in_array('1', $space->getMemberIds(), true)) {
    pass('Initiator is a member');
} else {
    fail('Initiator not in member list');
}
echo "\n";

// --- Phase 3: Status transitions ---
echo "Phase 3: Status transition Draft → Active\n";
$space->activate();
$repo->save($space);

$space = $repo->findById($spaceId);
if ($space->getStatus() === \Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus::Active) {
    pass('Status is now Active');
} else {
    fail("Expected Active, got {$space->getStatus()->value}");
}
echo "\n";

// --- Phase 4: Collaboard links ---
echo "Phase 4: Collaboard link management\n";

$link = $space->addCollaboardLink(uniqid('link_', true), 'Brainstorming Board', 'https://web.collaboard.app/board/test123');
$repo->save($space);
$space = $repo->findById($spaceId);

if (count($space->getCollaboardLinks()) === 1) {
    pass("Link added (id: {$space->getCollaboardLinks()[0]->getId()})");
} else {
    fail('Expected 1 link, got ' . count($space->getCollaboardLinks()));
}

// Test invalid URL
try {
    $space->addCollaboardLink('bad_link', 'Bad', 'https://miro.com/board/bad');
    fail('Should have thrown for invalid URL');
} catch (\Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidCollaboardUrlException $e) {
    pass('Invalid collaboard URL correctly rejected');
}

// Add second link then remove first
$link2 = $space->addCollaboardLink(uniqid('link_', true), 'Projektplan', 'https://web.collaboard.app/board/plan');
$repo->save($space);
$space = $repo->findById($spaceId);

if (count($space->getCollaboardLinks()) === 2) {
    pass('Second link added (total: 2)');
} else {
    fail('Expected 2 links, got ' . count($space->getCollaboardLinks()));
}

$firstLinkId = $space->getCollaboardLinks()[0]->getId();
$space->removeCollaboardLink($firstLinkId);
$repo->save($space);
$space = $repo->findById($spaceId);

if (count($space->getCollaboardLinks()) === 1) {
    pass('Link removed (remaining: 1)');
} else {
    fail('Expected 1 link after removal, got ' . count($space->getCollaboardLinks()));
}
echo "\n";

// --- Phase 5: Status updates ---
echo "Phase 5: Status updates\n";

$update = $space->addStatusUpdate(uniqid('upd_', true), 'Kickoff abgeschlossen', '<p>Wir haben gestartet!</p>', '1');
$repo->save($space);
$space = $repo->findById($spaceId);

if (count($space->getStatusUpdates()) === 1) {
    pass("Status update written (title: {$space->getStatusUpdates()[0]->getTitle()})");
} else {
    fail('Expected 1 update, got ' . count($space->getStatusUpdates()));
}

// XSS sanitization happens in the handler, not the domain — but let's verify the body stores correctly
$body = $space->getStatusUpdates()[0]->getBody();
if (str_contains($body, '<p>')) {
    pass('HTML body preserved correctly');
} else {
    fail("Body HTML lost: {$body}");
}
echo "\n";

// --- Phase 6: Join request flow ---
echo "Phase 6: Membership management\n";

$request = $space->requestToJoin(uniqid('req_', true), '42');
$repo->save($space);
$space = $repo->findById($spaceId);

if (count($space->getMembershipRequests()) === 1) {
    $req = $space->getMembershipRequests()[0];
    pass("Join request created (userId: {$req->getUserId()}, status: {$req->getStatus()->value})");
} else {
    fail('Expected 1 request, got ' . count($space->getMembershipRequests()));
}

// Accept the request
$reqId = $space->getMembershipRequests()[0]->getId();
$space->acceptJoinRequest($reqId);
$repo->save($space);
$space = $repo->findById($spaceId);

if (in_array('42', $space->getMemberIds(), true)) {
    pass('User 42 is now a member after acceptance');
} else {
    fail('User 42 not in member list after acceptance');
}

// Test duplicate join prevention
try {
    $space->requestToJoin('dup_req', '42');
    fail('Should reject duplicate join request');
} catch (\DomainException $e) {
    pass("Duplicate join correctly rejected: {$e->getMessage()}");
}

// Test join on Draft space
try {
    $draftSpace = \Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace::createFromApprovedIdea('draft_test', 'idea_draft', 'Draft', '', '1');
    $draftSpace->requestToJoin('req_draft', '2');
    fail('Should reject join on Draft space');
} catch (\DomainException $e) {
    pass('Join on Draft space correctly rejected');
}

// Direct invite
$space->addMember('99');
$repo->save($space);
$space = $repo->findById($spaceId);
if (in_array('99', $space->getMemberIds(), true)) {
    pass('Direct invite: user 99 added as member');
} else {
    fail('User 99 not found after direct invite');
}
echo "\n";

// --- Phase 7: Status lifecycle ---
echo "Phase 7: Full status lifecycle\n";

$space->complete();
$repo->save($space);
$space = $repo->findById($spaceId);
if ($space->getStatus()->value === 'completed') {
    pass('Active → Completed');
} else {
    fail("Expected completed, got {$space->getStatus()->value}");
}

// Invalid transition
try {
    $space->activate();
    fail('Should reject Completed → Active');
} catch (\Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidStatusTransitionException $e) {
    pass('Invalid transition Completed→Active rejected');
}

$space->archive();
$repo->save($space);
$space = $repo->findById($spaceId);
if ($space->getStatus()->value === 'archived') {
    pass('Completed → Archived');
} else {
    fail("Expected archived, got {$space->getStatus()->value}");
}
echo "\n";

// --- Phase 8: Public listing ---
echo "Phase 8: Public listing (findPublic)\n";

// Create a few test spaces with different statuses
$activeSpace = \Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace::createFromApprovedIdea(
    uniqid('space_', true),
    uniqid('idea_', true),
    'Active Test',
    'Desc',
    '1'
);
$activeSpace->activate();
$repo->save($activeSpace);

$completedSpace = \Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace::createFromApprovedIdea(
    uniqid('space_', true),
    uniqid('idea_', true),
    'Completed Test',
    'Desc',
    '1'
);
$completedSpace->activate();
$completedSpace->complete();
$repo->save($completedSpace);

$result = $repo->findPublic(null, 1, 10);
if ($result['total'] >= 2) {
    pass("findPublic returns active+completed spaces (total: {$result['total']})");
} else {
    fail("Expected ≥2 public spaces, got {$result['total']}");
}

$activeOnly = $repo->findPublic(\Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus::Active, 1, 10);
$activeFound = false;
foreach ($activeOnly['data'] as $s) {
    if ($s->getTitle() === 'Active Test') {
        $activeFound = true;
    }
}
if ($activeFound) {
    pass('Filter by Active status works');
} else {
    fail('Active space not found in filtered results');
}
echo "\n";

// --- Cleanup ---
echo "Cleaning up test data...\n";
$db->executeStatement('DELETE FROM ib_project_spaces WHERE id = ?', [$spaceId]);
$db->executeStatement('DELETE FROM ib_project_spaces WHERE id = ?', [$activeSpace->getId()]);
$db->executeStatement('DELETE FROM ib_project_spaces WHERE id = ?', [$completedSpace->getId()]);
echo "Done.\n\n";

echo "=== RESULTS: \033[32m{$passed} passed\033[0m, \033[31m{$failed} failed\033[0m ===\n";
exit($failed > 0 ? 1 : 0);
