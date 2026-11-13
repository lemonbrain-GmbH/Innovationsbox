<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $svg */
$svg = (string) ($props['svg'] ?? '');
/** @var string $label */
$label = (string) ($props['label'] ?? '');
/** @var string $size */
$size = (string) ($props['size'] ?? 'md');

$classes = ['icon', 'icon--' . $size];
?>
<span class="<?= implode(' ', $classes) ?>" <?= $label ? 'role="img" aria-label="' . htmlspecialchars($label) . '"' : 'aria-hidden="true"' ?>>
    <?= $svg ?>
</span>
