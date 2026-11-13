<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

$id = (string) ($props['id'] ?? '');
$title = (string) ($props['title'] ?? '');
$url = (string) ($props['url'] ?? '');
$isActive = ($props['isActive'] ?? 'false') === 'true';
?>
<div class="collaboard-item<?= $isActive ? ' collaboard-item--active' : '' ?>"
     data-id="<?= htmlspecialchars($id) ?>"
     data-url="<?= htmlspecialchars($url) ?>">
    <div class="collaboard-item__icon">
        <span class="collaboard-item__icon-text">C</span>
    </div>
    <div class="collaboard-item__info">
        <span class="collaboard-item__title"><?= htmlspecialchars($title) ?></span>
        <span class="collaboard-item__url"><?= htmlspecialchars(parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH)) ?></span>
    </div>
    <a class="button button--muted button--sm collaboard-item__action" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener noreferrer"><?= t('Öffnen') ?></a>
</div>
