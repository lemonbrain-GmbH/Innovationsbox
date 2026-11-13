<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

$user = new \Concrete\Core\User\User();
$isLoggedIn = $user->isRegistered();
?>

<div class="expert-anfragen-page">
    <?php if (!$isLoggedIn): ?>
        <div class="expert-anfragen-page__auth-notice">
            <p><?= t('Du musst angemeldet sein, um deine Anfragen zu sehen.') ?></p>
            <a href="/login" class="button button--accent button--lg"><?= t('Anmelden') ?></a>
        </div>
    <?php else: ?>
        <header class="expert-anfragen-page__header">
            <h1><?= t('Meine Anfragen') ?></h1>
            <p><?= t('Projektteams haben dich als Experte angefragt.') ?></p>
        </header>

        <div id="requests-container"></div>
    <?php endif; ?>
</div>
