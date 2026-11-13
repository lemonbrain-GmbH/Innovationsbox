<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');
?>
<section class="innovation-teaser lb-container">
    <div class="innovation-teaser__header">
        <h2 class="innovation-teaser__title"><?= t('Aktuelle Innovationen') ?></h2>
    </div>

    <div class="innovation-teaser__grid" data-innovation-teaser-grid>
        <div class="innovation-teaser__loading">
            <?php
            $props = [];
include DIR_COMPONENTS . '/atoms/spinner/spinner.php';
?>
        </div>
    </div>

    <div class="innovation-teaser__footer">
        <a href="/innovationen" class="button button--accent button--lg"><?= t('Alle Innovationen') ?></a>
    </div>
</section>
