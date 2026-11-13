<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'body');
/** @var string $text */
$text = (string) ($props['text'] ?? '');
/** @var string $id */
$id = (string) ($props['id'] ?? '');

$classes = [];
if ($variant === 'lead') {
    $classes[] = 'lead';
}
if ($variant === 'small') {
    $classes[] = 'small';
}
if ($variant === 'muted') {
    $classes[] = 'p--muted';
}
if ($variant === 'inverse') {
    $classes[] = 'p--inverse';
}

$classAttr = $classes ? ' class="' . implode(' ', $classes) . '"' : '';
$idAttr = $id ? ' id="' . htmlspecialchars($id) . '"' : '';
?>
<p<?= $idAttr ?><?= $classAttr ?>><?= htmlspecialchars($text) ?></p>
