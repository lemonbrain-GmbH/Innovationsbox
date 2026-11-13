<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'logoSrc' => $data['site']['logoSrc'],
        'logoAlt' => $data['site']['logoAlt'],
        'logoHref' => $data['site']['logoHref'],
        'items' => json_encode($data['nav']),
    ],
];
