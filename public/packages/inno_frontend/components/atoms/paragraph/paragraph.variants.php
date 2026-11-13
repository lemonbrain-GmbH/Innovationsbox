<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'variants' => ['body', 'lead', 'small'],
    'defaults' => [
        'variant' => 'body',
        'text' => $data['content']['introText'],
        'id' => '',
    ],
];
