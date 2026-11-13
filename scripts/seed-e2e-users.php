<?php

/**
 * Creates E2E test users with roles.
 *
 * Run inside the CMS environment (after c5:install and package install):
 *   php scripts/seed-e2e-users.php
 *
 * Requires: CONCRETE5_ENV set, CMS installed, packages installed.
 */
declare(strict_types=1);

// Boot Concrete CMS (same pattern as tests/integration/bootstrap.php)
$projectRoot = dirname(__DIR__);
$concretePath = $projectRoot . '/public/concrete';

require_once $projectRoot . '/vendor/autoload.php';

if (!defined('DIR_BASE')) {
    define('DIR_BASE', str_replace(DIRECTORY_SEPARATOR, '/', $projectRoot . '/public'));
}
if (!defined('C5_EXECUTE')) {
    define('C5_EXECUTE', true);
}

require_once $concretePath . '/bootstrap/configure.php';

// Register class aliases (\Core, \Events, etc.) before booting
$cmsConfig = require $concretePath . '/config/app.php';
$aliasList = new \Concrete\Core\Foundation\ClassAliasList();
$aliasList->registerMultiple(array_get($cmsConfig, 'aliases', []));
(new \Concrete\Core\Foundation\AliasClassLoader($aliasList))->register();

$app = require $concretePath . '/bootstrap/start.php';
$app->instance('app', $app);
$app->instance(\Concrete\Core\Application\Application::class, $app);
$app->instance(\Illuminate\Container\Container::class, $app);
$app->getRuntime()->boot();

use Concrete\Core\User\Group\Group;
use Concrete\Core\User\UserInfoRepository;

$userInfoRepo = $app->make(UserInfoRepository::class);

$users = [
    [
        'email'    => 'user@e2e.test',
        'username' => 'e2e-user',
        'password' => 'E2eTest!2026',
        'groups'   => [],
    ],
    [
        'email'    => 'moderator@e2e.test',
        'username' => 'e2e-moderator',
        'password' => 'E2eTest!2026',
        'groups'   => ['Moderator'],
    ],
    [
        'email'    => 'expert@e2e.test',
        'username' => 'e2e-expert',
        'password' => 'E2eTest!2026',
        'groups'   => ['Experte'],
    ],
    [
        'email'    => 'admin@e2e.test',
        'username' => 'e2e-admin',
        'password' => 'E2eTest!2026',
        'groups'   => ['Administrators'],
    ],
    [
        'email'    => 'initiator@e2e.test',
        'username' => 'e2e-initiator',
        'password' => 'E2eTest!2026',
        'groups'   => [],
    ],
];

foreach ($users as $userData) {
    $existing = $userInfoRepo->getByEmail($userData['email']);
    if ($existing) {
        echo "User {$userData['email']} already exists (uID={$existing->getUserID()}), skipping.\n";
        continue;
    }

    $data = [
        'uName'            => $userData['username'],
        'uEmail'           => $userData['email'],
        'uPassword'        => $userData['password'],
        'uPasswordConfirm' => $userData['password'],
        'uIsValidated'     => true,
    ];

    $ui = \Concrete\Core\User\UserInfo::add($data);
    if (!$ui) {
        echo "ERROR: Failed to create user {$userData['email']}\n";
        continue;
    }

    // Activate the user
    $u = $ui->getUserObject();
    $u->activate();

    // Set display_name attribute (required for profile completion)
    $ui->setAttribute('display_name', ucfirst(str_replace('e2e-', '', $userData['username'])));

    // Assign groups
    foreach ($userData['groups'] as $groupName) {
        $group = Group::getByName($groupName);
        if ($group) {
            $u->enterGroup($group);
            echo "  Added to group: {$groupName}\n";
        } else {
            echo "  WARNING: Group '{$groupName}' not found\n";
        }
    }

    echo "Created user: {$userData['email']} (uID={$ui->getUserID()})\n";
}

echo "\nE2E users seeded successfully.\n";
