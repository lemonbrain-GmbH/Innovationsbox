<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'label' => $data['buttons']['submit'],
        'href' => '#',
        'icon' => '',
    ],
];
