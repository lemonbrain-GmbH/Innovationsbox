<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $id */
$id = (string) ($props['id'] ?? 'modal-' . uniqid());
/** @var string $title */
$title = (string) ($props['title'] ?? '');
/** @var string $content */
$content = (string) ($props['content'] ?? '');
/** @var string $footer */
$footer = (string) ($props['footer'] ?? '');
/** @var string $size */
$size = (string) ($props['size'] ?? 'md');
/** @var bool $open */
$open = ($props['open'] ?? 'false') === 'true';

$titleId = $id . '-title';
?>
<dialog
    id="<?= htmlspecialchars($id) ?>"
    class="modal modal--<?= htmlspecialchars($size) ?>"
    aria-labelledby="<?= htmlspecialchars($titleId) ?>"
    <?= $open ? 'open' : '' ?>
>
    <div class="modal__header">
        <h2 id="<?= htmlspecialchars($titleId) ?>" class="modal__title"><?= htmlspecialchars($title) ?></h2>
        <button type="button" class="modal__close" aria-label="Schliessen" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    </div>
    <div class="modal__body"><?= $content ?></div>
    <?php if ($footer): ?>
        <div class="modal__footer"><?= $footer ?></div>
    <?php endif; ?>
</dialog>
