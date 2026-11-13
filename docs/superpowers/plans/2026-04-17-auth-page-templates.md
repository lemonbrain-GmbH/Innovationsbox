# Auth Page Templates Implementation Plan (Plan 2 of 6)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create custom-designed Single Page templates for the 5 auth pages (login, register, forgot password, reset password, email verification) using the Design System molecules from Plan 1.

**Architecture:** Concrete CMS has two layers for auth pages: (1) Single Pages (`login.php`, `register.php`) which are the page-level templates, and (2) Authentication Type views (`authentication/concrete/form.php`, `forgot_password.php`, `change_password.php`) which render the actual login/forgot/reset forms inside the login Single Page. We override Single Pages from the `inno_frontend` package, and auth type views from `application/authentication/concrete/` (the standard CMS override path). All templates use the Design System's `auth-card`, `form-field`, `password-field`, `button`, and `alert` molecules.

**Tech Stack:** PHP 8.3, Concrete CMS 9.4 Single Page system, Design System components (Atomic Design), SCSS with design tokens

**Spec:** `docs/superpowers/specs/2026-04-17-auth-pages-design.md` (Sections 2, 3, 5)

**Depends on:** Plan 1 (Auth Molecules) — `auth-card`, `password-field`, `form-field`, `button`, `alert` must exist.

---

## File Map

### New files

```
public/packages/inno_frontend/
  single_pages/
    login.php                          — wraps auth type form in auth-card
    register.php                       — register form with auth-card

public/application/authentication/concrete/
  form.php                             — login form (username/email + password + remember me)
  forgot_password.php                  — forgot password form
  change_password.php                  — reset password form
  password_sent.php                    — "email sent" success message
  password_changed.php                 — "password changed" success message
  email_validated.php                  — email verification success

resources/css/single_pages/
  login.scss                           — auth page styles
  register.scss                        — register page styles
```

### Modified files

```
public/packages/inno_frontend/controller.php    — register single page overrides in install/upgrade
```

---

## Task 1: Register Single Page Overrides in Package Controller

**Files:**
- Modify: `public/packages/inno_frontend/controller.php`

- [ ] **Step 1: Read the current controller**

Read `public/packages/inno_frontend/controller.php` to understand the current structure.

- [ ] **Step 2: Add the registerSinglePageOverrides method**

Add a new private method and call it from both `install()` and `upgrade()`. The method updates existing CMS Single Pages to resolve their views from the `inno_frontend` package instead of the core.

Add this import at the top of the file (after existing use statements):

```php
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single as SinglePage;
```

Add the method call to both `install()` and `upgrade()`:

```php
public function install(): void
{
    $pkg = parent::install();
    // ... existing install code ...
    $this->registerSinglePageOverrides();
}

public function upgrade(): void
{
    parent::upgrade();
    $this->registerSinglePageOverrides();
}
```

Add the private method:

```php
private function registerSinglePageOverrides(): void
{
    $pages = [
        '/login',
        '/register',
    ];

    foreach ($pages as $path) {
        $page = Page::getByPath($path);
        if ($page && !$page->isError()) {
            $page->update(['pkgID' => $this->getPackageEntity()->getPackageID()]);
        }
    }
}
```

Note: We only register `/login` and `/register` for now. Account and members pages come in Plans 3 and 4.

- [ ] **Step 3: Run PHPStan**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/phpstan analyse --memory-limit=256M
```

Expected: no errors.

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/controller.php
git commit -m "feat(auth): register login and register single page overrides in inno_frontend"
```

---

## Task 2: Login Single Page Template

**Files:**
- Create: `public/packages/inno_frontend/single_pages/login.php`

The login Single Page is a wrapper. The actual login form is rendered by the authentication type via `$auth->renderForm()` (Task 3). This template handles:
- Wrapping everything in `auth-card`
- The "already logged in" state
- The "fill required attributes" state (after login, before access)
- Error display

- [ ] **Step 1: Create the login single page template**

Create `public/packages/inno_frontend/single_pages/login.php`:

```php
<?php

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\User\User $user */
/** @var \Concrete\Core\Authentication\AuthenticationType[]|null $activeAuths */
/** @var \Concrete\Core\Authentication\AuthenticationType|null $authType */
/** @var string|null $authTypeElement */
/** @var array|null $authTypeParams */
/** @var \Concrete\Core\Attribute\Key\Key[]|null $required_attributes */
/** @var bool|null $logoutComplete */
/** @var string|null $logoutMessage */

$form = app('helper/form');
$token = app('helper/validation/token');
$isLoggedIn = isset($user) && $user->isRegistered();

// Determine auth state
$isAttributeMode = isset($required_attributes) && count($required_attributes) > 0;
$isLogoutComplete = isset($logoutComplete) && $logoutComplete;
?>

<?php
$authCardTitle = 'Anmelden';
$authCardSubtitle = 'Willkommen zurueck';

if ($isAttributeMode) {
    $authCardTitle = 'Profil vervollstaendigen';
    $authCardSubtitle = 'Bitte fuelle die folgenden Felder aus.';
} elseif ($isLogoutComplete) {
    $authCardTitle = 'Abgemeldet';
    $authCardSubtitle = '';
}

ob_start();
?>

<?php if ($isLogoutComplete): ?>
    <?php
    $props = [
        'variant' => 'success',
        'title' => '',
        'message' => $logoutMessage ?? t('You have been logged out.'),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';
    ?>

<?php elseif ($isLoggedIn && !$isAttributeMode): ?>
    <?php
    $props = [
        'variant' => 'info',
        'title' => '',
        'message' => t('You are already logged in.'),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';
    ?>

<?php elseif ($isAttributeMode): ?>
    <form method="post" action="<?= $view->action('fill_attributes') ?>">
        <?php
        $attribute_helper = new \Concrete\Core\Legacy\Attribute\Form();
        foreach ($required_attributes as $ak) {
            $attribute_helper->display($ak, true);
        }
        ?>
        <div style="margin-top: var(--space-4);">
            <?php
            $props = [
                'label' => t('Save'),
                'variant' => 'primary',
                'type' => 'submit',
                'fullWidth' => 'true',
            ];
            include DIR_COMPONENTS . '/atoms/button/button.php';
            ?>
        </div>
    </form>

<?php else: ?>
    <?php
    // Display errors if any
    if (isset($error) && is_object($error) && $error->has()) {
        $props = [
            'variant' => 'error',
            'title' => '',
            'message' => implode('<br>', $error->getList()),
            'dismissible' => 'false',
        ];
        include DIR_COMPONENTS . '/molecules/alert/alert.php';
    }

    // Render the active authentication type form
    if (isset($authType) && $authType) {
        $authType->renderForm($authTypeElement ?? 'form', $authTypeParams ?? []);
    } elseif (isset($activeAuths)) {
        foreach ($activeAuths as $auth) {
            $auth->renderForm();
        }
    }
    ?>
<?php endif; ?>

<?php
$content = ob_get_clean();

$props = [
    'title' => $authCardTitle,
    'subtitle' => $authCardSubtitle,
    'showLogo' => 'true',
    'content' => $content,
];
include DIR_COMPONENTS . '/molecules/auth-card/auth-card.php';
?>
```

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/single_pages/login.php
git commit -m "feat(auth): add custom login single page template with auth-card"
```

---

## Task 3: Login Auth Type Form Override

**Files:**
- Create: `public/application/authentication/concrete/form.php`

This overrides the core `authentication/concrete/form.php` — the actual login form with username, password, remember me, and links.

- [ ] **Step 1: Create the auth type form override**

Create `public/application/authentication/concrete/form.php`:

```php
<?php defined('C5_EXECUTE') or die('Access denied.');

$form = Core::make('helper/form');
$dh = Core::make('helper/date');
$token = Core::make('helper/validation/token');

$loggedIn = isset($user) && $user->isRegistered();

