<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $illustration */
$illustration = (string) ($props['illustration'] ?? '');

/** @var string $title */
$title = (string) ($props['title'] ?? '');

/** @var string $description */
$description = (string) ($props['description'] ?? '');

/** @var string $ctaLabel */
$ctaLabel = (string) ($props['ctaLabel'] ?? '');

/** @var string $ctaHref */
$ctaHref = (string) ($props['ctaHref'] ?? '#');

/** @var string $size */
$size = (string) ($props['size'] ?? 'md');
?>
<div class="empty-state empty-state--<?= htmlspecialchars($size) ?>">
    <?php if ($illustration): ?>
        <div class="empty-state__illustration" aria-hidden="true"><?= $illustration ?></div>
    <?php endif; ?>
    <h3 class="empty-state__title"><?= htmlspecialchars($title) ?></h3>
    <?php if ($description): ?>
        <p class="empty-state__description"><?= htmlspecialchars($description) ?></p>
    <?php endif; ?>
    <?php if ($ctaLabel):
        $props = [
            'variant' => 'primary',
            'size' => $size === 'sm' ? 'sm' : 'md',
            'label' => $ctaLabel,
            'href' => $ctaHref,
        ];
        include DIR_COMPONENTS . '/atoms/button/button.php';
    endif; ?>
</div>
