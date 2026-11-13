# Profile & Account Pages Implementation Plan (Plan 3 of 6)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create custom-designed edit profile and avatar pages using Design System components, and implement the forced profile completion redirect for users with missing required attributes.

**Architecture:** Override CMS Single Pages from `inno_frontend` package for `/account/edit_profile` and `/account/avatar`. Profile completion redirect registered as an `on_before_render` listener in `inno_shared/controller.php`. Templates use standard site layout (not auth-card — these are account management pages).

**Tech Stack:** PHP 8.3, Concrete CMS 9.4, Design System components, SCSS with design tokens

**Spec:** `docs/superpowers/specs/2026-04-17-auth-pages-design.md` (Sections 2, 6)

**Depends on:** Plan 1 (molecules: `password-field`, `avatar-upload`, `alert`, `form-field`, `button`)

---

## Task 1: Update Package Controller for Account Pages

**Files:**
- Modify: `public/packages/inno_frontend/controller.php`

- [ ] **Step 1: Add account pages to registerSinglePageOverrides**

In `public/packages/inno_frontend/controller.php`, find the `registerSinglePageOverrides()` method. Update the `$pages` array to include account pages:

Change:
```php
$pages = [
    '/login',
    '/register',
];
```

To:
```php
$pages = [
    '/login',
    '/register',
    '/account',
    '/account/edit_profile',
    '/account/avatar',
];
```

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/controller.php
git commit -m "feat(auth): register account single page overrides in inno_frontend"
```

---

## Task 2: Edit Profile Template

**Files:**
- Create: `public/packages/inno_frontend/single_pages/account/edit_profile.php`
- Create: `resources/css/single_pages/edit-profile.scss`

- [ ] **Step 1: Create the edit profile template**

Create `public/packages/inno_frontend/single_pages/account/edit_profile.php`:

```php
<?php

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\User\UserInfo $profile */
/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var bool $displayUserName */
/** @var array $attributeSets */
/** @var array $unassignedAttributes */
/** @var \Concrete\Core\Attribute\Form\Renderer $profileFormRenderer */

$config = app('config');
$date = app('helper/date');
$isCompletionMode = isset($_GET['complete']) && $_GET['complete'] === '1';
?>

<div class="edit-profile">
    <div class="lb-container lb-container--small">

        <h1 class="edit-profile__title"><?= t('Edit Profile') ?></h1>

        <?php if ($isCompletionMode): ?>
            <?php
            $props = [
                'variant' => 'info',
                'title' => '',
                'message' => t('Please complete your profile to use the platform.'),
                'dismissible' => 'false',
            ];
            include DIR_COMPONENTS . '/molecules/alert/alert.php';
            ?>
        <?php endif; ?>

        <?php if (isset($success) && $success): ?>
            <?php
            $props = [
                'variant' => 'success',
                'title' => '',
                'message' => $success,
                'dismissible' => 'true',
            ];
            include DIR_COMPONENTS . '/molecules/alert/alert.php';
            ?>
        <?php endif; ?>

        <?php if (isset($error) && is_object($error) && $error->has()): ?>
            <?php
            $props = [
                'variant' => 'error',
                'title' => '',
                'message' => implode('<br>', $error->getList()),
                'dismissible' => 'false',
            ];
            include DIR_COMPONENTS . '/molecules/alert/alert.php';
            ?>
        <?php endif; ?>

        <form method="post" action="<?= $view->action('save') ?>" enctype="multipart/form-data">
            <?php $token->output('profile_edit'); ?>

            <section class="edit-profile__section">
                <h2 class="edit-profile__section-title"><?= t('Personal Information') ?></h2>

                <?php if ($displayUserName): ?>
                    <?php
                    $props = [
                        'label' => t('Username'),
                        'id' => 'uName',
                        'name' => 'uName',
                        'type' => 'text',
                        'value' => $profile->getUserName(),
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
                    'value' => $profile->getUserEmail(),
                    'required' => 'true',
                ];
                include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
                ?>

                <?php if ($config->get('concrete.misc.user_timezones')): ?>
                    <?php
                    $timezones = $date->getTimezones();
                    $props = [
                        'label' => t('Time Zone'),
                        'id' => 'uTimezone',
                        'name' => 'uTimezone',
                        'type' => 'select',
                        'value' => $profile->getUserTimezone(),
                        'options' => json_encode(array_map(fn($k, $v) => ['value' => $k, 'label' => $v], array_keys($timezones), array_values($timezones))),
                    ];
                    include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
                    ?>
                <?php endif; ?>

                <?php if (isset($locales) && is_array($locales) && count($locales) > 1): ?>
                    <?php
                    $props = [
                        'label' => t('Language'),
                        'id' => 'uDefaultLanguage',
                        'name' => 'uDefaultLanguage',
                        'type' => 'select',
                        'value' => \Localization::activeLocale(),
                        'options' => json_encode(array_map(fn($k, $v) => ['value' => $k, 'label' => $v], array_keys($locales), array_values($locales))),
                    ];
                    include DIR_COMPONENTS . '/molecules/form-field/form-field.php';
                    ?>
                <?php endif; ?>
            </section>

            <?php if (count($attributeSets) > 0 || count($unassignedAttributes) > 0): ?>
                <section class="edit-profile__section">
                    <h2 class="edit-profile__section-title"><?= t('Additional Information') ?></h2>

                    <?php
                    foreach ($attributeSets as $setName => $attributes) {
                        if ($setName) {
                            echo '<h3 class="edit-profile__set-title">' . htmlspecialchars($setName) . '</h3>';
                        }
                        foreach ($attributes as $ak) {
                            echo '<div class="edit-profile__attribute">';
                            $profileFormRenderer->buildView($ak)->render();
                            echo '</div>';
                        }
                    }
                    foreach ($unassignedAttributes as $ak) {
                        echo '<div class="edit-profile__attribute">';
                        $profileFormRenderer->buildView($ak)->render();
                        echo '</div>';
                    }
                    ?>
                </section>
            <?php endif; ?>

            <section class="edit-profile__section">
                <h2 class="edit-profile__section-title"><?= t('Change Password') ?></h2>
                <p class="edit-profile__hint"><?= t('Leave blank to keep your current password.') ?></p>

                <?php
                $props = [
                    'label' => t('Current Password'),
                    'id' => 'uPasswordCurrent',
                    'name' => 'uPasswordCurrent',
                ];
                include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
                ?>

                <?php
                $props = [
                    'label' => t('New Password'),
                    'id' => 'uPasswordNew',
                    'name' => 'uPasswordNew',
                    'showStrength' => 'true',
                ];
                include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
                ?>

                <?php
                $props = [
                    'label' => t('Confirm New Password'),
                    'id' => 'uPasswordNewConfirm',
                    'name' => 'uPasswordNewConfirm',
                ];
                include DIR_COMPONENTS . '/molecules/password-field/password-field.php';
                ?>
            </section>

            <div class="edit-profile__actions">
                <?php
                $props = [
                    'label' => t('Save Profile'),
                    'variant' => 'primary',
                    'type' => 'submit',
                ];
                include DIR_COMPONENTS . '/atoms/button/button.php';
                ?>
            </div>
        </form>
    </div>
