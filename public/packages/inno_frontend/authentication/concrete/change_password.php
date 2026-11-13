<?php defined('C5_EXECUTE') or die('Access denied.');

$_error = [];
if (isset($error)) {
    if ($error instanceof Exception) {
        $_error[] = $error->getMessage();
    } elseif ($error instanceof \Concrete\Core\Error\ErrorList\ErrorList) {
        if ($error->has()) {
            $_error = $error->getList();
        }
    } elseif (is_array($error)) {
        $_error = $error;
    }
}

if (!empty($_error)) {
    $props = [
        'variant' => 'error',
        'title' => '',
        'message' => implode('<br>', array_map('htmlspecialchars', $_error)),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';
}
?>

<p style="font-size: var(--text-sm); color: var(--color-text-secondary); margin-bottom: var(--space-4);">
    <?= t('Gib dein neues Passwort ein.') ?>
</p>

<form class="concrete-login-form" method="post" action="<?= URL::to('/login', 'callback', $authType->getAuthenticationTypeHandle(), 'change_password', $uHash) ?>">

        <?php
    $props = [
        'label' => t('Neues Passwort'),
        'id' => 'uPassword',
        'name' => 'uPassword',
        'required' => 'true',
        'showStrength' => 'true',
        'hint' => t('Minimum %s characters.', Config::get('concrete.user.password.minimum', 5)),
    ];
include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
?>

        <?php if (Config::get('concrete.user.registration.display_confirm_password_field')): ?>
            <?php
    $props = [
        'label' => t('Neues Passwort bestätigen'),
        'id' => 'uPasswordConfirm',
        'name' => 'uPasswordConfirm',
        'required' => 'true',
    ];
            include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
            ?>
        <?php endif; ?>

    <div style="margin-top: var(--space-4);">
        <?php
    $props = [
        'label' => t('Passwort ändern'),
        'variant' => 'accent',
        'size' => 'lg',
        'type' => 'submit',
        'fullWidth' => 'true',
    ];
include DIR_COMPONENTS . '/atoms/button/button.php';
?>
    </div>
</form>
