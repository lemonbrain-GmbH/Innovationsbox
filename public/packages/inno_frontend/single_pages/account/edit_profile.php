<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var \Concrete\Core\User\UserInfo|null $profile */
/** @var \Concrete\Core\Error\ErrorList\ErrorList|null $error */
/** @var bool $success */
/** @var bool $displayTimezone */
/** @var array<string, string> $timezones */
/** @var array<string, string> $locales */
/** @var \Concrete\Core\Attribute\View\FormView $profileFormRenderer */
/** @var array<string, mixed[]> $attributeSets */
/** @var mixed[] $unassignedAttributes */
$form = app('helper/form');
$token = app('helper/validation/token');

$showCompletionBanner = isset($_GET['complete']) && $_GET['complete'] === '1';
?>
<div class="edit-profile">
    <h1 class="edit-profile__title"><?= t('Profil bearbeiten') ?></h1>

    <?php if ($showCompletionBanner): ?>
        <div class="edit-profile__banner">
            <?php
            $props = [
                'variant' => 'info',
                'title' => t('Profil unvollständig'),
                'message' => t('Bitte vervollständige dein Profil, bevor du fortfährst.'),
                'dismissible' => 'false',
            ];
        include DIR_COMPONENTS . '/molecules/alert/alert.php';
        ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success) && $success): ?>
        <div class="edit-profile__feedback">
            <?php
        $props = [
            'variant' => 'success',
            'title' => '',
            'message' => t('Dein Profil wurde erfolgreich aktualisiert.'),
            'dismissible' => 'false',
        ];
        include DIR_COMPONENTS . '/molecules/alert/alert.php';
        ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= $view->action('save') ?>">
        <?php $token->output('profile_edit'); ?>

        <section class="edit-profile__section">
            <h2 class="edit-profile__section-title"><?= t('Persönliche Angaben') ?></h2>

            <?php // Username is kept as a hidden field (required by CMS) but not shown to users?>
            <input type="hidden" name="uName" value="<?= isset($profile) ? h($profile->getUserName()) : h($form->getRequestValue('uName') ?? '') ?>"  />

            <?php
$props = [
    'label' => t('E-Mail-Adresse'),
    'id' => 'uEmail',
    'name' => 'uEmail',
    'type' => 'email',
    'value' => isset($profile) ? h($profile->getUserEmail()) : ($form->getRequestValue('uEmail') ?? ''),
    'required' => 'true',
];
include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
?>

            <?php if (isset($displayTimezone) && $displayTimezone): ?>
                <?php
    $timezoneOptions = [];
                if (isset($timezones) && is_array($timezones)) {
                    foreach ($timezones as $tzValue => $tzLabel) {
                        $timezoneOptions[] = [
                            'value' => $tzValue,
                            'label' => $tzLabel,
                            'selected' => (isset($profile) && $profile->getUserTimezone() === $tzValue) ? 'true' : 'false',
                        ];
                    }
                }
                $props = [
                    'label' => t('Zeitzone'),
                    'id' => 'uTimezone',
                    'name' => 'uTimezone',
                    'type' => 'select',
                    'options' => json_encode($timezoneOptions),
                ];
                include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
                ?>
            <?php endif; ?>

            <?php if (isset($locales) && is_array($locales) && count($locales) > 0): ?>
                <?php
                $localeOptions = [];
                foreach ($locales as $localeValue => $localeLabel) {
                    $localeOptions[] = [
                        'value' => $localeValue,
                        'label' => $localeLabel,
                        'selected' => (isset($profile) && $profile->getUserDefaultLanguage() === $localeValue) ? 'true' : 'false',
                    ];
                }
                $props = [
                    'label' => t('Bevorzugte Sprache'),
                    'id' => 'uDefaultLanguage',
                    'name' => 'uDefaultLanguage',
                    'type' => 'select',
                    'options' => json_encode($localeOptions),
                ];
                include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
                ?>
            <?php endif; ?>
        </section>

        <?php if (isset($profileFormRenderer) && isset($attributeSets)): ?>
            <section class="edit-profile__section">
                <h2 class="edit-profile__section-title"><?= t('Zusätzliche Angaben') ?></h2>
                <div class="edit-profile__attributes">
                    <?php
                    foreach ($attributeSets as $setName => $attributes) {
                        foreach ($attributes as $ak) {
                            $isRequired = $ak->isAttributeKeyRequiredOnRegister() || $ak->isAttributeKeyRequiredOnProfile();
                            $reqClass = $isRequired ? ' edit-profile__attribute--required' : '';
                            echo '<div class="edit-profile__attribute' . $reqClass . '">';
                            $profileFormRenderer->buildView($ak)->render();
                            echo '</div>';
                        }
                    }
            if (isset($unassignedAttributes)) {
                foreach ($unassignedAttributes as $ak) {
                    $isRequired = $ak->isAttributeKeyRequiredOnRegister() || $ak->isAttributeKeyRequiredOnProfile();
                    $reqClass = $isRequired ? ' edit-profile__attribute--required' : '';
                    echo '<div class="edit-profile__attribute' . $reqClass . '">';
                    $profileFormRenderer->buildView($ak)->render();
                    echo '</div>';
                }
            }
            ?>
                </div>
            </section>
        <?php endif; ?>

        <details class="edit-profile__password-accordion">
            <summary class="edit-profile__password-toggle">
                <h2 class="edit-profile__section-title"><?= t('Passwort ändern') ?></h2>
                <svg class="edit-profile__password-chevron" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </summary>
            <div class="edit-profile__password-content">
                <?php
                $props = [
                    'label' => t('Aktuelles Passwort'),
                    'id' => 'uPasswordCurrent',
                    'name' => 'uPasswordCurrent',
                ];
