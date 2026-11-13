<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var bool $displayUserName */
/** @var array<int, mixed> $attributeSets */
/** @var array<int, mixed> $unassignedAttributes */
/** @var \Concrete\Core\Captcha\CaptchaInterface|null $captcha */
/** @var \Concrete\Core\Error\ErrorList\ErrorList|null $error */
/** @var string|null $registerSuccess */
/** @var string[]|string|null $successMsg */
/** @var int|null $rcID */
$form = $form ?? app('helper/form');
$token = $token ?? app('helper/validation/token');
$displayUserName = $displayUserName ?? false;
$attributeSets = $attributeSets ?? [];
$unassignedAttributes = $unassignedAttributes ?? [];
$renderer = $renderer ?? null;

$hasSuccess = isset($registerSuccess);

if ($hasSuccess) {
    switch ($registerSuccess) {
        case 'validate':
            $cardTitle = t('E-Mail prüfen');
            $cardSubtitle = '';
            break;
        case 'pending':
            $cardTitle = t('Registrierung ausstehend');
            $cardSubtitle = '';
            break;
        default:
            $cardTitle = t('Willkommen!');
            $cardSubtitle = '';
    }
} else {
    $cardTitle = t('Konto erstellen');
    $cardSubtitle = t('Registrieren um mitzumachen');
}

ob_start();

if ($hasSuccess) {
    $messages = is_array($successMsg) ? $successMsg : [$successMsg];
    $props = [
        'variant' => 'success',
        'title' => '',
        'message' => implode('<br>', $messages),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';

    echo '<div style="text-align: center; margin-top: var(--space-4);">';
    echo '<a href="' . URL::to('/login') . '" class="auth-link">';
    echo t('Zur Anmeldung');
    echo '</a></div>';
} else {
    // Display errors
    if (isset($error) && is_object($error) && $error->has()) {
        $props = [
            'variant' => 'error',
            'title' => '',
            'message' => implode('<br>', $error->getList()),
            'dismissible' => 'false',
        ];
        include DIR_COMPONENTS . '/molecules/alert/alert.php';
    }
    ?>

    <form class="concrete-login-form" method="post" action="<?= $view->url('/register', 'do_register') ?>">
        <?php $token->output('register.do_register'); ?>

        <?php if ($displayUserName): ?>
            <?php
            $props = [
                'label' => t('Benutzername'),
                'id' => 'uName',
                'name' => 'uName',
                'type' => 'text',
                'value' => $form->getRequestValue('uName') ?? '',
                'required' => 'true',
            ];
            include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
            ?>
        <?php endif; ?>

        <?php
        $props = [
            'label' => t('E-Mail-Adresse'),
            'id' => 'uEmail',
            'name' => 'uEmail',
            'type' => 'email',
            'value' => $form->getRequestValue('uEmail') ?? '',
            'required' => 'true',
        ];
    include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
    ?>

        <?php
    $props = [
        'label' => t('Passwort'),
        'id' => 'uPassword',
        'name' => 'uPassword',
        'required' => 'true',
        'showStrength' => 'true',
        'hint' => t('Mindestens %s Zeichen.', Config::get('concrete.user.password.minimum', 5)),
    ];
    include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
    ?>

        <?php if (Config::get('concrete.user.registration.display_confirm_password_field')): ?>
            <?php
        $props = [
            'label' => t('Passwort bestätigen'),
            'id' => 'uPasswordConfirm',
            'name' => 'uPasswordConfirm',
            'required' => 'true',
        ];
            include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
            ?>
        <?php endif; ?>

        <?php
        // Custom user attributes are collected after registration
        // on first login via the profile completion flow.
    ?>

        <?php
    $captcha = \Concrete\Core\Support\Facade\Facade::getFacadeApplication()->make('captcha');
    if (Config::get('concrete.user.registration.captcha')) {
        echo '<div style="margin-top: var(--space-4);">';
        $captcha->display();
        echo '</div>';
    }
    ?>

        <?php if (isset($rcID) && $rcID): ?>
            <input type="hidden" name="rcID" value="<?= (int) $rcID ?>" />
        <?php endif; ?>

        <div style="margin-top: var(--space-4);">
            <?php
        $props = [
            'label' => t('Konto erstellen'),
            'variant' => 'accent',
            'size' => 'lg',
            'type' => 'submit',
            'fullWidth' => 'true',
        ];
    include DIR_COMPONENTS . '/atoms/button/button.php';
    ?>
        </div>

        <div class="auth-text-sm" style="text-align: center;">
            <?= t('Bereits ein Konto?') ?>
            <a href="<?= URL::to('/login') ?>">
                <?= t('Anmelden') ?>
            </a>
        </div>
    </form>
    <?php
}

$content = ob_get_clean();

$props = [
    'title' => $cardTitle,
    'subtitle' => $cardSubtitle,
    'showLogo' => 'false',
    'content' => $content,
];
include DIR_COMPONENTS . '/molecules/auth-card/auth-card.php';
