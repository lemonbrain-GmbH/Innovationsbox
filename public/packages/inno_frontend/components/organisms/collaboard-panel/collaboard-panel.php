<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

$collaboardLinks = (array) ($props['collaboardLinks'] ?? []);
$canManage = ($props['canManage'] ?? 'false') === 'true';
?>
<section class="collaboard-panel" aria-label="<?= t('Collaboards') ?>">
    <div class="collaboard-panel__header">
        <h3 class="collaboard-panel__title"><?= t('Collaboards') ?></h3>
        <?php if ($canManage): ?>
            <?php
            $props = ['label' => '+ Board hinzufügen', 'variant' => 'secondary', 'size' => 'sm'];
            include DIR_COMPONENTS . '/atoms/button/button.php';
            ?>
        <?php endif; ?>
    </div>

    <?php if (empty($collaboardLinks)): ?>
        <?php
        $props = ['title' => 'Keine Collaboards', 'message' => 'Es wurden noch keine Collaboards hinzugefügt.'];
        include DIR_COMPONENTS . '/molecules/empty-state/empty-state.php';
        ?>
    <?php else: ?>
        <div class="collaboard-panel__list">
            <?php foreach ($collaboardLinks as $link): ?>
                <?php
                $props = [
                    'id' => $link['id'] ?? '',
                    'title' => $link['title'] ?? '',
                    'url' => $link['url'] ?? '',
                    'isActive' => 'false',
                ];
                include DIR_COMPONENTS . '/molecules/collaboard-item/collaboard-item.php';
                ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="collaboard-panel__iframe-container" id="collaboard-iframe-container" hidden aria-hidden="true">
        <div class="collaboard-panel__iframe-toolbar">
            <span class="collaboard-panel__iframe-title" id="collaboard-iframe-title"></span>
            <div class="collaboard-panel__iframe-actions">
                <a class="collaboard-panel__iframe-newtab" id="collaboard-iframe-newtab" href="#" target="_blank" rel="noopener"><?= t('In neuem Tab öffnen') ?> ↑</a>
                <button class="collaboard-panel__iframe-close" id="collaboard-iframe-close"><?= t('Schliessen') ?> ×</button>
            </div>
        </div>
        <iframe class="collaboard-panel__iframe" id="collaboard-iframe" src="" title="Collaboard"></iframe>
    </div>
</section>
