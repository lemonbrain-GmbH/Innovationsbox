<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Block\Autonav\Controller $controller */

$navItems = $controller->getNavItems();
$c = \Concrete\Core\Page\Page::getCurrentPage();

if (count($navItems) === 0) {
    if (is_object($c) && $c->isEditMode()) { ?>
        <div class="ccm-edit-mode-disabled-item"><?=t('Empty Auto-Nav Block.')?></div>
    <?php }
    return;
}

// Map CMS nav items to component props (skip home page)
$items = [];
foreach ($navItems as $ni) {
    if ($ni->isHome) {
        continue;
    }

    $items[] = [
        'text' => $ni->name,
        'href' => $ni->url,
        'target' => $ni->target,
        'active' => $ni->isCurrent || $ni->inPath ? 'true' : 'false',
        'level' => (string) $ni->level,
        'hasSubmenu' => $ni->hasSubmenu,
        'subDepth' => (int) $ni->subDepth,
    ];
}

// Logo from site config (optional)
/** @var \Concrete\Core\Application\Application $app */
$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
/** @var \Concrete\Core\Entity\Site\Site $site */
$site = $app->make('site')->getSite();
$logoFile = $site->getAttribute('main_logo');
$logoSrc = is_object($logoFile) ? $logoFile->getRelativePath() : '';

$props = [
    'logoSrc' => $logoSrc,
    'logoAlt' => t('Innovationsbox'),
    'logoHref' => '/',
    'items' => json_encode($items),
];

include DIR_COMPONENTS . '/organisms/navigation-drawer/navigation-drawer.php';
