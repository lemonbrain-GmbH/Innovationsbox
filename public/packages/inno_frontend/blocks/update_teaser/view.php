<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');
?>
<section class="update-teaser lb-container spaced-block">
    <div class="update-teaser__header">
        <h2 class="update-teaser__title"><?= t('Letzte Innovationsentwicklungen') ?></h2>
    </div>

    <div class="update-teaser__list" data-update-teaser-list>
        <div class="update-teaser__loading">
            <?php
            $props = [];
include DIR_COMPONENTS . '/atoms/spinner/spinner.php';
?>
        </div>
    </div>
</section>
