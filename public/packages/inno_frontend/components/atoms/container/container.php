<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'default');
/** @var bool $spaced */
$spaced = ($props['spaced'] ?? 'false') === 'true';
/** @var string $content */
$content = (string) ($props['content'] ?? '');

if ($variant === 'narrow') {
    $classes = 'lb-container-narrow';
} else {
    $classes = 'lb-container';
    if ($variant !== 'default') {
        $classes .= ' ' . htmlspecialchars($variant);
    }
}

if ($spaced) {
    $classes .= ' spaced-block';
}
?>
<div class="<?= $classes ?>">
    <?= $content ?>
</div>
