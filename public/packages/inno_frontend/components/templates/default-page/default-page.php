<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $logoSrc */
$logoSrc = (string) ($props['logoSrc'] ?? '');
/** @var string $logoAlt */
$logoAlt = (string) ($props['logoAlt'] ?? '');
/** @var string $logoHref */
$logoHref = (string) ($props['logoHref'] ?? '/');
/** @var string $navItems */
$navItems = (string) ($props['navItems'] ?? '[]');
/** @var string $pageHeaderContent */
$pageHeaderContent = (string) ($props['pageHeaderContent'] ?? '');
/** @var string $mainContent */
$mainContent = (string) ($props['mainContent'] ?? '');
/** @var string $footerText */
$footerText = (string) ($props['footerText'] ?? '');
/** @var string $footerPartners */
$footerPartners = (string) ($props['footerPartners'] ?? '[]');
?>
<div class="ccm-page">
    <?php
    $props = ['logoSrc' => $logoSrc, 'logoAlt' => $logoAlt, 'logoHref' => $logoHref, 'navItems' => $navItems];
include DIR_COMPONENTS . '/organisms/header/header.php';
?>

    <header>
        <?= $pageHeaderContent ?>
    </header>

    <main id="main-content-area">
        <?= $mainContent ?>
    </main>

    <?php
$props = ['text' => $footerText, 'partners' => $footerPartners];
include DIR_COMPONENTS . '/organisms/footer/footer.php';
?>
</div>
