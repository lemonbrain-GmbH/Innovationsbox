<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'content' => '<h3>' . $data['content']['introTitle'] . '</h3><p>' . $data['content']['introText'] . '</p>',
    ],
];
