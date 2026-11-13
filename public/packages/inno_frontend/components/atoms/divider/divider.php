<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $orientation */
$orientation = (string) ($props['orientation'] ?? 'horizontal');
/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'solid');
/** @var string $label */
$label = (string) ($props['label'] ?? '');

$classes = ['divider', 'divider--' . $orientation, 'divider--' . $variant];
if ($label) {
    $classes[] = 'divider--labeled';
}
?>
<?php if ($label): ?>
<div class="<?= implode(' ', $classes) ?>" role="separator" aria-orientation="<?= htmlspecialchars($orientation) ?>">
    <span class="divider__label"><?= htmlspecialchars($label) ?></span>
</div>
<?php elseif ($orientation === 'vertical'): ?>
<div class="<?= implode(' ', $classes) ?>" role="separator" aria-orientation="vertical"></div>
<?php else: ?>
<hr class="<?= implode(' ', $classes) ?>">
<?php endif; ?>
