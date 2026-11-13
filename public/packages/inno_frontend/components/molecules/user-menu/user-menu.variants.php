<?php

declare(strict_types=1);

$authData = require __DIR__ . '/../../_fixtures/auth-data.php';
$user = $authData['user'];

return [
    'defaults' => [
        'userName' => $user['name'],
        'userEmail' => $user['email'],
        'userInitials' => $user['initials'],
        'avatarUrl' => $user['avatarUrl'],
        'editProfileUrl' => '/account/edit_profile',
        'logoutUrl' => '/login/do_logout',
        'isOpen' => 'false',
    ],
];
