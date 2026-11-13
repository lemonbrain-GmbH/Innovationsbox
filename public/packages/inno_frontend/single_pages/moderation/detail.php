<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');
?>

<div class="moderation-detail-page">
    <a href="/moderation" class="moderation-detail-page__back">← <?= t('Zurück zur Übersicht') ?></a>

    <div id="review-container"></div>
</div>

<!-- Reject Confirmation Modal -->
<dialog id="reject-modal" class="modal modal--sm" aria-labelledby="reject-modal-title">
    <div class="modal__header">
        <h2 id="reject-modal-title" class="modal__title"><?= t('Idee ablehnen') ?></h2>
        <button type="button" class="modal__close" aria-label="<?= t('Schliessen') ?>" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>
    <div class="modal__body">
        <p><?= t('Bitte gib einen Grund für die Ablehnung an. Dieser wird dem Einreichenden mitgeteilt.') ?></p>
        <div class="form-field">
            <label class="label" for="reject-reason"><?= t('Ablehnungsgrund') ?> *</label>
            <textarea id="reject-reason" class="input" rows="4" required></textarea>
        </div>
    </div>
    <div class="modal__footer">
        <button type="button" class="button button--muted button--sm" data-modal-close><?= t('Abbrechen') ?></button>
        <button type="button" class="button button--danger button--sm" id="reject-confirm"><?= t('Ablehnen') ?></button>
    </div>
</dialog>
