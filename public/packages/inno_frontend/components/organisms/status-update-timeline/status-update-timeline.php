<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

$updates = (array) ($props['updates'] ?? []);
$canManage = ($props['canManage'] ?? 'false') === 'true';
?>
<section class="status-update-timeline" aria-label="<?= t('Projekt-Updates') ?>">
    <div class="status-update-timeline__header">
        <h3 class="status-update-timeline__title"><?= t('Projekt-Updates') ?></h3>
        <?php if ($canManage): ?>
            <?php
            $props = ['label' => '+ Update schreiben', 'variant' => 'primary', 'size' => 'sm'];
            include DIR_COMPONENTS . '/atoms/button/button.php';
            ?>
        <?php endif; ?>
    </div>

    <?php if (empty($updates)): ?>
        <?php
        $props = ['title' => 'Keine Updates', 'message' => 'Es wurden noch keine Updates veröffentlicht.'];
        include DIR_COMPONENTS . '/molecules/empty-state/empty-state.php';
        ?>
    <?php else: ?>
        <div class="status-update-timeline__list">
            <?php foreach ($updates as $i => $update): ?>
                <?php
                $props = [
                    'title' => $update['title'] ?? '',
                    'body' => $update['body'] ?? '',
                    'authorName' => $update['authorName'] ?? '',
                    'createdAt' => $update['createdAt'] ?? '',
                    'isLatest' => $i === 0 ? 'true' : 'false',
                ];
                include DIR_COMPONENTS . '/molecules/status-update-entry/status-update-entry.php';
                ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
