<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

/** @var string $spaceId */
$spaceId = $spaceId ?? '';

?>
<div class="projektraum-page" data-space-id="<?= htmlspecialchars($spaceId) ?>">
    <div id="projektraum-hero-container"></div>

    <header class="projektraum-page__header" id="space-header">
        <div class="projektraum-page__header-info">
            <div class="projektraum-page__status" id="space-status-badge"></div>
            <h1 class="projektraum-page__title" id="space-title"></h1>
            <div class="projektraum-page__description" id="space-description"></div>
        </div>
        <div class="projektraum-page__header-actions" id="space-actions"></div>
    </header>

    <div class="projektraum-page__content">
        <main class="projektraum-page__main">
            <div id="collaboard-panel-container"></div>
            <div id="idea-attachments-container"></div>
            <div id="status-update-timeline-container"></div>
        </main>

        <div class="projektraum-page__sidebar" id="sidebar-container"></div>
    </div>

    <a href="/innovationen" class="button button--muted button--lg projektraum-page__back"><?= t('Zurück zur Übersicht') ?></a>
</div>

<!-- Status Update Modal -->
<dialog id="update-modal" class="modal modal--lg" aria-labelledby="update-modal-title">
    <div class="modal__header">
        <h2 id="update-modal-title" class="modal__title"><?= t('Update schreiben') ?></h2>
        <button type="button" class="modal__close" aria-label="<?= t('Schliessen') ?>" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>
    <form id="update-form" class="modal__body">
        <div class="form-field" style="margin-bottom: var(--space-4);">
            <label class="label" for="update-title"><?= t('Titel') ?> *</label>
            <input type="text" id="update-title" name="title" class="input" required placeholder="<?= t('z.B. Kickoff abgeschlossen') ?>">
        </div>
        <div class="form-field" style="margin-bottom: var(--space-4);">
            <label class="label" for="update-body"><?= t('Inhalt') ?> *</label>
            <textarea id="update-body" name="update-body" rows="8" required></textarea>
        </div>
        <div class="form-field" style="margin-bottom: var(--space-4);" id="update-existing-files-container" hidden>
            <label class="label"><?= t('Vorhandene Anhänge') ?></label>
            <div id="update-existing-files"></div>
        </div>
        <div class="form-field">
            <label class="label"><?= t('Anhänge') ?> <span style="font-weight: normal; color: var(--color-text-muted);">(<?= t('max. 5 Dateien, je max. 10 MB') ?>)</span></label>
            <div id="update-file-zone" class="file-upload-zone">
                <p class="file-upload-zone__text"><?= t('Dateien hierher ziehen oder klicken zum Auswählen') ?></p>
                <input type="file" id="update-files" multiple accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text" hidden>
            </div>
            <div id="update-file-list" class="file-upload-zone__list"></div>
        </div>
    </form>
    <div class="modal__footer">
        <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
        <button type="button" class="button button--accent button--sm" id="update-submit"><?= t('Veröffentlichen') ?></button>
    </div>
</dialog>

<!-- Add Collaboard Modal -->
<dialog id="collaboard-modal" class="modal modal--md" aria-labelledby="collaboard-modal-title">
    <div class="modal__header">
        <h2 id="collaboard-modal-title" class="modal__title"><?= t('Collaboard hinzufügen') ?></h2>
        <button type="button" class="modal__close" aria-label="<?= t('Schliessen') ?>" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>
    <form id="collaboard-form" class="modal__body">
        <div class="form-field" style="margin-bottom: var(--space-4);">
            <label class="label" for="collaboard-title"><?= t('Board-Titel') ?> *</label>
            <input type="text" id="collaboard-title" name="title" class="input" required placeholder="<?= t('z.B. Brainstorming Board') ?>">
        </div>
        <div class="form-field">
            <label class="label" for="collaboard-url"><?= t('Collaboard-URL') ?> *</label>
            <input type="url" id="collaboard-url" name="url" class="input" required placeholder="https://web.collaboard.app/share/...">
        </div>
    </form>
    <div class="modal__footer">
        <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
        <button type="button" class="button button--accent button--sm" id="collaboard-submit"><?= t('Hinzufügen') ?></button>
    </div>
</dialog>

<!-- Delete Update Confirmation Modal -->
<dialog id="delete-update-modal" class="modal modal--sm" aria-labelledby="delete-update-modal-title">
    <div class="modal__header">
        <h2 id="delete-update-modal-title" class="modal__title"><?= t('Update löschen') ?></h2>
        <button type="button" class="modal__close" aria-label="<?= t('Schliessen') ?>" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>
    <div class="modal__body">
        <p><?= t('Dieses Update wird unwiderruflich gelöscht.') ?></p>
        <p id="delete-update-name" style="font-weight: var(--font-weight-semibold);"></p>
    </div>
    <div class="modal__footer">
        <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
        <button type="button" class="button button--danger button--sm" id="delete-update-confirm"><?= t('Löschen') ?></button>
    </div>
</dialog>

<!-- Delete Space Confirmation Modal -->
<dialog id="delete-space-modal" class="modal modal--sm" aria-labelledby="delete-space-modal-title">
    <div class="modal__header">
        <h2 id="delete-space-modal-title" class="modal__title"><?= t('Innovation löschen') ?></h2>
        <button type="button" class="modal__close" aria-label="<?= t('Schliessen') ?>" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>
    <div class="modal__body">
        <p><?= t('Sicher löschen? Diese Innovation wird unwiderruflich gelöscht — inklusive aller Updates, Anhänge, Collaboard-Links und Mitgliedschaften.') ?></p>
        <p style="font-weight: var(--font-weight-semibold); color: var(--color-action-danger, var(--color-status-error-600));"><?= t('Diese Aktion kann nicht rückgängig gemacht werden.') ?></p>
    </div>
    <div class="modal__footer">
        <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
        <button type="button" class="button button--danger button--sm" id="delete-space-confirm"><?= t('Endgültig löschen') ?></button>
    </div>
</dialog>

<!-- Status Change Confirmation Modal -->
<dialog id="status-modal" class="modal modal--sm" aria-labelledby="status-modal-title">
    <div class="modal__header">
        <h2 id="status-modal-title" class="modal__title"></h2>
        <button type="button" class="modal__close" aria-label="<?= t('Schliessen') ?>" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>
    <div class="modal__body">
        <p id="status-modal-message"></p>
    </div>
    <div class="modal__footer">
        <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
        <button type="button" class="button button--accent button--sm" id="status-modal-confirm"></button>
    </div>
</dialog>
