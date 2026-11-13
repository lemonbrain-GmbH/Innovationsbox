<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'levels' => [1, 2, 3, 4, 5, 6],
    'defaults' => [
        'level' => '2',
        'text' => $data['content']['introTitle'],
        'id' => '',
        'variant' => 'default',
    ],
];
