<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $text */
$text = (string) ($props['text'] ?? 'Label');
/** @var string $for */
$for = (string) ($props['for'] ?? '');
/** @var bool $required */
$required = ($props['required'] ?? 'false') === 'true';
/** @var bool $disabled */
$disabled = ($props['disabled'] ?? 'false') === 'true';

$classes = ['label'];
if ($required) {
    $classes[] = 'label--required';
}
if ($disabled) {
    $classes[] = 'label--disabled';
}
?>
<label<?= $for ? ' for="' . htmlspecialchars($for) . '"' : '' ?> class="<?= implode(' ', $classes) ?>">
    <?= htmlspecialchars($text) ?><?php if ($required): ?><span class="label__required" aria-hidden="true">*</span><?php endif; ?>
</label>