</div>
```

- [ ] **Step 2: Create edit-profile.scss**

Create `resources/css/single_pages/edit-profile.scss`:

```scss
.edit-profile {
  padding: var(--space-8) 0;

  &__title {
    font-size: var(--text-2xl);
    font-weight: var(--font-weight-semibold);
    margin-bottom: var(--space-6);
  }

  &__section {
    margin-bottom: var(--space-8);
    padding-bottom: var(--space-8);
    border-bottom: 1px solid var(--color-border-secondary);

    &:last-of-type {
      border-bottom: none;
    }
  }

  &__section-title {
    font-size: var(--text-lg);
    font-weight: var(--font-weight-medium);
    margin-bottom: var(--space-4);
  }

  &__set-title {
    font-size: var(--text-base);
    font-weight: var(--font-weight-medium);
    margin: var(--space-4) 0 var(--space-2);
  }

  &__hint {
    font-size: var(--text-sm);
    color: var(--color-text-secondary);
    margin-bottom: var(--space-4);
  }

  &__attribute {
    margin-bottom: var(--space-4);
  }

  &__actions {
    margin-top: var(--space-6);
  }

  .form-field,
  .password-field {
    margin-bottom: var(--space-4);
  }
}
```

- [ ] **Step 3: Build**

```bash
npm run development
```

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/single_pages/account/edit_profile.php \
  resources/css/single_pages/edit-profile.scss
git commit -m "feat(auth): add custom edit profile single page template"
```

---

## Task 3: Avatar Template

**Files:**
- Create: `public/packages/inno_frontend/single_pages/account/avatar.php`
- Create: `resources/css/single_pages/avatar.scss`

- [ ] **Step 1: Create avatar template**

Create `public/packages/inno_frontend/single_pages/account/avatar.php`:

