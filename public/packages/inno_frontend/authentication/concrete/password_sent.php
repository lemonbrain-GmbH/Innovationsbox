<?php defined('C5_EXECUTE') or die('Access denied.'); ?>

<?php
$props = [
    'variant' => 'success',
    'title' => t('E-Mail gesendet'),
    'message' => t('Wir haben dir eine E-Mail mit Anweisungen zum Zurücksetzen deines Passworts gesendet.'),
    'dismissible' => 'false',
];
include DIR_COMPONENTS . '/molecules/alert/alert.php';
?>

<div style="text-align: center; margin-top: var(--space-4);">
    <a href="<?= URL::to('/login') ?>" class="auth-link">
        <?= t('Zurück zur Anmeldung') ?>
    </a>
</div>
