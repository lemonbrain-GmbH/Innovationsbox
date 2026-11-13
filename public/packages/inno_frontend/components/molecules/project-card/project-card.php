<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

$id = (string) ($props['id'] ?? '');
$title = (string) ($props['title'] ?? '');
$description = (string) ($props['description'] ?? '');
$status = (string) ($props['status'] ?? 'active');
$initiatorName = (string) ($props['initiatorName'] ?? '');
$initiatorInitials = (string) ($props['initiatorInitials'] ?? '');
$memberCount = (int) ($props['memberCount'] ?? 0);
$latestUpdateTitle = (string) ($props['latestUpdateTitle'] ?? '');
$latestUpdateDate = (string) ($props['latestUpdateDate'] ?? '');
$createdAt = (string) ($props['createdAt'] ?? '');
$categoryName = (string) ($props['categoryName'] ?? '');
$initiatorAvatarUrl = (string) ($props['initiatorAvatarUrl'] ?? '');
$imageUrl = (string) ($props['imageUrl'] ?? '');
$showDescription = filter_var($props['showDescription'] ?? false, FILTER_VALIDATE_BOOLEAN);
$isActive = $status === 'active';

$statusLabel = match ($status) {
    'active' => 'Aktiv',
    'completed' => 'Abgeschlossen',
    default => ucfirst($status),
};
$statusVariant = $isActive ? 'success' : 'neutral';
?>
<article class="project-card<?= $isActive ? '' : ' project-card--completed' ?>" aria-labelledby="project-card-title-<?= htmlspecialchars($id) ?>">
    <div class="project-card__image">
        <?php if ($imageUrl !== ''): ?>
            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="" loading="lazy">
        <?php endif; ?>
    </div>

    <div class="project-card__body">
        <div class="project-card__header">
            <?php if ($categoryName !== ''): ?>
                <span class="project-card__badge project-card__badge--category"><?= htmlspecialchars($categoryName) ?></span>
            <?php endif; ?>
            <?php
            $props = ['label' => $statusLabel, 'variant' => $statusVariant, 'size' => 'sm'];
include DIR_COMPONENTS . '/atoms/badge/badge.php';
?>
        </div>

        <h3 class="project-card__title" id="project-card-title-<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($title) ?></h3>

        <?php if ($showDescription && $description !== ''): ?>
            <p class="project-card__description"><?= htmlspecialchars($description) ?></p>
        <?php endif; ?>

        <div class="project-card__meta">
            <div class="project-card__meta-item">
                <svg class="project-card__meta-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                <span><strong><?= $memberCount ?></strong> Mitglieder</span>
            </div>
            <?php if ($createdAt !== ''): ?>
                <span class="project-card__age">Erstellt: <?= htmlspecialchars($createdAt) ?></span>
            <?php endif; ?>
        </div>

        <div class="project-card__footer">
            <div class="project-card__initiator">
                <?php
    $props = ['src' => $initiatorAvatarUrl, 'name' => $initiatorName, 'size' => 'sm', 'shape' => 'circle'];
include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
?>
                <div class="project-card__initiator-info">
                    <span class="project-card__initiator-name"><?= htmlspecialchars($initiatorName) ?></span>
                    <span class="project-card__initiator-role">Initiator·in</span>
                </div>
            </div>
            <?php if ($isActive): ?>
                <a href="/projektraum/view/<?= htmlspecialchars($id) ?>" class="project-card__btn button button--accent button--lg">Mitmachen</a>
            <?php else: ?>
                <a href="/projektraum/view/<?= htmlspecialchars($id) ?>" class="project-card__btn button button--muted button--lg">Mehr erfahren</a>
            <?php endif; ?>
        </div>
    </div>
</article>
