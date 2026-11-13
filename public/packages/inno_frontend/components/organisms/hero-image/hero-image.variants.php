<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'title' => $data['hero']['title'],
        'body' => $data['hero']['body'],
        'imageSrc' => $data['hero']['imageSrc'],
        'imageAlt' => $data['hero']['imageAlt'],
        'buttonLabel' => $data['buttons']['submit'],
        'buttonHref' => '#',
    ],
];
