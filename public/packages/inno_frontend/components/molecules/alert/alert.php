<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'info');

/** @var string $title */
$title = (string) ($props['title'] ?? '');

/** @var string $message */
$message = (string) ($props['message'] ?? '');

/** @var bool $dismissible */
$dismissible = ($props['dismissible'] ?? 'false') === 'true';

/** @var bool $showIcon */
$showIcon = ($props['icon'] ?? 'true') === 'true';

$icons = [
    'info' => '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
    'success' => '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
    'warning' => '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
    'error' => '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
];

$role = $variant === 'error' ? 'alert' : 'status';
?>
<div class="alert alert--<?= htmlspecialchars($variant) ?>" role="<?= $role ?>">
    <?php if ($showIcon): ?>
        <span class="alert__icon" aria-hidden="true"><?= $icons[$variant] ?? '' ?></span>
    <?php endif; ?>
    <div class="alert__content">
        <?php if ($title): ?>
            <h4 class="alert__title"><?= htmlspecialchars($title) ?></h4>
        <?php endif; ?>
        <p class="alert__message"><?= $message ?></p>
    </div>
    <?php if ($dismissible): ?>
        <button type="button" class="alert__dismiss" aria-label="Schliessen" data-alert-dismiss>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    <?php endif; ?>
</div>
