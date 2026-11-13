<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'logoSrc' => $data['site']['logoSrc'],
        'logoAlt' => $data['site']['logoAlt'],
        'logoHref' => $data['site']['logoHref'],
        'navItems' => json_encode($data['nav']),
        'pageTitle' => 'Infos / FAQ',
        'mainContent' => '<div class="lb-container-narrow spaced-block content-block"><span class="question">' . $data['content']['faqQuestions'][0] . '</span><p>' . $data['content']['introText'] . '</p></div>',
        'footerText' => $data['footer']['text'],
        'footerPartners' => json_encode($data['partners']),
    ],
];