include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
?>

                <?php
$props = [
    'label' => t('Neues Passwort'),
    'id' => 'uPasswordNew',
    'name' => 'uPasswordNew',
    'showStrength' => 'true',
];
include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
?>

                <?php
$props = [
    'label' => t('Neues Passwort bestätigen'),
    'id' => 'uPasswordNewConfirm',
    'name' => 'uPasswordNewConfirm',
];
include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
?>
            </div>
        </details>

        <div class="edit-profile__actions">
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

            <?php
$props = [
    'label' => t('Änderungen speichern'),
    'variant' => 'accent',
    'size' => 'lg',
    'type' => 'submit',
];
include DIR_COMPONENTS . '/atoms/button/button.php';
?>
        </div>
    </form>

    <!-- Danger Zone: Delete Account -->
    <section class="edit-profile__danger-zone" id="danger-zone">
        <h3 class="edit-profile__danger-title"><?= t('Konto löschen') ?></h3>
        <p class="edit-profile__danger-text">
            <?= t('Wenn du dein Konto löschst, kannst du dich nicht mehr anmelden. Deine bisherigen Beiträge (Ideen, Innovationen, Updates) bleiben erhalten.') ?>
        </p>
        <button type="button" id="btn-delete-account" class="button button--danger button--md">
            <span class="button__label"><?= t('Konto löschen') ?></span>
        </button>
    </section>

    <dialog id="delete-account-modal" class="modal modal--sm">
        <div class="modal__content">
            <h3 class="modal__title"><?= t('Konto endgültig löschen') ?></h3>
            <p><?= t('Bitte gib dein Passwort ein, um die Löschung zu bestätigen. Diese Aktion kann nicht rückgängig gemacht werden.') ?></p>
            <div class="form-field">
                <label for="delete-account-password" class="form-field__label"><?= t('Passwort') ?></label>
                <input type="password" id="delete-account-password" class="input" required>
            </div>
            <div id="delete-account-error" class="alert alert--error" role="alert" hidden></div>
            <div class="modal__footer">
                <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
                <button type="button" id="delete-account-confirm" class="button button--danger button--sm"><?= t('Endgültig löschen') ?></button>
            </div>
        </div>
    </dialog>
</div>
