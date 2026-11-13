<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Page\Page $c */
?>

<?= $this->inc('elements/header_top.php'); ?>

<?php
// Resolve auth state from the current CMS user
$u = new \Concrete\Core\User\User();

/** @var string $authState */
$authState = 'logged-out';
/** @var string $userName */
$userName = '';
/** @var string $userEmail */
$userEmail = '';
/** @var string $userInitials */
$userInitials = '';
/** @var string $avatarUrl */
$avatarUrl = '';
/** @var string $editProfileUrl */
$editProfileUrl = '/account/edit_profile';
/** @var string $logoutUrl */
$logoutUrl = '/login/do_logout';

if ($u->isRegistered()) {
    $authState = 'logged-in';

    /** @var \Concrete\Core\User\UserInfo|null $ui */
    $ui = \Concrete\Core\User\UserInfo::getByID((int) $u->getUserID());

    if ($ui !== null) {
        $userName = (string) ($ui->getAttribute('display_name') ?: $ui->getUserName());
        $userEmail = (string) $ui->getUserEmail();

        // Derive initials from name (up to two uppercase letters)
        $nameParts = array_filter(explode(' ', $userName));
        $userInitials = '';
        foreach (array_slice($nameParts, 0, 2) as $part) {
            $userInitials .= mb_strtoupper(mb_substr((string) $part, 0, 1));
        }

        if ($ui->hasAvatar()) {
            $avatarUrl = (string) $ui->getUserAvatar()->getPath();
        }
    }

    /** @var \Concrete\Core\Validation\CSRF\Token $tokenHelper */
    $tokenHelper = app('helper/validation/token');
    $csrfToken = (string) $tokenHelper->generate('do_logout');
    $logoutUrl = (string) \Concrete\Core\Support\Facade\Url::to('/login/do_logout') . '?ccm_token=' . $csrfToken;

    // Query notification badge counts
    $badgeCounts = ['moderationReviews' => 0, 'expertRequests' => 0, 'joinRequests' => 0, 'total' => 0];
    try {
        $countsQuery = app(\Concrete\Package\InnoModeration\Adapter\Out\Persistence\UserCountsQuery::class);

        $isMod = false;
        $isExp = false;
        if ($u->isSuperUser()) {
            $isMod = true;
        } else {
            foreach ($u->getUserGroups() as $gID) {
                $g = \Concrete\Core\User\Group\Group::getByID($gID);
                if ($g) {
                    $n = mb_strtolower($g->getGroupName());
                    if ($n === 'moderator' || $n === 'administrator' || $n === 'administratoren' || $n === 'admins') {
                        $isMod = true;
                    }
                    if ($n === 'experte') {
                        $isExp = true;
                    }
                }
            }
        }

        $badgeCounts = $countsQuery->getCounts((int) $u->getUserID(), $isMod, $isExp);
    } catch (\Throwable $e) {
        // Silently fail — badge counts are non-critical
    }
}
?>

<header class="page-header">
    <a href="#main-content-area" class="skip-link visually-hidden"><?= t('Zum Hauptinhalt springen') ?></a>

    <div class="lb-container">
        <?php
        $a = new GlobalArea('Header Nav');
$a->display($c);
?>
    </div>

    <?php
    $a = new GlobalArea('Unsupported Browser');
$a->display($c);
?>

    <div class="page-header__auth" data-header-auth>
        <?php if ($authState === 'logged-in') : ?>
            <?php
            $props = [
                'userName' => $userName,
                'userEmail' => $userEmail,
                'userInitials' => $userInitials,
                'avatarUrl' => $avatarUrl,
                'editProfileUrl' => $editProfileUrl,
                'logoutUrl' => $logoutUrl,
                'moderationCount' => $badgeCounts['moderationReviews'],
                'expertCount' => $badgeCounts['expertRequests'],
                'joinCount' => $badgeCounts['joinRequests'],
                'totalCount' => $badgeCounts['total'],
            ];
            include DIR_COMPONENTS . '/molecules/user-menu/user-menu.php';
            ?>
        <?php else : ?>
            <a href="/login" class="button button--ghost button--sm">
                <?= t('Anmelden') ?>
            </a>
            <a href="/register" class="button button--primary button--sm">
                <?= t('Registrieren') ?>
            </a>
        <?php endif; ?>
    </div>
</header>
