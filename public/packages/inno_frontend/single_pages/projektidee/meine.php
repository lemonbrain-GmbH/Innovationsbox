<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

$user = new \Concrete\Core\User\User();
$isLoggedIn = $user->isRegistered();
?>

<div class="projektidee-meine-page">
    <?php if (!$isLoggedIn): ?>
        <div class="projektidee-meine-page__auth-notice">
            <p><?= t('Du musst angemeldet sein, um deine Projektideen zu sehen.') ?></p>
            <a href="/login" class="button button--accent button--sm"><?= t('Anmelden') ?></a>
        </div>
    <?php else: ?>
        <header class="projektidee-meine-page__header">
            <h1><?= t('Meine Projektideen') ?></h1>
            <a href="/projektidee/einreichen" class="button button--accent button--lg"><?= t('+ Neue Idee einreichen') ?></a>
        </header>

        <div id="ideas-container"></div>
    <?php endif; ?>
</div>

<!-- Edit Idea Modal -->
<dialog id="edit-idea-modal" class="modal modal--md" aria-labelledby="edit-idea-modal-title">
    <div class="modal__header">
        <h2 id="edit-idea-modal-title" class="modal__title"><?= t('Idee bearbeiten') ?></h2>
        <button type="button" class="modal__close" aria-label="<?= t('Schliessen') ?>" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>
    <div class="modal__body">
        <div class="form-field" style="margin-bottom: var(--space-4);">
            <label class="label" for="edit-idea-title"><?= t('Titel') ?> *</label>
            <input type="text" id="edit-idea-title" class="input" required>
        </div>
        <div class="form-field" style="margin-bottom: var(--space-4);">
            <label class="label" for="edit-idea-category"><?= t('Kategorie') ?> *</label>
            <select id="edit-idea-category" class="input" required>
                <option value=""><?= t('Kategorie wählen...') ?></option>
            </select>
        </div>
        <div class="form-field" style="margin-bottom: var(--space-4);">
            <label class="label" for="edit-idea-description"><?= t('Beschreibung') ?> *</label>
            <textarea id="edit-idea-description" class="input" rows="6"></textarea>
        </div>
        <div class="form-field" style="margin-bottom: var(--space-4);">
            <label class="label"><?= t('Titelbild') ?></label>
            <div class="header-image-upload">
                <div class="header-image-upload__preview" id="edit-header-image-preview" hidden>
                    <img id="edit-header-image-preview-img" alt="" loading="lazy">
                    <button type="button" class="unstyled-button header-image-upload__remove" id="edit-header-image-remove" aria-label="<?= t('Titelbild entfernen') ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="header-image-upload__dropzone" id="edit-header-image-dropzone">
                    <span><?= t('Bild hierher ziehen oder klicken zum Auswählen') ?></span>
                    <input type="file" id="edit-header-image-input" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
                </div>
            </div>
        </div>
        <div class="form-field" style="margin-bottom: var(--space-4);">
            <label class="label"><?= t('Vorhandene Anhänge') ?></label>
            <div id="edit-existing-files"></div>
        </div>
        <div class="form-field">
            <label class="label"><?= t('Neue Anhänge hinzufügen') ?></label>
            <input type="file" id="edit-new-files" multiple accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text">
        </div>
    </div>
    <div class="modal__footer">
        <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
        <button type="button" class="button button--accent button--sm" id="edit-idea-submit"><?= t('Speichern') ?></button>
    </div>
</dialog>

<!-- Withdraw Confirmation Modal -->
<dialog id="withdraw-modal" class="modal modal--sm" aria-labelledby="withdraw-modal-title">
    <div class="modal__header">
        <h2 id="withdraw-modal-title" class="modal__title"><?= t('Idee zurückziehen') ?></h2>
        <button type="button" class="modal__close" aria-label="<?= t('Schliessen') ?>" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>
    <div class="modal__body">
        <p><?= t('Diese Idee wird unwiderruflich zurückgezogen und gelöscht.') ?></p>
        <p id="withdraw-idea-name" style="font-weight: var(--font-weight-semibold);"></p>
    </div>
    <div class="modal__footer">
        <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
        <button type="button" class="button button--danger button--sm" id="withdraw-confirm"><?= t('Zurückziehen') ?></button>
    </div>
</dialog>
