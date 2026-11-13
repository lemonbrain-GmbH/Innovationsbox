<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'variants' => ['light', 'dark'],
    'defaults' => [
        'variant' => 'light',
        'content' => '<h2>' . $data['hero']['title'] . '</h2>' . $data['hero']['body'],
    ],
];
