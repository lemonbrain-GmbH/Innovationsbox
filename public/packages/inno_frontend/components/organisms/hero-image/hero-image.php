<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $title */
$title = (string) ($props['title'] ?? '');
/** @var string $body */
$body = (string) ($props['body'] ?? '');
/** @var string $imageSrc */
$imageSrc = (string) ($props['imageSrc'] ?? '');
/** @var string $imageAlt */
$imageAlt = (string) ($props['imageAlt'] ?? '');
/** @var string $buttonLabel */
$buttonLabel = (string) ($props['buttonLabel'] ?? '');
/** @var string $buttonHref */
$buttonHref = (string) ($props['buttonHref'] ?? '#');
?>
<div class="hero-image-wrapper" role="banner">
    <?php
    $props = ['src' => $imageSrc, 'alt' => $imageAlt, 'fit' => 'cover', 'width' => '100vw'];
include DIR_COMPONENTS . '/atoms/image/image.php';
?>

    <div class="hero-image-text">
        <?php if ($title): ?>
            <?php
        $props = ['level' => '1', 'text' => $title];
            include DIR_COMPONENTS . '/atoms/heading/heading.php';
            ?>
        <?php endif; ?>
        <?php if ($body): ?>
            <?= $body ?>
        <?php endif; ?>
        <?php if ($buttonLabel): ?>
            <?php
            $props = ['label' => $buttonLabel, 'href' => $buttonHref];
            include DIR_COMPONENTS . '/molecules/feature-button/feature-button.php';
            ?>
        <?php endif; ?>
    </div>
</div>
