<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $title */
$title = (string) ($props['title'] ?? 'Anmelden');

/** @var string $subtitle */
$subtitle = (string) ($props['subtitle'] ?? '');

/** @var bool $showLogo */
$showLogo = ($props['showLogo'] ?? 'true') === 'true';

/** @var string $content */
$content = (string) ($props['content'] ?? '');
?>
<div class="auth-card">
    <div class="auth-card__background" aria-hidden="true"></div>
    <div class="auth-card__card">
        <?php if ($showLogo): ?>
            <div class="auth-card__logo">
                <div class="auth-card__logo-mark">I</div>
                <span class="auth-card__logo-text">Innovationsbox</span>
            </div>
        <?php endif; ?>

        <div class="auth-card__header">
            <h1 class="auth-card__title"><?= htmlspecialchars($title) ?></h1>
            <?php if ($subtitle !== ''): ?>
                <p class="auth-card__subtitle"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </div>

        <div class="auth-card__content">
            <?= $content ?>
        </div>
    </div>
</div>
