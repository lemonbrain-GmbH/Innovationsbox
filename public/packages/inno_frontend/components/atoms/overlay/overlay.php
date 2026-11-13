<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'light');
/** @var string $content */
$content = (string) ($props['content'] ?? '');
?>
<div class="overlay overlay--<?= htmlspecialchars($variant) ?>" aria-hidden="true">
    <?= $content ?>
</div>
