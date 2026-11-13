<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
$cat = $data['categories'][0];
return [
    'defaults' => [
        'title' => $cat['title'],
        'href' => $cat['href'],
        'target' => '_self',
        'imageSrc' => $cat['imageSrc'],
        'imageAlt' => $cat['imageAlt'],
    ],
];
