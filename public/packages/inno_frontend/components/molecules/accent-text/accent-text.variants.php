<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'text' => $data['content']['quote'],
    ],
];
