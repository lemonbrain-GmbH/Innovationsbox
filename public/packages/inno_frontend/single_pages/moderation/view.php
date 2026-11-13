<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');
?>

<div class="moderation-page">
    <header class="moderation-page__header">
        <h1><?= t('Moderation') ?></h1>
        <p><?= t('Eingereichte Projektideen prüfen und genehmigen oder ablehnen.') ?></p>
    </header>

    <nav class="moderation-page__nav">
        <a href="/moderation/kategorien" class="moderation-page__nav-link">
            <span class="moderation-page__nav-icon">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/><circle cx="8" cy="6" r="1.5" fill="currentColor" stroke="none"/><circle cx="8" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="8" cy="18" r="1.5" fill="currentColor" stroke="none"/></svg>
            </span>
            <?= t('Kategorien verwalten') ?>
        </a>
        <a href="/moderation/benutzer" class="moderation-page__nav-link">
            <span class="moderation-page__nav-icon">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            </span>
            <?= t('Benutzer verwalten') ?>
        </a>
    </nav>

    <div id="reviews-container"></div>
</div>
