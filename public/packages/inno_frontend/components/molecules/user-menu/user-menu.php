<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $userName */
$userName = (string) ($props['userName'] ?? '');
/** @var string $userEmail */
$userEmail = (string) ($props['userEmail'] ?? '');
/** @var string $userInitials */
$userInitials = (string) ($props['userInitials'] ?? '');
/** @var string $avatarUrl */
$avatarUrl = (string) ($props['avatarUrl'] ?? '');
/** @var string $editProfileUrl */
$editProfileUrl = (string) ($props['editProfileUrl'] ?? '/account/edit_profile');
/** @var string $logoutUrl */
$logoutUrl = (string) ($props['logoutUrl'] ?? '/login/do_logout');
/** @var bool $isOpen */
$isOpen = ($props['isOpen'] ?? 'false') === 'true';
/** @var int $moderationCount */
$moderationCount = (int) ($props['moderationCount'] ?? 0);
/** @var int $expertCount */
$expertCount = (int) ($props['expertCount'] ?? 0);
/** @var int $joinCount */
$joinCount = (int) ($props['joinCount'] ?? 0);
/** @var int $totalCount */
$totalCount = (int) ($props['totalCount'] ?? 0);

$menuId = 'user-menu-dropdown';
?>
<div class="user-menu <?= $isOpen ? 'user-menu--open' : '' ?>" data-user-menu>
    <button
        type="button"
        class="user-menu__trigger"
        aria-haspopup="true"
        aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
        aria-controls="<?= htmlspecialchars($menuId) ?>"
        data-user-menu-trigger
        data-badge-total="<?= $totalCount ?>"
    >
        <?php
        $props = [
            'src' => $avatarUrl,
            'name' => $userName,
            'size' => 'sm',
            'shape' => 'circle',
        ];
include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
?>
        <?php if ($totalCount > 0): ?>
            <span class="user-menu__badge" aria-label="<?= $totalCount ?> offene Aufgaben"><?= $totalCount ?></span>
        <?php endif; ?>
    </button>

    <div
        id="<?= htmlspecialchars($menuId) ?>"
        class="user-menu__dropdown"
        role="menu"
        <?= $isOpen ? '' : 'hidden' ?>
    >
        <div class="user-menu__info" role="none">
            <?php
    $props = [
        'src' => $avatarUrl,
        'name' => $userName,
        'size' => 'md',
        'shape' => 'circle',
    ];
include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
?>
            <div class="user-menu__details">
                <span class="user-menu__name"><?= htmlspecialchars($userName) ?></span>
                <span class="user-menu__email"><?= htmlspecialchars($userEmail) ?></span>
            </div>
        </div>

        <hr class="user-menu__divider" role="none">

        <a href="<?= htmlspecialchars($editProfileUrl) ?>" class="user-menu__item" role="menuitem">
            Profil bearbeiten
        </a>

        <a href="/account/avatar" class="user-menu__item" role="menuitem">
            Profilbild ändern
        </a>

        <hr class="user-menu__divider" role="none">

        <a href="/projektidee/meine" class="user-menu__item" role="menuitem">
            Meine Projektideen
        </a>

        <a href="/innovationen?mine=true" class="user-menu__item" role="menuitem">
            Meine Innovationen
            <?php if ($joinCount > 0): ?>
                <span class="user-menu__item-badge"><?= $joinCount ?></span>
            <?php endif; ?>
        </a>

        <?php
        $isModerator = false;
$currentUser = new \Concrete\Core\User\User();
if ($currentUser->isRegistered()) {
    // Check if user is admin (superuser) or in moderator/administrator groups
    if ($currentUser->isSuperUser()) {
        $isModerator = true;
    } else {
        foreach ($currentUser->getUserGroups() as $gID) {
            $g = \Concrete\Core\User\Group\Group::getByID($gID);
            if ($g) {
                $n = mb_strtolower($g->getGroupName());
                if ($n === 'moderator' || $n === 'administrator' || $n === 'administratoren' || $n === 'admins') {
                    $isModerator = true;
                    break;
                }
            }
        }
    }
}
?>
        <?php if ($isModerator): ?>
            <a href="/moderation" class="user-menu__item" role="menuitem">
                Moderation
                <?php if ($moderationCount > 0): ?>
                    <span class="user-menu__item-badge"><?= $moderationCount ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php
        $isExperte = false;
if ($currentUser->isRegistered()) {
    foreach ($currentUser->getUserGroups() as $gID) {
        $g = \Concrete\Core\User\Group\Group::getByID($gID);
        if ($g && mb_strtolower($g->getGroupName()) === 'experte') {
            $isExperte = true;
            break;
        }
    }
}
?>
        <?php if ($isExperte): ?>
            <a href="/expertanfragen" class="user-menu__item" role="menuitem">
                Meine Anfragen
                <?php if ($expertCount > 0): ?>
                    <span class="user-menu__item-badge"><?= $expertCount ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <hr class="user-menu__divider" role="none">

        <a href="<?= htmlspecialchars($logoutUrl) ?>" class="user-menu__item user-menu__item--danger" role="menuitem">
            Abmelden
        </a>
    </div>
</div>
