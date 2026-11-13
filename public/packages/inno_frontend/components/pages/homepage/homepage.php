<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';

$outerProps = [
    'logoSrc' => $data['site']['logoSrc'],
    'logoAlt' => $data['site']['logoAlt'],
    'logoHref' => $data['site']['logoHref'],
    'navItems' => json_encode($data['nav']),
    'heroTitle' => $data['hero']['title'],
    'heroBody' => $data['hero']['body'],
    'heroImageSrc' => $data['hero']['imageSrc'],
    'heroImageAlt' => $data['hero']['imageAlt'],
    'heroButtonLabel' => $data['buttons']['submit'],
    'heroButtonHref' => '/einreichen',
    'mainContent' => '',
    'footerText' => $data['footer']['text'],
    'footerPartners' => json_encode($data['partners']),
];

ob_start();

$props = ['content' => '<h3>' . $data['content']['introTitle'] . '</h3><p>' . $data['content']['introText'] . '</p><span class="accent">' . $data['content']['quote'] . '</span>'];
include DIR_COMPONENTS . '/organisms/content-block/content-block.php';

$props = ['pages' => json_encode($data['categories'])];
include DIR_COMPONENTS . '/organisms/page-list-grid/page-list-grid.php';

$props = ['label' => $data['buttons']['moreInfo'], 'href' => '/projektideen'];
include DIR_COMPONENTS . '/molecules/feature-button/feature-button.php';

$mainContent = ob_get_clean();

$props = $outerProps;
$props['mainContent'] = $mainContent;
include DIR_COMPONENTS . '/templates/view-page/view-page.php';
