<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

$title = (string) ($props['title'] ?? '');
$body = (string) ($props['body'] ?? '');
$authorName = (string) ($props['authorName'] ?? '');
$createdAt = (string) ($props['createdAt'] ?? '');
$isLatest = ($props['isLatest'] ?? 'false') === 'true';
?>
<div class="status-update-entry<?= $isLatest ? ' status-update-entry--latest' : '' ?>">
    <div class="status-update-entry__dot"></div>
    <div class="status-update-entry__content">
        <span class="status-update-entry__meta"><?= htmlspecialchars($createdAt) ?> — <?= htmlspecialchars($authorName) ?></span>
        <h4 class="status-update-entry__title"><?= htmlspecialchars($title) ?></h4>
        <?php if ($body !== ''): ?>
            <div class="status-update-entry__body"><?= $body ?></div>
        <?php endif; ?>
    </div>
</div>
