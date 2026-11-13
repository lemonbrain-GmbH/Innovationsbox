<?php

declare(strict_types=1);

$authData = require __DIR__ . '/../../_fixtures/auth-data.php';
$user = $authData['user'];

return [
    'defaults' => [
        'userName' => $user['name'],
        'userInitials' => $user['initials'],
        'avatarUrl' => $user['avatarUrl'],
        'profileUrl' => $user['profileUrl'],
        'memberSince' => $user['memberSince'],
        'role' => $user['role'],
        'expertise' => 'Erneuerbare Energien',
        'email' => $user['email'],
    ],
    'variants' => [
        'anonymous' => [
            'profileUrl' => '',
            'email' => '',
        ],
    ],
];
