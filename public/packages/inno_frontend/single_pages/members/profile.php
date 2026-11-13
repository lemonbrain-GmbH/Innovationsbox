<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Localization\Service\Date $dh */
/** @var \Concrete\Core\User\UserInfo|null $profile */
/** @var bool $canEdit */
/** @var \Concrete\Core\Attribute\Key\UserKey[] $uaks */
$dh = app('helper/date');
$uaks = \Concrete\Core\Attribute\Key\UserKey::getPublicProfileList();
?>
<div class="member-profile">
    <div class="member-profile__back">
        <?php
        $props = [
            'variant' => 'ghost',
            'size'    => 'sm',
            'label'   => t('Zurück zum Verzeichnis'),
            'href'    => $view->url('/members/directory'),
        ];
include DIR_COMPONENTS . '/atoms/button/button.php';
?>
    </div>

    <div class="member-profile__header">
        <div class="member-profile__avatar">
            <?php
    $avatarSrc = '';
if (isset($profile)) {
    $profileAvatar = $profile->getUserAvatar();
    if ($profileAvatar) {
        $avatarSrc = (string) $profileAvatar->getPath();
    }
}
$profileName = isset($profile) ? ((string) ($profile->getAttribute('display_name') ?: $profile->getUserName())) : '';
$props = [
    'src'   => $avatarSrc,
    'name'  => $profileName,
    'size'  => '2xl',
    'shape' => 'circle',
];
include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
?>
        </div>

        <div class="member-profile__meta">
            <h1 class="member-profile__name"><?= htmlspecialchars(ucfirst($profileName)) ?></h1>

            <?php
$groupName = '';
if (isset($profile)) {
    $groups = $profile->getUserGroupObjects();
    foreach ($groups as $g) {
        if (!$g->isGuestGroup() && !$g->isRegisteredGroup()) {
            $groupName = (string) $g->getGroupName();
            break;
        }
    }
}
if ($groupName !== ''):
    ?>
                <div class="member-profile__role">
                    <?php
        $props = [
            'label'   => $groupName,
            'variant' => 'neutral',
            'size'    => 'sm',
        ];
    include DIR_COMPONENTS . '/atoms/badge/badge.php';
    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($profile) && $profile->getUserDateAdded()): ?>
                <p class="member-profile__joined">
                    <?= t('Mitglied seit %s', $dh->formatDate($profile->getUserDateAdded(), false)) ?>
                </p>
            <?php endif; ?>

            <?php
            $viewerUser = new \Concrete\Core\User\User();
if ($viewerUser->isRegistered() && isset($profile)):
    $profileEmail = (string) $profile->getUserEmail();
    if ($profileEmail !== ''):
        ?>
                <div class="member-profile__email">
                    <?php
                    $props = ['email' => $profileEmail];
        include DIR_COMPONENTS . '/atoms/email-link/email-link.php';
        ?>
                </div>
            <?php
    endif;
endif;
?>

            <div class="member-profile__actions">
                <?php if (isset($canEdit) && $canEdit): ?>
                    <?php
    $props = [
        'label'   => t('Profil bearbeiten'),
        'variant' => 'primary',
        'href'    => $view->url('/account/edit_profile'),
    ];
                    include DIR_COMPONENTS . '/atoms/button/button.php';
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (count($uaks) > 0): ?>
        <div class="member-profile__attributes">
            <h2 class="member-profile__section-title"><?= t('Profilinformationen') ?></h2>
            <div class="member-profile__attribute-list">
                <?php foreach ($uaks as $ua): ?>
                    <?php
                    $attrValue = '';
                    if (isset($profile)) {
                        $attrValue = $profile->getAttribute($ua, 'displaySanitized', 'display');
                    }
                    ?>
                    <div class="member-profile__attribute">
                        <div class="member-profile__attribute-label">
                            <?= htmlspecialchars($ua->getAttributeKeyDisplayName()) ?>
                        </div>
                        <div class="member-profile__attribute-value<?= !$attrValue ? ' member-profile__attribute-value--empty' : '' ?>">
                            <?= $attrValue ?: t('Keine Angabe') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php
    $a = new Area('Main');
$a->setBlockWrapperStart('<div class="member-profile__content-item">');
$a->setBlockWrapperEnd('</div>');
$a->display($c);
?>
</div>
