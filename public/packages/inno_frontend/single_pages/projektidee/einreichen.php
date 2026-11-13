<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

$user = new \Concrete\Core\User\User();
$isLoggedIn = $user->isRegistered();
?>

<div class="projektidee-einreichen-page">
    <?php if (!$isLoggedIn): ?>
        <div class="projektidee-einreichen-page__auth-notice">
            <p><?= t('Du musst angemeldet sein, um eine Projektidee einzureichen.') ?></p>
            <a href="/login" class="button button--accent button--lg"><?= t('Anmelden') ?></a>
        </div>
    <?php else: ?>
        <header class="projektidee-einreichen-page__header">
            <h1><?= t('Projektidee einreichen') ?></h1>
            <p><?= t('Beschreibe deine Idee für ein klimarelevantes Projekt. Nach der Einreichung wird sie vom Moderationsteam geprüft.') ?></p>
        </header>

        <form id="idea-form" class="projektidee-einreichen-page__form">
            <div class="form-field" style="margin-bottom: var(--space-4);">
                <label class="label" for="idea-title"><?= t('Titel') ?> *</label>
                <input type="text" id="idea-title" name="title" class="input" required placeholder="<?= t('z.B. Solaranlage auf dem Schulhaus') ?>">
            </div>

            <div class="form-field" style="margin-bottom: var(--space-4);">
                <label class="label" for="idea-category"><?= t('Kategorie') ?> *</label>
                <select id="idea-category" name="category" class="input" required>
                    <option value=""><?= t('Kategorie wählen...') ?></option>
                </select>
            </div>

            <div class="form-field" style="margin-bottom: var(--space-4);">
                <label class="label" for="idea-description"><?= t('Beschreibung') ?> *</label>
                <textarea id="idea-description" name="description" class="input" rows="8"></textarea>
            </div>

            <div class="form-field" style="margin-bottom: var(--space-4);">
                <label class="label"><?= t('Titelbild') ?></label>
                <p class="form-hint"><?= t('Fügen Sie ein Titelbild hinzu (min. 800px breit, max. 10MB). JPEG, PNG, GIF oder WebP.') ?></p>
                <div class="header-image-upload" id="header-image-upload">
                    <div class="header-image-upload__preview" id="header-image-preview" hidden>
                        <img id="header-image-preview-img" alt="" loading="lazy">
                        <button type="button" class="unstyled-button header-image-upload__remove" id="header-image-remove" aria-label="<?= t('Titelbild entfernen') ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="header-image-upload__dropzone" id="header-image-dropzone">
                        <span><?= t('Bild hierher ziehen oder klicken zum Auswählen') ?></span>
                        <input type="file" id="header-image-input" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
                    </div>
                </div>
            </div>

            <div class="form-field" style="margin-bottom: var(--space-4);">
                <label class="label"><?= t('Anhänge') ?> <span style="font-weight: normal; color: var(--color-text-muted);">(<?= t('optional, max. 5 Dateien, je max. 10 MB') ?>)</span></label>
                <div id="file-upload-zone" class="file-upload-zone">
                    <p class="file-upload-zone__text"><?= t('Dateien hierher ziehen oder klicken zum Auswählen') ?></p>
                    <input type="file" id="idea-files" multiple accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text" hidden>
                </div>
                <div id="file-list" class="file-upload-zone__list"></div>
            </div>

            <div id="form-error-container"></div>

            <div class="projektidee-einreichen-page__actions">
                <a href="/projektidee/meine" class="button button--muted button--lg"><?= t('Abbrechen') ?></a>
                <button type="submit" class="button button--accent button--lg" id="idea-submit"><?= t('Idee einreichen') ?></button>
            </div>
        </form>
    <?php endif; ?>
</div>
