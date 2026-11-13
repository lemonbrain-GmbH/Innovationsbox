<?php defined('C5_EXECUTE') or die('Access denied.'); ?>

<?php
$props = [
    'variant' => 'success',
    'title' => t('E-Mail bestätigt'),
    'message' => t('Deine E-Mail-Adresse wurde bestätigt. Du kannst dich jetzt anmelden.'),
    'dismissible' => 'false',
];
include DIR_COMPONENTS . '/molecules/alert/alert.php';
?>

<div style="text-align: center; margin-top: var(--space-4);">
    <a href="<?= URL::to('/login') ?>" class="auth-link">
        <?= t('Anmelden') ?>
    </a>
</div>
