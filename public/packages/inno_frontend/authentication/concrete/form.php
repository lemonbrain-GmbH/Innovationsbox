<?php defined('C5_EXECUTE') or die('Access denied.');

$form = Core::make('helper/form');
$token = Core::make('helper/validation/token');

$loggedIn = isset($user) && $user->isRegistered();

if ($loggedIn) {
    $props = [
        'variant' => 'info',
        'title' => '',
        'message' => t('Du bist bereits angemeldet.'),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';
    return;
}
?>

<form class="concrete-login-form" method="post" action="<?= URL::to('/login', 'authenticate', $this->getAuthenticationTypeHandle()) ?>">

    <?php
    $isEmailOnly = Config::get('concrete.user.registration.email_registration');
$props = [
    'label' => $isEmailOnly ? t('E-Mail-Adresse') : t('Benutzername'),
    'id' => 'uName',
    'name' => 'uName',
    'type' => $isEmailOnly ? 'email' : 'text',
    'required' => 'true',
    'placeholder' => '',
];
include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
?>

    <?php
$props = [
    'label' => t('Passwort'),
    'id' => 'uPassword',
    'name' => 'uPassword',
    'required' => 'true',
];
include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
?>

    <?php if (Config::get('concrete.session.remember_me.lifetime') > 0): ?>
        <?php
    $props = [
        'id' => 'uMaintainLogin',
        'name' => 'uMaintainLogin',
        'value' => '1',
        'label' => t('Angemeldet bleiben (14 Tage)'),
        'checked' => 'false',
    ];
        include DIR_COMPONENTS . '/atoms/checkbox/checkbox.php';
        ?>
    <?php endif; ?>

    <?php if (isset($locales) && is_array($locales) && count($locales) > 0): ?>
        <?php
        $props = [
            'label' => t('Sprache'),
            'id' => 'USER_LOCALE',
            'name' => 'USER_LOCALE',
            'type' => 'select',
            'options' => json_encode(array_map(fn($k, $v) => ['value' => $k, 'label' => $v], array_keys($locales), array_values($locales))),
        ];
        include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
        ?>
    <?php endif; ?>

    <?php $token->output('login_' . $this->getAuthenticationTypeHandle()); ?>

    <div>
        <a href="<?= URL::to('/login', 'callback', 'concrete', 'forgot_password') ?>" class="auth-link">
            <?= t('Passwort vergessen?') ?>
        </a>
    </div>

    <div>
        <?php
        $props = [
            'label' => t('Anmelden'),
            'variant' => 'accent',
            'size' => 'lg',
            'type' => 'submit',
            'fullWidth' => 'true',
        ];
include DIR_COMPONENTS . '/atoms/button/button.php';
?>
    </div>

    <?php if (Config::get('concrete.user.registration.enabled')): ?>
        <div class="auth-text-sm" style="text-align: center;">
            <?= t('Noch kein Konto?') ?>
            <a href="<?= URL::to('/register') ?>">
                <?= t('Registrieren') ?>
            </a>
        </div>
    <?php endif; ?>
</form>
