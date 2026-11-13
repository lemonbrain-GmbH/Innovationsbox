<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');
?>

<div class="moderation-kategorien-page">
    <a href="/moderation" class="moderation-kategorien-page__back"><?= t('← Zurück zur Moderation') ?></a>

    <header class="moderation-kategorien-page__header">
        <h1><?= t('Kategorien verwalten') ?></h1>
        <button type="button" class="button button--accent button--sm" id="btn-add-category"><?= t('+ Neue Kategorie') ?></button>
    </header>

    <div id="categories-container"></div>
</div>

<!-- Add/Edit Category Modal -->
<dialog id="category-modal" class="modal modal--sm" aria-labelledby="category-modal-title">
    <div class="modal__header">
        <h2 id="category-modal-title" class="modal__title"><?= t('Kategorie') ?></h2>
        <button type="button" class="modal__close" aria-label="<?= t('Schliessen') ?>" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>
    <div class="modal__body">
        <div class="form-field" style="margin-bottom: var(--space-4);">
            <label class="label" for="category-name"><?= t('Name') ?> *</label>
            <input type="text" id="category-name" class="input" required>
        </div>
        <div class="form-field" style="margin-bottom: var(--space-4);">
            <label class="label" for="category-slug"><?= t('Slug') ?> <span style="font-weight: normal; color: var(--color-text-muted);">(<?= t('optional, wird automatisch generiert') ?>)</span></label>
            <input type="text" id="category-slug" class="input">
        </div>
        <div class="form-field">
            <label class="label" for="category-sort"><?= t('Sortierung') ?></label>
            <input type="number" id="category-sort" class="input" value="0" min="0">
        </div>
    </div>
    <div class="modal__footer">
        <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
        <button type="button" class="button button--accent button--sm" id="category-submit"><?= t('Speichern') ?></button>
    </div>
</dialog>

<!-- Delete Confirmation Modal -->
<dialog id="delete-category-modal" class="modal modal--sm" aria-labelledby="delete-category-modal-title">
    <div class="modal__header">
        <h2 id="delete-category-modal-title" class="modal__title"><?= t('Kategorie löschen') ?></h2>
        <button type="button" class="modal__close" aria-label="<?= t('Schliessen') ?>" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>
    <div class="modal__body">
        <p><?= t('Diese Kategorie wird unwiderruflich gelöscht.') ?></p>
        <p id="delete-category-name" style="font-weight: var(--font-weight-semibold);"></p>
    </div>
    <div class="modal__footer">
        <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
        <button type="button" class="button button--danger button--sm" id="delete-category-confirm"><?= t('Löschen') ?></button>
    </div>
</dialog>
