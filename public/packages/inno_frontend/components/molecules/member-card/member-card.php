<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $userName */
$userName = (string) ($props['userName'] ?? '');
/** @var string $userInitials */
$userInitials = (string) ($props['userInitials'] ?? '');
/** @var string $avatarUrl */
$avatarUrl = (string) ($props['avatarUrl'] ?? '');
/** @var string $profileUrl */
$profileUrl = (string) ($props['profileUrl'] ?? '');
/** @var string $memberSince */
$memberSince = (string) ($props['memberSince'] ?? '');
/** @var string $role */
$role = (string) ($props['role'] ?? '');
/** @var string $expertise */
$expertise = (string) ($props['expertise'] ?? '');
/** @var string $email */
$email = (string) ($props['email'] ?? '');
$isLinked = $profileUrl !== '';

$tagName = $isLinked ? 'a' : 'div';
$linkAttr = $isLinked ? ' href="' . htmlspecialchars($profileUrl) . '"' : '';
?>
<<?= $tagName ?><?= $linkAttr ?> class="member-card" aria-label="<?= htmlspecialchars($userName) ?>">
    <div class="member-card__avatar">
        <?php
        $props = [
            'src' => $avatarUrl,
            'name' => $userName,
            'size' => 'lg',
            'shape' => 'circle',
        ];
include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
?>
    </div>

    <div class="member-card__info">
        <?php if ($role !== ''): ?>
            <?php
            $props = [
                'label' => $role,
                'variant' => 'neutral',
                'size' => 'sm',
            ];
            include DIR_COMPONENTS . '/atoms/badge/badge.php';
            ?>
        <?php endif; ?>

       <span class="member-card__name"><?= htmlspecialchars($userName) ?></span>

        <?php if ($expertise !== ''): ?>
            <span class="member-card__expertise"><?= htmlspecialchars($expertise) ?></span>
        <?php endif; ?>

        <?php if ($email !== ''): ?>
            <span class="member-card__email"><?= htmlspecialchars($email) ?></span>
        <?php endif; ?>

        <?php if ($memberSince !== ''): ?>
            <span class="member-card__date">Mitglied seit <?= htmlspecialchars($memberSince) ?></span>
        <?php endif; ?>
    </div>
</<?= $tagName ?>>
