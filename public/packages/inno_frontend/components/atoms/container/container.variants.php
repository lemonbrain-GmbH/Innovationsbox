<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'variants' => ['default', 'medium', 'small', 'narrow'],
    'defaults' => [
        'variant' => 'default',
        'spaced' => 'false',
        'content' => '<p>' . $data['content']['introText'] . '</p>',
    ],
];
