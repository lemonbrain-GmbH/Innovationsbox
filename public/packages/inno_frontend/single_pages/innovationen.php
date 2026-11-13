<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');
?>
<div class="innovationen-page">
    <header class="innovationen-page__header">
        <h1 class="innovationen-page__title"><?= t('Innovationen') ?></h1>
        <div class="innovationen-page__search">
            <input type="search" class="innovationen-page__search-input" id="project-search"
                   placeholder="<?= t('Innovationen durchsuchen...') ?>" aria-label="<?= t('Suche') ?>">
        </div>
    </header>
    <p class="innovationen-page__subtitle"><?= t('Alle laufenden und abgeschlossenen Innovationen unserer Community') ?></p>

    <hr class="innovationen-page__divider">

    <div class="innovationen-page__toolbar">
        <div class="innovationen-page__filter-group">
            <span class="innovationen-page__filter-label"><?= t('Filtern:') ?></span>
            <nav class="innovationen-page__filters" id="project-filters" aria-label="<?= t('Filter') ?>">
                <button class="unstyled-button innovationen-page__filter innovationen-page__filter--active" data-value="" type="button"><?= t('Alle') ?></button>
                <button class="unstyled-button innovationen-page__filter" data-value="active" type="button"><?= t('Aktive') ?></button>
                <button class="unstyled-button innovationen-page__filter" data-value="completed" type="button"><?= t('Abgeschlossene') ?></button>
                <button class="unstyled-button innovationen-page__filter" data-value="archived" type="button" id="filter-archived" hidden><?= t('Archiviert') ?></button>
            </nav>
        </div>
        <div class="innovationen-page__filter-group">
            <span class="innovationen-page__filter-label"><?= t('Rubriken:') ?></span>
            <nav class="innovationen-page__filters innovationen-page__filters--categories" id="category-filters" aria-label="<?= t('Kategorie-Filter') ?>">
                <button class="unstyled-button innovationen-page__filter innovationen-page__filter--active" data-category="" type="button"><?= t('Alle') ?></button>
            </nav>
        </div>
    </div>

    <div class="innovationen-page__grid" id="project-spaces-grid">
        <div class="innovationen-page__loading">
            <?php
    $props = ['size' => 'lg'];
include DIR_COMPONENTS . '/atoms/spinner/spinner.php';
?>
        </div>
    </div>

    <div class="innovationen-page__pagination" id="project-pagination"></div>
</div>
