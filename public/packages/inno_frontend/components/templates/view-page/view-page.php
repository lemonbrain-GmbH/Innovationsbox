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
/** @var string $heroTitle */
$heroTitle = (string) ($props['heroTitle'] ?? '');
/** @var string $heroBody */
$heroBody = (string) ($props['heroBody'] ?? '');
/** @var string $heroImageSrc */
$heroImageSrc = (string) ($props['heroImageSrc'] ?? '');
/** @var string $heroImageAlt */
$heroImageAlt = (string) ($props['heroImageAlt'] ?? '');
/** @var string $heroButtonLabel */
$heroButtonLabel = (string) ($props['heroButtonLabel'] ?? '');
/** @var string $heroButtonHref */
$heroButtonHref = (string) ($props['heroButtonHref'] ?? '#');
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
        <?php
    $props = [
        'title' => $heroTitle,
        'body' => $heroBody,
        'imageSrc' => $heroImageSrc,
        'imageAlt' => $heroImageAlt,
        'buttonLabel' => $heroButtonLabel,
        'buttonHref' => $heroButtonHref,
    ];
include DIR_COMPONENTS . '/organisms/hero-image/hero-image.php';
?>
    </header>

    <main>
        <?= $mainContent ?>
    </main>

    <?php
    $props = ['text' => $footerText, 'partners' => $footerPartners];
include DIR_COMPONENTS . '/organisms/footer/footer.php';
?>
</div>