```php
<?php

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\User\UserInfo $profile */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var object $avatarCropperInstance */
?>

<div class="avatar-page">
    <div class="lb-container lb-container--small">

        <h1 class="avatar-page__title"><?= t('Profile Picture') ?></h1>

        <?php if (isset($success) && $success): ?>
            <?php
            $props = [
                'variant' => 'success',
                'title' => '',
                'message' => $success,
                'dismissible' => 'true',
            ];
            include DIR_COMPONENTS . '/molecules/alert/alert.php';
            ?>
        <?php endif; ?>

        <?php if (isset($error) && is_object($error) && $error->has()): ?>
            <?php
            $props = [
                'variant' => 'error',
                'title' => '',
                'message' => implode('<br>', $error->getList()),
                'dismissible' => 'false',
            ];
            include DIR_COMPONENTS . '/molecules/alert/alert.php';
            ?>
        <?php endif; ?>

        <?php
        $hasAvatar = $profile->hasAvatar();
        $avatarUrl = $hasAvatar ? $profile->getUserAvatar()->getPath() : '';

        $props = [
            'hasAvatar' => $hasAvatar ? 'true' : 'false',
            'avatarUrl' => $avatarUrl,
            'userName' => $profile->getUserName(),
            'cropperHtml' => (string) $avatarCropperInstance->getTag(),
        ];
        include DIR_COMPONENTS . '/molecules/avatar-upload/avatar-upload.php';
        ?>

        <?php if ($hasAvatar): ?>
            <form method="post" action="<?= $view->action('delete') ?>" style="margin-top: var(--space-4);">
                <?php $token->output('delete_avatar'); ?>
                <?php
                $props = [
                    'label' => t('Remove Picture'),
                    'variant' => 'danger',
                    'size' => 'sm',
                    'type' => 'submit',
                ];
                include DIR_COMPONENTS . '/atoms/button/button.php';
                ?>
            </form>
        <?php endif; ?>

        <div style="margin-top: var(--space-6);">
            <a href="<?= URL::to('/account/edit_profile') ?>" style="font-size: var(--text-sm); color: var(--color-action-primary); text-decoration: none;">
                &larr; <?= t('Back to Edit Profile') ?>
            </a>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Create avatar.scss**

Create `resources/css/single_pages/avatar.scss`:

```scss
.avatar-page {
  padding: var(--space-8) 0;

  &__title {
    font-size: var(--text-2xl);
    font-weight: var(--font-weight-semibold);
    margin-bottom: var(--space-6);
  }
}
```

- [ ] **Step 3: Build**

```bash
npm run development
```

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/single_pages/account/avatar.php \
  resources/css/single_pages/avatar.scss
git commit -m "feat(auth): add custom avatar single page template"
```

---

## Task 4: Account Dashboard Template

**Files:**
- Create: `public/packages/inno_frontend/single_pages/account/view.php`

- [ ] **Step 1: Create account dashboard**

The core account view is empty — create a simple redirect/landing page.

Create `public/packages/inno_frontend/single_pages/account/view.php`:

```php
<?php

defined('C5_EXECUTE') or die('Access Denied.');

// Redirect to edit profile — the account dashboard is not a standalone page
$response = Redirect::to('/account/edit_profile');
$response->send();
exit;
```

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/single_pages/account/view.php
git commit -m "feat(auth): add account dashboard redirect to edit profile"
```

---

## Task 5: Forced Profile Completion Redirect

**Files:**
- Modify: `public/packages/inno_shared/controller.php`

- [ ] **Step 1: Add profile completion redirect listener**

Read `public/packages/inno_shared/controller.php`. In the `on_start()` method, after the `$this->registerExceptionHandler();` line and before `$this->registerDocsRoutes();`, add:

```php
        // --- Profile completion redirect ---
        $this->registerProfileCompletionCheck();
```

Then add the new private method:

```php
    private function registerProfileCompletionCheck(): void
    {
        Events::addListener('on_before_render', function () {
            $user = $this->app->make(\Concrete\Core\User\User::class);
            if (!$user->isRegistered()) {
                return;
            }

            $request = \Concrete\Core\Http\Request::getInstance();
            $path = $request->getPathInfo();

            // Skip allowed paths during incomplete profile
            $allowedPrefixes = ['/account/', '/login', '/ccm/', '/api/'];
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return;
                }
            }
            if ($path === '/logout') {
                return;
            }

            // Check if any required-on-profile attributes are empty
            $ui = \Concrete\Core\User\UserInfo::getByID($user->getUserID());
            if (!$ui) {
                return;
            }

            $keys = \Concrete\Core\Attribute\Key\UserKey::getEditableInProfileList();
            foreach ($keys as $key) {
                if ($key->isAttributeKeyRequiredOnProfile()) {
                    $value = $ui->getAttributeValueObject($key);
                    if (!$value || (string) $value->getDisplayValue() === '') {
                        // Store intended destination
                        $session = $this->app->make(\Symfony\Component\HttpFoundation\Session\Session::class);
                        $session->set('profile_completion_redirect', $path);

                        $response = \Concrete\Core\Http\ResponseFactory::redirect('/account/edit_profile?complete=1', 302);
                        $response->send();
                        exit;
                    }
                }
            }
        });
    }
```

- [ ] **Step 2: Run PHPStan**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/phpstan analyse --memory-limit=256M
```

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_shared/controller.php
git commit -m "feat(auth): add forced profile completion redirect for users with missing required attributes"
```

---

## Task 6: Verification

- [ ] **Step 1: Build frontend**

```bash
npm run development
```

Expected: no errors. Verify `edit-profile.css` and `avatar.css` exist in `themes/innovationsbox/css/single_pages/`.

- [ ] **Step 2: Run PHPStan**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/phpstan analyse --memory-limit=256M
```

- [ ] **Step 3: Run unit tests**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/pest --testsuite=Unit
```

Expected: 76 tests pass.

- [ ] **Step 4: Verify all files**

```bash
ls -la public/packages/inno_frontend/single_pages/account/
ls -la resources/css/single_pages/
```
