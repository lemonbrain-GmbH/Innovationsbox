<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'targets' => ['_self', '_blank'],
    'defaults' => [
        'text' => $data['buttons']['moreInfo'],
        'href' => '#',
        'target' => '_self',
    ],
];
