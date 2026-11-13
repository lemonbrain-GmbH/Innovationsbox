<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'logoSrc' => $data['site']['logoSrc'],
        'logoAlt' => $data['site']['logoAlt'],
        'logoHref' => $data['site']['logoHref'],
        'navItems' => json_encode($data['nav']),
        'pageHeaderContent' => '<h1>Infos / FAQ</h1>',
        'mainContent' => '<div class="lb-container-narrow spaced-block content-block"><h3>' . $data['content']['aboutTitle'] . '</h3><p>' . $data['content']['aboutText'] . '</p></div>',
        'footerText' => $data['footer']['text'],
        'footerPartners' => json_encode($data['partners']),
    ],
];
