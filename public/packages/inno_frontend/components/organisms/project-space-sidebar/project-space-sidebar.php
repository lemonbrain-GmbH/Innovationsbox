<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

$initiatorName = (string) ($props['initiatorName'] ?? '');
$initiatorInitials = (string) ($props['initiatorInitials'] ?? '');
$members = (array) ($props['members'] ?? []);
$requests = (array) ($props['requests'] ?? []);
$canManage = ($props['canManage'] ?? 'false') === 'true';
$createdAt = (string) ($props['createdAt'] ?? '');
$updateCount = (int) ($props['updateCount'] ?? 0);
?>
<aside class="project-space-sidebar" aria-label="<?= t('Projektteam und Statistik') ?>">
    <h3 class="project-space-sidebar__section-title"><?= t('Team') ?></h3>

    <div class="project-space-sidebar__initiator">
        <span class="project-space-sidebar__label"><?= t('Initiatorin') ?></span>
        <?php
        $props = [
            'userName' => $initiatorName,
            'userInitials' => $initiatorInitials,
            'avatarUrl' => '',
            'profileUrl' => '',
            'role' => 'Initiatorin',
            'memberSince' => '',
            'expertise' => '',
            'email' => '',
        ];
include DIR_COMPONENTS . '/molecules/member-card/member-card.php';
?>
    </div>

    <?php if (!empty($members)): ?>
        <div class="project-space-sidebar__members">
            <span class="project-space-sidebar__label"><?= t('Entwickler') ?> (<?= count($members) ?>)</span>
            <?php foreach ($members as $member): ?>
                <div class="project-space-sidebar__member-row">
                    <?php
            $props = ['src' => '', 'name' => $member['name'] ?? '', 'size' => 'sm', 'shape' => 'circle'];
                include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
                ?>
                    <span class="project-space-sidebar__member-name"><?= htmlspecialchars($member['name'] ?? '') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($canManage && !empty($requests)): ?>
        <div class="project-space-sidebar__requests">
            <span class="project-space-sidebar__label"><?= t('Anfragen') ?> (<?= count($requests) ?>)</span>
            <?php foreach ($requests as $req): ?>
                <?php
                $props = [
                    'requestId' => $req['id'] ?? '',
                    'userName' => $req['userName'] ?? '',
                    'userInitials' => $req['userInitials'] ?? '',
                ];
                include DIR_COMPONENTS . '/molecules/join-request-card/join-request-card.php';
                ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="project-space-sidebar__stats">
        <span class="project-space-sidebar__label"><?= t('Statistik') ?></span>
        <div class="project-space-sidebar__stat-row">
            <span><?= t('Erstellt') ?></span>
            <span><?= htmlspecialchars($createdAt) ?></span>
        </div>
        <div class="project-space-sidebar__stat-row">
            <span><?= t('Updates') ?></span>
            <span><?= $updateCount ?></span>
        </div>
    </div>
</aside>
