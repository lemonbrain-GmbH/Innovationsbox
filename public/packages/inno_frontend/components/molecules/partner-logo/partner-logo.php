<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $name */
$name = (string) ($props['name'] ?? 'Partner');
/** @var string $href */
$href = (string) ($props['href'] ?? '#');
/** @var string $imageSrc */
$imageSrc = (string) ($props['imageSrc'] ?? '');
?>
<a href="<?= htmlspecialchars($href) ?>" target="_blank" rel="noopener noreferrer" class="logo-link">
    <img src="<?= htmlspecialchars($imageSrc) ?>" alt="<?= htmlspecialchars($name) ?>">
</a>
