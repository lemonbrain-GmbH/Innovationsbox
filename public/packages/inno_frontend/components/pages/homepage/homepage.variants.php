<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'logoSrc' => $data['site']['logoSrc'],
        'logoAlt' => $data['site']['logoAlt'],
        'logoHref' => $data['site']['logoHref'],
        'navItems' => json_encode($data['nav']),
        'heroTitle' => $data['hero']['title'],
        'heroBody' => $data['hero']['body'],
        'heroImageSrc' => $data['hero']['imageSrc'],
        'heroImageAlt' => $data['hero']['imageAlt'],
        'categories' => json_encode($data['categories']),
        'footerText' => $data['footer']['text'],
        'footerPartners' => json_encode($data['partners']),
    ],
];
