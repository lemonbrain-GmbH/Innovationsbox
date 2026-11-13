<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $src */
$src = (string) ($props['src'] ?? '');
/** @var string $alt */
$alt = (string) ($props['alt'] ?? '');
/** @var string $name */
$name = (string) ($props['name'] ?? '');
/** @var string $size */
$size = (string) ($props['size'] ?? 'md');
/** @var string $shape */
$shape = (string) ($props['shape'] ?? 'circle');

// Derive up to 2 initials from $name
$initials = '';
if ($name) {
    $words = preg_split('/\s+/', trim($name));
    $first = mb_substr($words[0] ?? '', 0, 1);
    $second = count($words) > 1 ? mb_substr(end($words), 0, 1) : '';
    $initials = mb_strtoupper($first . $second);
}

$classes = ['avatar', 'avatar--' . $size, 'avatar--' . $shape];
?>
<?php if ($src): ?>
<span class="<?= implode(' ', $classes) ?>">
    <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($alt ?: $name) ?>" class="avatar__img">
</span>
<?php else: ?>
<span class="<?= implode(' ', $classes) ?>" <?= $name ? 'aria-label="' . htmlspecialchars($name) . '"' : 'aria-hidden="true"' ?>>
    <span class="avatar__initials"><?= htmlspecialchars($initials) ?></span>
</span>
<?php endif; ?>
