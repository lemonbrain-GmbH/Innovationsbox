<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $title */
$title = (string) ($props['title'] ?? 'Projektidee');
/** @var string $href */
$href = (string) ($props['href'] ?? '#');
/** @var string $target */
$target = (string) ($props['target'] ?? '_self');
/** @var string $imageSrc */
$imageSrc = (string) ($props['imageSrc'] ?? '');
/** @var string $imageAlt */
$imageAlt = (string) ($props['imageAlt'] ?? '');
?>
<a href="<?= htmlspecialchars($href) ?>" target="<?= htmlspecialchars($target) ?>" class="page-link">
    <?php if ($imageSrc): ?>
        <?php
        $props = ['src' => $imageSrc, 'alt' => $imageAlt, 'ratio' => '3/2', 'fit' => 'cover', 'rounded' => 'true', 'width' => '100%'];
        include DIR_COMPONENTS . '/atoms/image/image.php';
        ?>
    <?php endif; ?>
    <span class="page-link-text">
        <?= htmlspecialchars($title) ?>
    </span>
</a>
