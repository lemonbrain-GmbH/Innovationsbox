<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $pagesJson */
$pagesJson = (string) ($props['pages'] ?? '[]');

/** @var array<int, array<string, mixed>> $pages */
$pages = (array) (json_decode($pagesJson, true) ?: []);
?>
<div class="lb-container spaced-block">
    <div class="page-list-wrapper" role="region" aria-label="<?= t('Seitenliste') ?>">
        <div class="page-list-pages">
            <?php foreach ($pages as $page): ?>
                <?php
                $props = [
                    'title' => $page['title'] ?? '',
                    'href' => $page['href'] ?? '#',
                    'target' => $page['target'] ?? '_self',
                    'imageSrc' => $page['imageSrc'] ?? '',
                    'imageAlt' => $page['imageAlt'] ?? '',
                ];
                include DIR_COMPONENTS . '/molecules/page-card/page-card.php';
                ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
