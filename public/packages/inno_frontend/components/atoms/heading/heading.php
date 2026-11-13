<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var int $level */
$level = (int) ($props['level'] ?? 2);
$level = max(1, min(6, $level));
/** @var string $text */
$text = (string) ($props['text'] ?? 'Überschrift');
/** @var string $id */
$id = (string) ($props['id'] ?? '');
/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'default');
$tag = 'h' . $level;

$classes = [];
if ($variant === 'muted') {
    $classes[] = 'heading--muted';
}
if ($variant === 'inverse') {
    $classes[] = 'heading--inverse';
}
$classAttr = $classes ? ' class="' . implode(' ', $classes) . '"' : '';
$idAttr = $id ? ' id="' . htmlspecialchars($id) . '"' : '';
?>
<<?= $tag ?><?= $idAttr ?><?= $classAttr ?>><?= htmlspecialchars($text) ?></<?= $tag ?>>
