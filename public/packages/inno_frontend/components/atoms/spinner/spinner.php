<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $size */
$size = (string) ($props['size'] ?? 'md');
/** @var string $label */
$label = (string) ($props['label'] ?? 'Wird geladen…');

$classes = ['spinner', 'spinner--' . $size];
?>
<span class="<?= implode(' ', $classes) ?>" role="status" aria-label="<?= htmlspecialchars($label) ?>">
    <svg viewBox="0 0 24 24" class="spinner__svg" aria-hidden="true">
        <circle cx="12" cy="12" r="10" class="spinner__track"></circle>
        <circle cx="12" cy="12" r="10" class="spinner__arc"></circle>
    </svg>
</span>