if ($loggedIn) {
    $props = [
        'variant' => 'info',
        'title' => '',
        'message' => t('You are already logged in.'),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';
    return;
}
?>

<form class="concrete-login-form" method="post" action="<?= URL::to('/login', 'authenticate', $this->getAuthenticationTypeHandle()) ?>">

    <?php
    $isEmailOnly = Config::get('concrete.user.registration.email_registration');
    $props = [
        'label' => $isEmailOnly ? t('Email Address') : t('User Name'),
        'id' => 'uName',
        'name' => 'uName',
        'type' => $isEmailOnly ? 'email' : 'text',
        'required' => 'true',
        'placeholder' => '',
    ];
    include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
    ?>

    <?php
    $props = [
        'label' => t('Password'),
        'id' => 'uPassword',
        'name' => 'uPassword',
        'required' => 'true',
    ];
    include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
    ?>

    <?php if (Config::get('concrete.session.remember_me.lifetime') > 0): ?>
        <?php
        $props = [
            'id' => 'uMaintainLogin',
            'name' => 'uMaintainLogin',
            'value' => '1',
            'label' => t('Stay signed in for %s', $dh->describeInterval(Config::get('concrete.session.remember_me.lifetime'))),
            'checked' => 'false',
        ];
        include DIR_COMPONENTS . '/atoms/checkbox/checkbox.php';
        ?>
    <?php endif; ?>

    <?php if (isset($locales) && is_array($locales) && count($locales) > 0): ?>
        <?php
        $props = [
            'label' => t('Language'),
            'id' => 'USER_LOCALE',
            'name' => 'USER_LOCALE',
            'type' => 'select',
            'options' => json_encode(array_map(fn($k, $v) => ['value' => $k, 'label' => $v], array_keys($locales), array_values($locales))),
        ];
        include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
        ?>
    <?php endif; ?>

    <?php $token->output('login_' . $this->getAuthenticationTypeHandle()); ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: var(--space-2);">
        <a href="<?= URL::to('/login', 'concrete', 'forgot_password') ?>" style="font-size: var(--text-sm); color: var(--color-action-primary); text-decoration: none;">
            <?= t('Forgot Password?') ?>
        </a>
    </div>

    <div style="margin-top: var(--space-4);">
        <?php
        $props = [
            'label' => t('Sign In'),
            'variant' => 'primary',
            'type' => 'submit',
            'fullWidth' => 'true',
        ];
        include DIR_COMPONENTS . '/atoms/button/button.php';
        ?>
    </div>

    <?php if (Config::get('concrete.user.registration.enabled')): ?>
        <div style="text-align: center; margin-top: var(--space-4); font-size: var(--text-sm); color: var(--color-text-secondary);">
            <?= t("Don't have an account?") ?>
            <a href="<?= URL::to('/register') ?>" style="color: var(--color-action-primary); text-decoration: none; font-weight: var(--font-weight-medium);">
                <?= t('Sign up') ?>
            </a>
        </div>
    <?php endif; ?>
</form>
```

- [ ] **Step 2: Commit**

```bash
git add public/application/authentication/concrete/form.php
git commit -m "feat(auth): override login form with design system components"
```

---

## Task 4: Forgot Password & Password Reset Templates

**Files:**
- Create: `public/application/authentication/concrete/forgot_password.php`
- Create: `public/application/authentication/concrete/change_password.php`
- Create: `public/application/authentication/concrete/password_sent.php`
- Create: `public/application/authentication/concrete/password_changed.php`

- [ ] **Step 1: Create forgot_password.php**

Create `public/application/authentication/concrete/forgot_password.php`:

```php
<?php defined('C5_EXECUTE') or die('Access denied.');

$token = Core::make('helper/validation/token');
?>

<?php
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
        <?= t('Enter your email address below. We will send you instructions to reset your password.') ?>
    </p>

    <?php
    $props = [
        'label' => t('Email Address'),
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
            'label' => t('Reset Password'),
            'variant' => 'primary',
            'type' => 'submit',
            'fullWidth' => 'true',
        ];
        include DIR_COMPONENTS . '/atoms/button/button.php';
        ?>
    </div>

    <div style="text-align: center; margin-top: var(--space-4); font-size: var(--text-sm);">
        <a href="<?= URL::to('/login') ?>" style="color: var(--color-action-primary); text-decoration: none;">
            <?= t('Back to Login') ?>
        </a>
    </div>
</form>
```

- [ ] **Step 2: Create change_password.php**

Create `public/application/authentication/concrete/change_password.php`:

```php
<?php defined('C5_EXECUTE') or die('Access denied.');

$_error = [];
if (isset($error)) {
    if ($error instanceof Exception) {
        $_error[] = $error->getMessage();
    } elseif ($error instanceof \Concrete\Core\Error\ErrorList\ErrorList) {
        if ($error->has()) {
            $_error = $error->getList();
        }
    } elseif (is_array($error)) {
        $_error = $error;
    }
}

if (!empty($_error)) {
    $props = [
        'variant' => 'error',
        'title' => '',
        'message' => implode('<br>', array_map('htmlspecialchars', $_error)),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';
}
?>

<p style="font-size: var(--text-sm); color: var(--color-text-secondary); margin-bottom: var(--space-4);">
    <?= t('Enter your new password below.') ?>
</p>

<form method="post" action="<?= URL::to('/login', 'callback', $authType->getAuthenticationTypeHandle(), 'change_password', $uHash) ?>">

    <?php
    $props = [
        'label' => t('New Password'),
        'id' => 'uPassword',
        'name' => 'uPassword',
        'required' => 'true',
        'showStrength' => 'true',
    ];
    include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
    ?>

    <?php
    $props = [
        'label' => t('Confirm New Password'),
        'id' => 'uPasswordConfirm',
        'name' => 'uPasswordConfirm',
        'required' => 'true',
    ];
    include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
    ?>

    <div style="margin-top: var(--space-4);">
        <?php
        $props = [
            'label' => t('Change Password'),
            'variant' => 'primary',
            'type' => 'submit',
            'fullWidth' => 'true',
        ];
        include DIR_COMPONENTS . '/atoms/button/button.php';
        ?>
    </div>
</form>
```

- [ ] **Step 3: Create password_sent.php**

Create `public/application/authentication/concrete/password_sent.php`:

```php
<?php defined('C5_EXECUTE') or die('Access denied.'); ?>

<?php
$props = [
    'variant' => 'success',
    'title' => t('Email Sent'),
    'message' => t('We have sent you an email with instructions to reset your password.'),
    'dismissible' => 'false',
];
include DIR_COMPONENTS . '/molecules/alert/alert.php';
?>

<div style="text-align: center; margin-top: var(--space-4); font-size: var(--text-sm);">
    <a href="<?= URL::to('/login') ?>" style="color: var(--color-action-primary); text-decoration: none;">
        <?= t('Back to Login') ?>
    </a>
</div>
```

- [ ] **Step 4: Create password_changed.php**

Create `public/application/authentication/concrete/password_changed.php`:

```php
<?php defined('C5_EXECUTE') or die('Access denied.'); ?>

<?php
$props = [
    'variant' => 'success',
    'title' => t('Password Changed'),
    'message' => t('Your password has been changed successfully. You can now sign in with your new password.'),
    'dismissible' => 'false',
];
include DIR_COMPONENTS . '/molecules/alert/alert.php';
?>

<div style="text-align: center; margin-top: var(--space-4); font-size: var(--text-sm);">
    <a href="<?= URL::to('/login') ?>" style="color: var(--color-action-primary); text-decoration: none;">
        <?= t('Sign In') ?>
    </a>
</div>
```

- [ ] **Step 5: Commit**

```bash
git add public/application/authentication/concrete/
git commit -m "feat(auth): add forgot password, reset password, and success message templates"
```

---

## Task 5: Email Validated Template

**Files:**
- Create: `public/application/authentication/concrete/email_validated.php`

- [ ] **Step 1: Create email_validated.php**

Create `public/application/authentication/concrete/email_validated.php`:

```php
<?php defined('C5_EXECUTE') or die('Access denied.'); ?>

<?php
$props = [
    'variant' => 'success',
    'title' => t('Email Verified'),
    'message' => t('Your email address has been verified. You can now sign in.'),
    'dismissible' => 'false',
];
include DIR_COMPONENTS . '/molecules/alert/alert.php';
?>

<div style="text-align: center; margin-top: var(--space-4); font-size: var(--text-sm);">
    <a href="<?= URL::to('/login') ?>" style="color: var(--color-action-primary); text-decoration: none;">
        <?= t('Sign In') ?>
    </a>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add public/application/authentication/concrete/email_validated.php
git commit -m "feat(auth): add email verified success template"
```

---

## Task 6: Register Single Page Template

**Files:**
- Create: `public/packages/inno_frontend/single_pages/register.php`

- [ ] **Step 1: Create register.php**

Create `public/packages/inno_frontend/single_pages/register.php`:

```php
<?php

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var bool $displayUserName */
/** @var array $attributeSets */
/** @var array $unassignedAttributes */
/** @var \Concrete\Core\Attribute\Form\Renderer $renderer */
/** @var string|null $registerSuccess */
/** @var string|array|null $successMsg */

$hasSuccess = isset($registerSuccess);
?>

<?php
// Determine card title based on state
if ($hasSuccess) {
    switch ($registerSuccess) {
        case 'validate':
            $cardTitle = t('Check Your Email');
            $cardSubtitle = '';
            break;
        case 'pending':
            $cardTitle = t('Registration Pending');
            $cardSubtitle = '';
            break;
        default:
            $cardTitle = t('Welcome!');
            $cardSubtitle = '';
    }
} else {
    $cardTitle = t('Create Account');
    $cardSubtitle = t('Sign up to participate');
}

ob_start();
?>

<?php if ($hasSuccess): ?>
    <?php
    $messages = is_array($successMsg) ? $successMsg : [$successMsg];
    $props = [
        'variant' => 'success',
        'title' => '',
        'message' => implode('<br>', $messages),
        'dismissible' => 'false',
    ];
    include DIR_COMPONENTS . '/molecules/alert/alert.php';
    ?>

    <div style="text-align: center; margin-top: var(--space-4); font-size: var(--text-sm);">
        <a href="<?= URL::to('/login') ?>" style="color: var(--color-action-primary); text-decoration: none;">
            <?= t('Go to Login') ?>
        </a>
    </div>

<?php else: ?>
    <?php
    // Display errors if any
    if (isset($error) && is_object($error) && $error->has()) {
        $props = [
            'variant' => 'error',
            'title' => '',
            'message' => implode('<br>', $error->getList()),
            'dismissible' => 'false',
        ];
        include DIR_COMPONENTS . '/molecules/alert/alert.php';
    }
    ?>

    <form method="post" action="<?= $view->url('/register', 'do_register') ?>">
        <?php $token->output('register.do_register'); ?>

        <?php if ($displayUserName): ?>
            <?php
            $props = [
                'label' => t('Username'),
                'id' => 'uName',
                'name' => 'uName',
                'type' => 'text',
                'value' => $form->getRequestValue('uName') ?? '',
                'required' => 'true',
            ];
            include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
            ?>
        <?php endif; ?>

        <?php
        $props = [
            'label' => t('Email Address'),
            'id' => 'uEmail',
            'name' => 'uEmail',
            'type' => 'email',
            'value' => $form->getRequestValue('uEmail') ?? '',
            'required' => 'true',
        ];
        include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
        ?>

        <?php
        $props = [
            'label' => t('Password'),
            'id' => 'uPassword',
            'name' => 'uPassword',
            'required' => 'true',
            'showStrength' => 'true',
            'hint' => t('Minimum %s characters.', Config::get('concrete.user.password.minimum', 5)),
        ];
        include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
        ?>

        <?php if (Config::get('concrete.user.registration.display_confirm_password_field')): ?>
            <?php
            $props = [
                'label' => t('Confirm Password'),
                'id' => 'uPasswordConfirm',
                'name' => 'uPasswordConfirm',
                'required' => 'true',
            ];
            include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
            ?>
        <?php endif; ?>

        <?php
        // Render custom user attributes
        if (count($attributeSets) > 0) {
            foreach ($attributeSets as $setName => $attributes) {
                foreach ($attributes as $ak) {
                    $renderer->buildView($ak)->render();
                }
            }
        }
        foreach ($unassignedAttributes as $ak) {
            $renderer->buildView($ak)->render();
        }
        ?>

        <?php
        // CAPTCHA
        $captcha = Loader::helper('validation/captcha');
        if (Config::get('concrete.user.registration.captcha')) {
            echo '<div style="margin-top: var(--space-4);">';
            $captcha->display();
            echo '</div>';
        }
        ?>

        <?php if (isset($rcID) && $rcID): ?>
            <input type="hidden" name="rcID" value="<?= (int) $rcID ?>" />
        <?php endif; ?>

        <div style="margin-top: var(--space-4);">
            <?php
            $props = [
                'label' => t('Create Account'),
                'variant' => 'primary',
                'type' => 'submit',
                'fullWidth' => 'true',
            ];
            include DIR_COMPONENTS . '/atoms/button/button.php';
            ?>
        </div>

        <div style="text-align: center; margin-top: var(--space-4); font-size: var(--text-sm); color: var(--color-text-secondary);">
            <?= t('Already have an account?') ?>
            <a href="<?= URL::to('/login') ?>" style="color: var(--color-action-primary); text-decoration: none; font-weight: var(--font-weight-medium);">
                <?= t('Sign in') ?>
            </a>
        </div>
    </form>
<?php endif; ?>

<?php
$content = ob_get_clean();

$props = [
    'title' => $cardTitle,
    'subtitle' => $cardSubtitle,
    'showLogo' => 'true',
    'content' => $content,
];
include DIR_COMPONENTS . '/molecules/auth-card/auth-card.php';
?>
```

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/single_pages/register.php
git commit -m "feat(auth): add custom register single page template with auth-card"
```

---

## Task 7: Single Page SCSS

**Files:**
- Create: `resources/css/single_pages/login.scss`
- Create: `resources/css/single_pages/register.scss`

- [ ] **Step 1: Create login.scss**

Create `resources/css/single_pages/login.scss`:

```scss
// Login page: auth-card styles are in the component SCSS.
// This file handles login-specific overrides and the concrete-login-form wrapper.

.concrete-login-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}
```

- [ ] **Step 2: Create register.scss**

Create `resources/css/single_pages/register.scss`:

```scss
// Register page: auth-card styles are in the component SCSS.
// This file handles register-specific overrides.

// CMS attribute rendering may output unstyled elements — normalize them.
.ccm-attribute {
  margin-bottom: var(--space-4);
}
```

- [ ] **Step 3: Build to verify SCSS compiles**

```bash
npm run development
```

Expected: webpack compiles both files to `themes/innovationsbox/css/single_pages/login.css` and `register.css`.

- [ ] **Step 4: Commit**

```bash
git add resources/css/single_pages/login.scss resources/css/single_pages/register.scss
git commit -m "feat(auth): add login and register single page SCSS"
```

---

## Task 8: Verification

- [ ] **Step 1: Build frontend**

```bash
npm run development
```

Expected: no errors.

- [ ] **Step 2: Run PHPStan**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/phpstan analyse --memory-limit=256M
```

Expected: no errors.

- [ ] **Step 3: Run unit tests**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/pest --testsuite=Unit
```

Expected: 76 tests pass.

- [ ] **Step 4: Verify files are in place**

```bash
ls -la public/packages/inno_frontend/single_pages/
ls -la public/application/authentication/concrete/
ls -la resources/css/single_pages/
```

Expected: all files present.

- [ ] **Step 5: Final commit if any fixes needed**

```bash
git status
git add -A && git commit -m "fix: resolve build issues" || echo "Nothing to commit"
```
