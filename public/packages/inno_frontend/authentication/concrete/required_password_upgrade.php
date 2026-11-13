<?php


defined('C5_EXECUTE') or die('Access denied.');

/**
 * @var Concrete\Core\Error\ErrorList\ErrorList|null $callbackError (may be not set)
 * @var Concrete\Core\Authentication\AuthenticationType $authType
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var string $intro_msg
 */

if (isset($callbackError) && $callbackError->has()) {
    $props = [
        'variant' => 'error',
        'title' => '',
        'message' => implode('<br>', $callbackError->getList()),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';
}
?>

<form method="post"
      action="<?= URL::to('/login', 'callback', $authType->getAuthenticationTypeHandle(), 'required_password_upgrade') ?>">
    <?php $token->output(); ?>

    <p style="font-size: var(--text-sm); color: var(--color-text-secondary); margin-bottom: var(--space-4);">
        <?= nl2br(h($intro_msg)) ?>
    </p>

    <div style="margin-top: var(--space-4);">
        <?php
        $props = [
            'label' => t('Passwort zurücksetzen und senden'),
            'variant' => 'accent',
            'size' => 'lg',
            'type' => 'submit',
            'fullWidth' => 'true',
        ];
include DIR_COMPONENTS . '/atoms/button/button.php';
?>
    </div>
</form>
