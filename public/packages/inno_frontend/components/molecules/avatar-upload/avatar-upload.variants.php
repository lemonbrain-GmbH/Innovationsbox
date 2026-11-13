<?php

declare(strict_types=1);

$authData = require __DIR__ . '/../../_fixtures/auth-data.php';
$user = $authData['user'];

return [
    'defaults' => [
        'hasAvatar' => 'true',
        'avatarUrl' => $user['avatarUrl'],
        'userName' => $user['name'],
        'cropperHtml' => '',
    ],
];
