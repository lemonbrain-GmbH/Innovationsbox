<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';

ob_start();

$props = ['content' => '<h3>' . $data['content']['aboutTitle'] . '</h3><p>' . $data['content']['aboutText'] . '</p><span class="question">' . $data['content']['faqQuestions'][0] . '</span><p>' . $data['content']['introText'] . '</p>'];
include DIR_COMPONENTS . '/organisms/content-block/content-block.php';

$props = ['label' => $data['buttons']['submit'], 'href' => '/einreichen'];
include DIR_COMPONENTS . '/molecules/feature-button/feature-button.php';

$mainContent = ob_get_clean();

$props = [
    'logoSrc' => $data['site']['logoSrc'],
    'logoAlt' => $data['site']['logoAlt'],
    'logoHref' => $data['site']['logoHref'],
    'navItems' => json_encode($data['nav']),
    'pageHeaderContent' => '<h1>' . $data['content']['aboutTitle'] . '</h1>',
    'mainContent' => $mainContent,
    'footerText' => $data['footer']['text'],
    'footerPartners' => json_encode($data['partners']),
];
include DIR_COMPONENTS . '/templates/default-page/default-page.php';
