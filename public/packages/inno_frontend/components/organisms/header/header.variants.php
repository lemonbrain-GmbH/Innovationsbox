<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
$authData = require __DIR__ . '/../../_fixtures/auth-data.php';
$user = $authData['user'];

return [
    'defaults' => [
        'logoSrc' => $data['site']['logoSrc'],
        'logoAlt' => $data['site']['logoAlt'],
        'logoHref' => $data['site']['logoHref'],
        'navItems' => json_encode($data['nav']),
        'authState' => 'logged-out',
        'userName' => $user['name'],
        'userEmail' => $user['email'],
        'userInitials' => $user['initials'],
        'avatarUrl' => $user['avatarUrl'],
        'editProfileUrl' => '/account/edit_profile',
        'logoutUrl' => '/login/do_logout',
    ],
];
