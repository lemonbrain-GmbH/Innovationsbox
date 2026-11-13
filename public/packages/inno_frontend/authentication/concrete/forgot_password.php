<?php defined('C5_EXECUTE') or die('Access denied.');

$token = Core::make('helper/validation/token');
$request = \Concrete\Core\Http\Request::getInstance();

/*
 * Security: never reveal whether an email address has an account.
 * If the CMS core added an SMTP error (mail server rejected the
 * recipient, connection failed, etc.) we silently suppress it and
 * redirect to the "password sent" page — the user sees the same
 * response regardless of whether the email exists or the mail server
 * is reachable. Rate-limit errors are still shown.
 */
if (isset($callbackError) && $callbackError->has() && $request->isMethod('POST')) {
    $isOnlyMailError = true;
    foreach ($callbackError->getList() as $err) {
        $msg = is_object($err) ? $err->getMessage() : (string) $err;
        // Keep rate-limit / validation errors visible
        if (
            stripos($msg, 'too many attempts') !== false
            || stripos($msg, 'email address') !== false
            || stripos($msg, 'token') !== false
        ) {
            $isOnlyMailError = false;
            break;
        }
    }
    if ($isOnlyMailError) {
        // Redirect to the same "email sent" page the CMS would show on success
        $handle = isset($authType) ? $authType->getAuthenticationTypeHandle() : 'concrete';
        $redirectUrl = (string) \Concrete\Core\Support\Facade\Url::to('/login', $handle, 'password_sent');
        header('Location: ' . $redirectUrl, true, 302);
        exit;
    }
}

if (isset($callbackError) && $callbackError->has()) {
    $props = [
        'variant' => 'error',
        'title' => '',
        'message' => implode('<br>', $callbackError->getList()),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';
}
?>

<form method="post" action="<?= URL::to('/login', 'callback', $authType->getAuthenticationTypeHandle(), 'forgot_password') ?>">
    <?php $token->output(); ?>

    <p style="font-size: var(--text-sm); color: var(--color-text-secondary); margin-bottom: var(--space-4);">
        <?= t('Gib deine E-Mail-Adresse ein. Wir senden dir eine Anleitung zum Zurücksetzen deines Passworts.') ?>
    </p>

    <?php
    $props = [
        'label' => t('E-Mail-Adresse'),
        'id' => 'uEmail',
        'name' => 'uEmail',
        'type' => 'email',
        'required' => 'true',
    ];
include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
?>

    <div style="margin-top: var(--space-4);">
        <?php
    $props = [
        'label' => t('Passwort zurücksetzen'),
        'variant' => 'accent',
        'size' => 'lg',
        'type' => 'submit',
        'fullWidth' => 'true',
    ];
include DIR_COMPONENTS . '/atoms/button/button.php';
?>
    </div>

    <div style="text-align: center; margin-top: var(--space-4);">
        <a href="<?= URL::to('/login') ?>" class="auth-link">
            <?= t('Zurück zur Anmeldung') ?>
        </a>
    </div>
</form>
