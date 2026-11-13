<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Localization\Localization;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Application as ApplicationFacade;
use Concrete\Core\User\User;
use Concrete\Core\View\View;

/** @var \Concrete\Core\Page\Page $c */
/** @var \Concrete\Core\View\View $view */

$app = ApplicationFacade::getFacadeApplication();

/** @var \Concrete\Core\Package\Package $pkg */
$pkg = $app->make(PackageService::class)->getByHandle('inno_frontend');
$icon_url = (string) $pkg->getRelativePath() . '/icon.png';

/** @var string|null $siteTitle */
$siteTitle = Config::get('concrete.site');
$page = Page::getCurrentPage();
/** @var mixed $metaTitle */
$metaTitle = $page->getAttribute('meta_title');
/** @var string $url */
$url = (string) $page->getCollectionLink();
/** @var string $pageName */
$pageName = (string) $page->getCollectionName();

$stateClass = 'is-view ';
if ($page->isHomePage()) {
    $stateClass .= 'is-home ';
}
$u = $app->make(User::class);
?>

<!DOCTYPE HTML>
<html lang="<?php echo Localization::activeLanguage() ?>">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="<?= h($app->make('token')->generate('api')) ?>">
    <meta name="theme-path" content="<?= h($pkg->getRelativePath() . '/themes/innovationsbox') ?>">

    <meta property="og:url" content="<?= h($url) ?>">

    <script>
        window.isEditMode = false;
        window.isLoggedIn = false;
        <?php if ($u->isRegistered()) { ?>
            window.isLoggedIn = true;
            <?php $stateClass .= 'is-logged '; ?>
        <?php } ?>

        <?php if (Page::getCurrentPage()->isEditMode()) { ?>
            window.isEditMode = true;
            <?php $stateClass .= 'is-edit '; ?>
        <?php } ?>
    </script>

    <?php View::element('header_required', ['pageTitle' => h($metaTitle ? $metaTitle : ($pageName ? $pageName : ($siteTitle ?? '')))]); ?>
    <link rel="icon" href="<?= $icon_url ?>" type="image/png" sizes="97x97">

    <?php $themePath = $pkg->getRelativePath() . '/themes/innovationsbox'; ?>
    <style>
        @font-face {
            font-family: 'Open Sans';
            font-stretch: 75% 100%;
            src: url('<?= $themePath ?>/fonts/OpenSans-VariableFont_wdth,wght.ttf') format('truetype');
            font-weight: 300 800;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Open Sans';
            font-stretch: 75% 100%;
            src: url('<?= $themePath ?>/fonts/OpenSans-Italic-VariableFont_wdth,wght.ttf') format('truetype');
            font-weight: 300 800;
            font-style: italic;
            font-display: swap;
        }
    </style>

    <?=$view->getThemeStyles()?>
</head>

<body id="page<?php echo $c->getCollectionID(); ?>" data-enhance="false">
    <div class="<?php echo $c->getPageWrapperClass() ?> <?= $stateClass ?>">
