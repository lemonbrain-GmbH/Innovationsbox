#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Test helper: Dispatches a ProjectIdeaApproved event to trigger project space creation.
 *
 * Usage:
 *   php scripts/test-create-space.php [submitter_user_id]
 *
 * Defaults to user ID 1 (admin). Creates a fake idea ID and fires the event,
 * which triggers OnProjectIdeaApproved → creates a Draft project space.
 */

// Use the Concrete CMS CLI bootstrap (same as bin/concrete)
$DIR_BASE = dirname(__DIR__) . '/public';
define('DIR_BASE', $DIR_BASE);

require $DIR_BASE . '/concrete/bootstrap/configure.php';
require $DIR_BASE . '/concrete/bootstrap/autoload.php';

$cms = require $DIR_BASE . '/concrete/bootstrap/start.php';
$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();

// Manually boot all installed packages (since we're not running the full HTTP runtime)
$service = $app->make(\Concrete\Core\Package\PackageService::class);
$packages = $service->getInstalledList();
foreach ($packages as $pkg) {
    $handle = $pkg->getPackageHandle();
    if (!str_starts_with($handle, 'inno_')) {
        continue;
    }
    $controller = $pkg->getController();
    if (method_exists($controller, 'on_start')) {
        echo "Booting {$handle}...\n";
        $controller->on_start();
    }
}

// Parse args
$submitterId = (string) ($argv[1] ?? '1');
$ideaId = 'idea_test_' . uniqid();

echo "Dispatching ProjectIdeaApproved event...\n";
echo "  Idea ID:      {$ideaId}\n";
echo "  Submitter ID: {$submitterId}\n\n";

// Dispatch the event
$event = new \Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved($ideaId, $submitterId);

$director = $app->make('director');
$director->dispatch('innovationsbox.project_idea.approved', $event);

// Verify it worked
$db = $app->make(\Concrete\Core\Database\Connection\Connection::class);
$row = $db->fetchAssociative('SELECT * FROM ib_project_spaces WHERE project_idea_id = ?', [$ideaId]);

if ($row) {
    echo "SUCCESS — Project space created:\n";
    echo "  Space ID:    {$row['id']}\n";
    echo "  Title:       {$row['title']}\n";
    echo "  Status:      {$row['status']}\n";
    echo "  Initiator:   {$row['initiator_id']}\n";
    echo "  Created at:  {$row['created_at']}\n";

    $members = $db->fetchAllAssociative('SELECT * FROM ib_project_space_members WHERE space_id = ?', [$row['id']]);
    echo '  Members:     ' . count($members) . "\n";
} else {
    echo "FAILED — No project space found for idea {$ideaId}\n";
    echo "Check CMS logs for errors.\n";
}
