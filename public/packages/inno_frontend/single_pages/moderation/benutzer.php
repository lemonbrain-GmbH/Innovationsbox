<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');
?>

<div class="moderation-benutzer-page">
    <a href="/moderation" class="moderation-benutzer-page__back"><?= t('← Zurück zur Moderation') ?></a>

    <header class="moderation-benutzer-page__header">
        <h1><?= t('Benutzer verwalten') ?></h1>
    </header>

    <div id="users-container"></div>
</div>

<dialog id="delete-user-modal" class="modal modal--sm">
    <div class="modal__content">
        <h3 class="modal__title"><?= t('Benutzer entfernen') ?></h3>
        <p id="delete-user-name"></p>
        <div class="form-field">
            <label for="delete-user-reason" class="form-field__label"><?= t('Begründung (wird dem Benutzer per E-Mail mitgeteilt)') ?></label>
            <textarea id="delete-user-reason" class="input" rows="3" required></textarea>
        </div>
        <div id="delete-user-error" class="alert alert--error" role="alert" hidden></div>
        <div class="modal__footer">
            <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
            <button type="button" id="delete-user-confirm" class="button button--danger button--sm"><?= t('Benutzer entfernen') ?></button>
        </div>
    </div>
</dialog>
