<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Authentication\AuthenticationType;
use Concrete\Core\User\User;

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var \Concrete\Core\User\User $user */
/** @var \Concrete\Core\Error\ErrorList\ErrorList|null $error */
/** @var bool $isLoggedIn */
/** @var bool $isAttributeMode */
/** @var bool $isLogoutComplete */
/** @var \Concrete\Core\Authentication\AuthenticationType|null $authType */
/** @var \Concrete\Core\Authentication\AuthenticationType[] $activeAuths */
/** @var string|null $authTypeElement */
/** @var array<string, mixed> $authTypeParams */
/** @var array<int, mixed> $required_attributes */
/** @var string|null $logoutMessage */
$form = app('helper/form');
$token = app('helper/validation/token');
$user = $user ?? new User();
$isLoggedIn = $user->isRegistered();

// Resolve auth types (same as core login.php)
if (isset($authType) && $authType) {
    $activeAuths = [$authType];
} elseif (!isset($activeAuths) || !$activeAuths) {
    $activeAuths = AuthenticationType::getList(true, true);
}
if (!isset($authTypeElement)) {
    $authTypeElement = null;
}
if (!isset($authTypeParams)) {
    $authTypeParams = [];
}
// Workaround: elements not in apiMethods (e.g. password_sent) can't go through
// callback(), so they arrive via ?auth_element= query param instead.
if (!$authTypeElement && !empty($_GET['auth_element'])) {
    $authTypeElement = preg_replace('/[^a-z_]/', '', $_GET['auth_element']);
    $at = AuthenticationType::getByHandle('concrete');
    if ($at) {
        $activeAuths = [$at];
    }
}
core_log(json_encode($authTypeParams));
$isAttributeMode = isset($required_attributes) && count($required_attributes) > 0;
$isLogoutComplete = isset($logoutComplete) && $logoutComplete;

$authCardTitle = 'Anmelden';
$authCardSubtitle = 'Willkommen zurück';

if ($isAttributeMode) {
    $authCardTitle = 'Profil vervollständigen';
    $authCardSubtitle = 'Bitte fülle die folgenden Felder aus.';
} elseif ($isLogoutComplete) {
    $authCardTitle = 'Abgemeldet';
    $authCardSubtitle = '';
} elseif ($authTypeElement === 'forgot_password') {
    $authCardTitle = 'Passwort vergessen';
    $authCardSubtitle = '';
}

ob_start();

if ($isLogoutComplete) {
    $props = [
        'variant' => 'success',
        'title' => '',
        'message' => $logoutMessage ?? t('Du wurdest abgemeldet.'),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';
} elseif ($isLoggedIn && !$isAttributeMode) {
    $props = [
        'variant' => 'info',
        'title' => '',
        'message' => t('Du bist bereits angemeldet.'),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';
} elseif ($isAttributeMode) {
    echo '<form class="concrete-login-form" method="post" action="' . $view->action('fill_attributes') . '">';
    $attribute_helper = new \Concrete\Core\Form\Service\Widget\Attribute();
    foreach ($required_attributes as $ak) {
        echo $attribute_helper->display($ak, true);
    }
    echo '<div>';
    $props = [
        'label' => t('Speichern'),
        'variant' => 'primary',
        'type' => 'submit',
        'fullWidth' => 'true',
    ];
    include DIR_COMPONENTS . '/atoms/button/button.php';
    echo '</div></form>';
} else {
    if (isset($error) && is_object($error) && $error->has()) {
        $props = [
            'variant' => 'error',
            'title' => '',
            'message' => implode('<br>', $error->getList()),
            'dismissible' => 'false',
        ];
        include DIR_COMPONENTS . '/molecules/alert/alert.php';
    }

    foreach ($activeAuths as $auth) {
        if ($authTypeElement && !empty($authTypeParams) && method_exists($auth->controller, $authTypeElement)) {
            // Workaround: CMS core renderTemplate() calls controller methods without
            // forwarding URL params (e.g. change_password() receives no $uHash).
            // Call the controller method with the correct params, then render the
            // template ourselves so renderTemplate doesn't re-invoke it without args.
            // Use an isolated closure (like core TemplateService) to prevent scope leaks.
            call_user_func_array([$auth->controller, $authTypeElement], $authTypeParams);
            $sets = $auth->controller->getSets();
            $atHandle = $auth->getAuthenticationTypeHandle();
            $pkgPath = DIR_PACKAGES . '/inno_frontend/authentication/' . $atHandle . '/' . $authTypeElement . '.php';
            $corePath = DIR_BASE_CORE . '/' . DIRNAME_AUTHENTICATION . '/' . $atHandle . '/' . $authTypeElement . '.php';
            $templateFile = file_exists($pkgPath) ? $pkgPath : (file_exists($corePath) ? $corePath : null);
            if ($templateFile) {
                $renderIsolated = function (string $_path, array $_data): void {
                    extract($_data);
                    include $_path;
                };
                $renderIsolated($templateFile, is_array($sets) ? $sets : []);
            }
        } elseif ($authTypeElement) {
            $auth->renderForm($authTypeElement, $authTypeParams);
        } else {
            $auth->renderForm();
        }
    }
}

$content = ob_get_clean();

$props = [
    'title' => $authCardTitle,
    'subtitle' => $authCardSubtitle,
    'showLogo' => 'false',
    'content' => $content,
];
include DIR_COMPONENTS . '/molecules/auth-card/auth-card.php';
