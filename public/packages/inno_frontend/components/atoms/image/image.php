<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $src */
$src = (string) ($props['src'] ?? '');
/** @var string $alt */
$alt = (string) ($props['alt'] ?? '');
/** @var string $ratio */
$ratio = (string) ($props['ratio'] ?? 'auto');
/** @var string $fit */
$fit = (string) ($props['fit'] ?? 'cover');
/** @var bool $rounded */
$rounded = ($props['rounded'] ?? 'false') === 'true';
/** @var string $width */
$width = (string) ($props['width'] ?? '100%');

$classes = 'image';
if ($rounded) {
    $classes .= ' image--rounded';
}

$style = "width: {$width};";
if ($ratio !== 'auto') {
    $style .= " aspect-ratio: {$ratio};";
}
$style .= " object-fit: {$fit};";
?>
<img
    class="<?= $classes ?>"
    src="<?= htmlspecialchars($src) ?>"
    alt="<?= htmlspecialchars($alt) ?>"
    style="<?= htmlspecialchars($style) ?>"
    loading="lazy"
>
