<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
$partner = $data['partners'][0];
return [
    'defaults' => [
        'name' => $partner['name'],
        'href' => $partner['href'],
        'imageSrc' => $partner['imageSrc'],
    ],
];
