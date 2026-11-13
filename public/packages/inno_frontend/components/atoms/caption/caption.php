<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $text */
$text = (string) ($props['text'] ?? 'Caption');
/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'default');

$classes = ['caption'];
if (in_array($variant, ['muted', 'error', 'success'], true)) {
    $classes[] = 'caption--' . $variant;
}
?>
<span class="<?= implode(' ', $classes) ?>"><?= htmlspecialchars($text) ?></span>
