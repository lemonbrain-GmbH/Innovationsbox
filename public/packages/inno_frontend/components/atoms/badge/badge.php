<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $label */
$label = (string) ($props['label'] ?? 'Badge');
/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'neutral');
/** @var string $size */
$size = (string) ($props['size'] ?? 'md');
/** @var string $shape */
$shape = (string) ($props['shape'] ?? 'rounded');
/** @var string $icon */
$icon = (string) ($props['icon'] ?? '');

$classes = ['badge', 'badge--' . $variant, 'badge--' . $size, 'badge--' . $shape];
?>
<span class="<?= implode(' ', $classes) ?>">
    <?php if ($icon): ?><span class="badge__icon" aria-hidden="true"><?= $icon ?></span><?php endif; ?>
    <?= htmlspecialchars($label) ?>
</span>
