<?php defined('C5_EXECUTE') or die('Access denied.'); ?>

<?php
$props = [
    'variant' => 'error',
    'title' => t('Token konnte nicht validiert werden'),
    'message' => t('The token you provided doesn\'t appear to be valid. Please paste the url exactly as it appears in the email or visit the forgot password page again to have a new token generated.'),
    'dismissible' => 'false',
];
include DIR_COMPONENTS . '/molecules/alert/alert.php';
?>

<div style="text-align: center; margin-top: var(--space-4);">
    <a href="<?= URL::to('/login') ?>" class="auth-link">
        <?= t('Zurück zur Anmeldung') ?>
    </a>
</div>
