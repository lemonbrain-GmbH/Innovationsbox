<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

$requestId = (string) ($props['requestId'] ?? '');
$userName = (string) ($props['userName'] ?? '');
$userInitials = (string) ($props['userInitials'] ?? '');
?>
<div class="join-request-card" data-request-id="<?= htmlspecialchars($requestId) ?>">
    <div class="join-request-card__user">
        <?php
        $props = ['src' => '', 'name' => $userName, 'size' => 'sm', 'shape' => 'circle'];
include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
?>
        <span class="join-request-card__name"><?= htmlspecialchars($userName) ?></span>
    </div>
    <div class="join-request-card__actions">
        <button class="unstyled-button join-request-card__btn join-request-card__btn--accept" data-action="accept" aria-label="<?= t('Annehmen') ?>">✓</button>
        <button class="unstyled-button join-request-card__btn join-request-card__btn--reject" data-action="reject" aria-label="<?= t('Ablehnen') ?>">✕</button>
    </div>
</div>
