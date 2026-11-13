<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'text' => $data['nav'][0]['text'],
        'href' => $data['nav'][0]['href'],
        'active' => 'false',
        'level' => '1',
        'standalone' => 'true',
    ],
];
