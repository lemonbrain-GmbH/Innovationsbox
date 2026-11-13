<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $label */
$label = (string) ($props['label'] ?? 'Projektidee einreichen');

/** @var string $href */
$href = (string) ($props['href'] ?? '#');

/** @var string $icon */
$icon = (string) ($props['icon'] ?? '');
?>
<div class="feature-link">
    <a class="feature-button button button--accent button--lg" href="<?= htmlspecialchars($href) ?>">
        <?php if ($icon): ?>
            <span class="me-3"><?= $icon ?></span>
        <?php endif; ?>
        <?= htmlspecialchars($label) ?>
    </a>
</div>
