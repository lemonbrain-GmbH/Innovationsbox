<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Error\ErrorList\ErrorList|null $error */
/** @var bool $hasAvatar */
/** @var int $avatarWidth */
/** @var int|null $width */
/** @var \Concrete\Core\User\UserInfo|null $profile */
/** @var \Concrete\Core\File\Image\Thumbnail\Type\Version|null $avatarCropperInstance */
$hasAvatar = isset($hasAvatar) && $hasAvatar;
$avatarWidth = $width ?? 200;
?>
<div class="edit-profile" data-view="account">
    <h1 class="edit-profile__title"><?= t('Profilbild') ?></h1>

    <?php if (isset($error) && is_object($error) && $error->has()): ?>
        <div class="edit-profile__feedback">
            <?php
        $props = [
            'variant' => 'error',
            'title' => '',
            'message' => implode('<br>', $error->getList()),
            'dismissible' => 'false',
        ];
        include DIR_COMPONENTS . '/molecules/alert/alert.php';
        ?>
        </div>
    <?php endif; ?>

    <div class="avatar-page__layout">
        <?php if ($hasAvatar && isset($profile)): ?>
            <div class="avatar-page__left">
                <img src="<?= h($profile->getUserAvatar()->getPath()) ?>"
                     alt="<?= h($profile->getAttribute('display_name') ?: $profile->getUserName()) ?>"
                     class="avatar-page__image">
                <form method="post" action="<?= $view->action('delete') ?>" class="avatar-page__delete">
                    <?php app('helper/validation/token')->output('delete_avatar'); ?>
                    <?php
                    $props = [
                        'label' => t('Entfernen'),
                        'variant' => 'danger',
                        'type' => 'submit',
                        'size' => 'sm',
                    ];
            include DIR_COMPONENTS . '/atoms/button/button.php';
            ?>
                </form>
            </div>
        <?php endif; ?>

        <?php if (isset($avatarCropperInstance)): ?>
            <div class="avatar-page__cropper">
                <?= $avatarCropperInstance->getTag() ?>
            </div>
        <?php endif; ?>
    </div>
</div>
