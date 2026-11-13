# Auth Molecules Implementation Plan (Plan 1 of 6)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build 5 new Design System molecules needed by the auth/profile page templates: `password-field`, `auth-card`, `user-menu`, `avatar-upload`, `member-card` — each with Storybook stories.

**Architecture:** Each molecule follows the project's component pattern: `X.php` (template with `$props`), `X.variants.php` (Storybook defaults), `X.stories.json` (Storybook config), `X.scss` (BEM styles). Components include sub-components via `DIR_COMPONENTS` path. Booleans passed as `'true'`/`'false'` strings. Interactive components get a `.ts` file imported in the Storybook aggregator.

**Tech Stack:** PHP 8.3, SCSS with BEM + design tokens, TypeScript, Storybook (server-rendered PHP stories)

**Spec:** `docs/superpowers/specs/2026-04-17-auth-pages-design.md` (Section 4: New Components)

---

## File Map

### New files (22 total)

```
public/packages/inno_frontend/components/
  molecules/
    password-field/
      password-field.php              — template: form-field wrapper + toggle + strength bar
      password-field.variants.php     — Storybook defaults
      password-field.stories.json     — 7 stories
      password-field.scss             — BEM styles for toggle + strength indicator
    auth-card/
      auth-card.php                   — branded card wrapper: background + card + logo
      auth-card.variants.php          — Storybook defaults
      auth-card.stories.json          — 3 stories
      auth-card.scss                  — gradient background, centered card, responsive
    user-menu/
      user-menu.php                   — avatar trigger + dropdown
      user-menu.variants.php          — Storybook defaults
      user-menu.stories.json          — 4 stories
      user-menu.scss                  — trigger, dropdown panel, items
      user-menu.ts                    — toggle open/close, keyboard, focus trap
    avatar-upload/
      avatar-upload.php               — preview + upload/delete controls
      avatar-upload.variants.php      — Storybook defaults
      avatar-upload.stories.json      — 2 stories
      avatar-upload.scss              — large preview, controls layout
    member-card/
      member-card.php                 — avatar + name + date + badge, links to profile
      member-card.variants.php        — Storybook defaults
      member-card.stories.json        — 3 stories
      member-card.scss                — card layout with avatar
  _fixtures/
    auth-data.php                     — shared sample data for auth stories
```

### Modified files (2)

```
public/packages/inno_frontend/components/_storybook/components.scss  — add 5 @use imports
public/packages/inno_frontend/components/_storybook/components.ts    — add user-menu import
```

---

## Task 1: Auth Fixtures

**Files:**
- Create: `public/packages/inno_frontend/components/_fixtures/auth-data.php`

- [ ] **Step 1: Create the shared auth fixtures file**

Create `public/packages/inno_frontend/components/_fixtures/auth-data.php`:

```php
<?php

declare(strict_types=1);

return [
    'user' => [
        'name' => 'Marco Kuoni',
        'email' => 'marco@innovationsbox.ch',
        'initials' => 'MK',
        'avatarUrl' => 'https://i.pravatar.cc/150?u=marco',
        'role' => 'Initiator',
        'memberSince' => '12. Januar 2026',
        'profileUrl' => '/members/profile/marco',
    ],
    'errors' => [
        'login' => 'Benutzername oder Passwort ist falsch.',
        'email' => 'Bitte eine gueltige E-Mail eingeben.',
        'password' => 'Passwort muss mindestens 5 Zeichen lang sein.',
        'passwordMismatch' => 'Passwoerter stimmen nicht ueberein.',
        'required' => 'Dieses Feld ist erforderlich.',
    ],
    'messages' => [
        'emailSent' => 'Eine E-Mail mit Anweisungen wurde an deine Adresse gesendet.',
        'emailVerified' => 'Deine E-Mail-Adresse wurde bestaetigt. Du kannst dich jetzt anmelden.',
        'profileSaved' => 'Dein Profil wurde erfolgreich gespeichert.',
        'completeProfile' => 'Bitte vervollstaendige dein Profil, um die Plattform nutzen zu koennen.',
    ],
];
```

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/components/_fixtures/auth-data.php
git commit -m "feat(design-system): add shared auth fixture data for Storybook"
```

---

## Task 2: Password Field Molecule

**Files:**
- Create: `public/packages/inno_frontend/components/molecules/password-field/password-field.php`
- Create: `public/packages/inno_frontend/components/molecules/password-field/password-field.variants.php`
- Create: `public/packages/inno_frontend/components/molecules/password-field/password-field.stories.json`
- Create: `public/packages/inno_frontend/components/molecules/password-field/password-field.scss`

- [ ] **Step 1: Create password-field.php**

Create `public/packages/inno_frontend/components/molecules/password-field/password-field.php`:

```php
<?php
$label = $props['label'] ?? 'Passwort';
$id = $props['id'] ?? 'password';
$name = $props['name'] ?? $id;
$value = $props['value'] ?? '';
$placeholder = $props['placeholder'] ?? '';
$hint = $props['hint'] ?? '';
$error = $props['error'] ?? '';
$required = ($props['required'] ?? 'false') === 'true';
$disabled = ($props['disabled'] ?? 'false') === 'true';
$showStrength = ($props['showStrength'] ?? 'false') === 'true';

$hasError = $error !== '';
$hintId = $id . '-hint';
$describedBy = $hasError ? $hintId : ($hint !== '' ? $hintId : '');

$fieldClasses = ['password-field'];
if ($hasError) {
    $fieldClasses[] = 'password-field--error';
}
if ($disabled) {
    $fieldClasses[] = 'password-field--disabled';
}
?>
<div class="<?= implode(' ', $fieldClasses) ?>" data-password-field>
    <?php
    $props = [
        'text' => $label,
        'for' => $id,
        'required' => $required ? 'true' : 'false',
        'disabled' => $disabled ? 'true' : 'false',
    ];
    include DIR_COMPONENTS . '/atoms/label/label.php';
    ?>

    <div class="password-field__input-wrapper">
        <?php
        $props = [
            'id' => $id,
            'name' => $name,
            'type' => 'password',
            'value' => $value,
            'placeholder' => $placeholder,
            'disabled' => $disabled ? 'true' : 'false',
            'required' => $required ? 'true' : 'false',
            'invalid' => $hasError ? 'true' : 'false',
            'describedBy' => $describedBy,
            'size' => 'md',
        ];
        include DIR_COMPONENTS . '/atoms/input/input.php';
        ?>
        <button
            type="button"
            class="password-field__toggle"
            aria-label="Passwort anzeigen"
            data-password-toggle
            <?= $disabled ? 'disabled' : '' ?>
        >
            <svg class="password-field__icon password-field__icon--show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="password-field__icon password-field__icon--hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" hidden>
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
        </button>
    </div>

    <?php if ($showStrength): ?>
        <div class="password-field__strength" data-password-strength aria-live="polite">
            <div class="password-field__strength-bar">
                <div class="password-field__strength-fill" data-password-strength-fill></div>
            </div>
            <span class="password-field__strength-label" data-password-strength-label></span>
        </div>
    <?php endif; ?>

    <?php if ($hasError): ?>
        <span id="<?= htmlspecialchars($hintId) ?>" class="caption caption--error"><?= htmlspecialchars($error) ?></span>
    <?php elseif ($hint !== ''): ?>
        <span id="<?= htmlspecialchars($hintId) ?>" class="caption caption--muted"><?= htmlspecialchars($hint) ?></span>
    <?php endif; ?>
</div>
```

- [ ] **Step 2: Create password-field.variants.php**

Create `public/packages/inno_frontend/components/molecules/password-field/password-field.variants.php`:

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'label' => 'Passwort',
        'id' => 'password',
        'name' => 'password',
        'value' => '',
        'placeholder' => '',
        'hint' => 'Mindestens 5 Zeichen.',
        'error' => '',
        'required' => 'false',
        'disabled' => 'false',
        'showStrength' => 'false',
    ],
];
```

- [ ] **Step 3: Create password-field.stories.json**

Create `public/packages/inno_frontend/components/molecules/password-field/password-field.stories.json`:

```json
{
  "title": "Molecules/Password Field",
  "argTypes": {
    "label": { "control": "text" },
    "id": { "control": "text" },
    "name": { "control": "text" },
    "value": { "control": "text" },
    "placeholder": { "control": "text" },
    "hint": { "control": "text" },
    "error": { "control": "text" },
    "required": { "control": { "type": "select" }, "options": ["true", "false"] },
    "disabled": { "control": { "type": "select" }, "options": ["true", "false"] },
    "showStrength": { "control": { "type": "select" }, "options": ["true", "false"] }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/password-field" } } },
    {
      "name": "With Value",
      "args": { "value": "secret123" },
      "parameters": { "server": { "id": "molecules/password-field" } }
    },
    {
      "name": "With Error",
      "args": { "error": "Passwort muss mindestens 5 Zeichen lang sein.", "value": "abc" },
      "parameters": { "server": { "id": "molecules/password-field" } }
    },
    {
      "name": "With Strength Indicator",
      "args": { "showStrength": "true", "value": "MyStr0ng!" },
      "parameters": { "server": { "id": "molecules/password-field" } }
    },
    {
      "name": "Required",
      "args": { "required": "true" },
      "parameters": { "server": { "id": "molecules/password-field" } }
    },
    {
      "name": "Disabled",
      "args": { "disabled": "true", "value": "locked" },
      "parameters": { "server": { "id": "molecules/password-field" } }
    },
    {
      "name": "Confirm Password",
      "args": { "label": "Passwort bestaetigen", "id": "password-confirm", "name": "uPasswordConfirm", "hint": "" },
      "parameters": { "server": { "id": "molecules/password-field" } }
    }
  ]
}
```

- [ ] **Step 4: Create password-field.scss**

Create `public/packages/inno_frontend/components/molecules/password-field/password-field.scss`:

```scss
.password-field {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);

  &__input-wrapper {
    position: relative;
    display: flex;
    align-items: center;

    .input {
      width: 100%;
      padding-right: var(--space-10);
    }
  }

  &__toggle {
    position: absolute;
    right: var(--space-2);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    padding: 0;
    border: none;
    background: none;
    color: var(--color-neutral-500);
    cursor: pointer;
    border-radius: var(--radius-sm);

    &:hover {
      color: var(--color-neutral-700);
    }

    &:focus-visible {
      outline: 2px solid var(--color-action-focus);
      outline-offset: -2px;
    }

    &:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  }

  &__icon--hide {
    display: none;
  }

  &--visible &__icon--show {
    display: none;
  }

  &--visible &__icon--hide {
    display: block;
  }

  &__strength {
    display: flex;
    align-items: center;
    gap: var(--space-2);
  }

  &__strength-bar {
    flex: 1;
    height: 4px;
    background: var(--color-neutral-200);
    border-radius: var(--radius-full);
    overflow: hidden;
  }

  &__strength-fill {
    height: 100%;
    width: 0;
    border-radius: var(--radius-full);
    transition: width var(--motion-duration-normal) var(--motion-easing-standard),
                background-color var(--motion-duration-normal) var(--motion-easing-standard);

    @media (prefers-reduced-motion: reduce) {
      transition: none;
    }
  }

  &__strength-label {
    font-size: var(--text-xs);
    color: var(--color-neutral-600);
    white-space: nowrap;
    min-width: 60px;
  }
}
```

- [ ] **Step 5: Verify Storybook renders the component**

```bash
npm run storybook
```

Open Storybook at `http://localhost:6006`, navigate to "Molecules / Password Field". Verify the default story renders a password input with a toggle button. Stop Storybook when verified.

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/password-field/
git commit -m "feat(design-system): add password-field molecule with toggle and strength indicator"
```

---

## Task 3: Auth Card Molecule

**Files:**
- Create: `public/packages/inno_frontend/components/molecules/auth-card/auth-card.php`
- Create: `public/packages/inno_frontend/components/molecules/auth-card/auth-card.variants.php`
- Create: `public/packages/inno_frontend/components/molecules/auth-card/auth-card.stories.json`
- Create: `public/packages/inno_frontend/components/molecules/auth-card/auth-card.scss`

- [ ] **Step 1: Create auth-card.php**

Create `public/packages/inno_frontend/components/molecules/auth-card/auth-card.php`:

```php
<?php
$title = $props['title'] ?? 'Anmelden';
$subtitle = $props['subtitle'] ?? '';
$showLogo = ($props['showLogo'] ?? 'true') === 'true';
$content = $props['content'] ?? '';
?>
<div class="auth-card">
    <div class="auth-card__background"></div>
    <div class="auth-card__card">
        <?php if ($showLogo): ?>
            <div class="auth-card__logo">
                <div class="auth-card__logo-mark">I</div>
                <span class="auth-card__logo-text">Innovationsbox</span>
            </div>
        <?php endif; ?>

        <div class="auth-card__header">
            <h1 class="auth-card__title"><?= htmlspecialchars($title) ?></h1>
            <?php if ($subtitle !== ''): ?>
                <p class="auth-card__subtitle"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </div>

        <div class="auth-card__content">
            <?= $content ?>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Create auth-card.variants.php**

Create `public/packages/inno_frontend/components/molecules/auth-card/auth-card.variants.php`:

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'title' => 'Anmelden',
        'subtitle' => 'Willkommen zurueck',
        'showLogo' => 'true',
        'content' => '<p style="color:var(--color-neutral-400);text-align:center;padding:var(--space-8) 0;">Formularinhalt wird hier eingefuegt</p>',
    ],
];
```

- [ ] **Step 3: Create auth-card.stories.json**

Create `public/packages/inno_frontend/components/molecules/auth-card/auth-card.stories.json`:

```json
{
  "title": "Molecules/Auth Card",
  "argTypes": {
    "title": { "control": "text" },
    "subtitle": { "control": "text" },
    "showLogo": { "control": { "type": "select" }, "options": ["true", "false"] },
    "content": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/auth-card" } } },
    {
      "name": "Register",
      "args": { "title": "Konto erstellen", "subtitle": "Registriere dich, um mitzumachen" },
      "parameters": { "server": { "id": "molecules/auth-card" } }
    },
    {
      "name": "Without Logo",
      "args": { "showLogo": "false" },
      "parameters": { "server": { "id": "molecules/auth-card" } }
    }
  ]
}
```

- [ ] **Step 4: Create auth-card.scss**

Create `public/packages/inno_frontend/components/molecules/auth-card/auth-card.scss`:

```scss
.auth-card {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: var(--space-6);

  &__background {
    position: fixed;
    inset: 0;
    background: linear-gradient(
      135deg,
      var(--color-primary-50) 0%,
      var(--color-primary-100) 50%,
      var(--color-neutral-50) 100%
    );
    z-index: -1;
  }

  &__card {
    width: 100%;
    max-width: 440px;
    background: var(--color-bg-primary);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    padding: var(--space-8);
  }

  &__logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-2);
    margin-bottom: var(--space-6);
  }

  &__logo-mark {
    width: 48px;
    height: 48px;
    background: var(--color-primary-600);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: var(--font-weight-bold);
    font-size: var(--text-lg);
  }

  &__logo-text {
    font-weight: var(--font-weight-semibold);
    font-size: var(--text-base);
    color: var(--color-text-primary);
  }

  &__header {
    text-align: center;
    margin-bottom: var(--space-6);
  }

  &__title {
    font-size: var(--text-xl);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    margin: 0;
  }

  &__subtitle {
    font-size: var(--text-sm);
    color: var(--color-text-secondary);
    margin: var(--space-1) 0 0;
  }

  &__content {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  @media (max-width: 480px) {
    padding: var(--space-4);

    &__background {
      background: var(--color-bg-primary);
    }

    &__card {
      box-shadow: none;
      padding: var(--space-6);
    }
  }
}
```

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/auth-card/
git commit -m "feat(design-system): add auth-card molecule with branded background"
```

---

## Task 4: User Menu Molecule

**Files:**
- Create: `public/packages/inno_frontend/components/molecules/user-menu/user-menu.php`
- Create: `public/packages/inno_frontend/components/molecules/user-menu/user-menu.variants.php`
- Create: `public/packages/inno_frontend/components/molecules/user-menu/user-menu.stories.json`
- Create: `public/packages/inno_frontend/components/molecules/user-menu/user-menu.scss`
- Create: `public/packages/inno_frontend/components/molecules/user-menu/user-menu.ts`

- [ ] **Step 1: Create user-menu.php**

Create `public/packages/inno_frontend/components/molecules/user-menu/user-menu.php`:

```php
<?php
$userName = $props['userName'] ?? '';
$userEmail = $props['userEmail'] ?? '';
$userInitials = $props['userInitials'] ?? '';
$avatarUrl = $props['avatarUrl'] ?? '';
$editProfileUrl = $props['editProfileUrl'] ?? '/account/edit_profile';
$logoutUrl = $props['logoutUrl'] ?? '/login/do_logout';
$isOpen = ($props['isOpen'] ?? 'false') === 'true';

$menuId = 'user-menu-dropdown';
?>
<div class="user-menu <?= $isOpen ? 'user-menu--open' : '' ?>" data-user-menu>
    <button
        type="button"
        class="user-menu__trigger"
        aria-haspopup="true"
        aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
        aria-controls="<?= htmlspecialchars($menuId) ?>"
        data-user-menu-trigger
    >
        <?php
        $props = [
            'src' => $avatarUrl,
            'name' => $userName,
            'size' => 'sm',
            'shape' => 'circle',
        ];
        include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
        ?>
    </button>

    <div
        id="<?= htmlspecialchars($menuId) ?>"
        class="user-menu__dropdown"
        role="menu"
        <?= $isOpen ? '' : 'hidden' ?>
    >
        <div class="user-menu__info" role="none">
            <?php
            $props = [
                'src' => $avatarUrl,
                'name' => $userName,
                'size' => 'md',
                'shape' => 'circle',
            ];
            include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
            ?>
            <div class="user-menu__details">
                <span class="user-menu__name"><?= htmlspecialchars($userName) ?></span>
                <span class="user-menu__email"><?= htmlspecialchars($userEmail) ?></span>
            </div>
        </div>

        <hr class="user-menu__divider" role="none">

        <a href="<?= htmlspecialchars($editProfileUrl) ?>" class="user-menu__item" role="menuitem">
            Profil bearbeiten
        </a>

        <hr class="user-menu__divider" role="none">

        <a href="<?= htmlspecialchars($logoutUrl) ?>" class="user-menu__item user-menu__item--danger" role="menuitem">
            Abmelden
        </a>
    </div>
</div>
```

- [ ] **Step 2: Create user-menu.variants.php**

Create `public/packages/inno_frontend/components/molecules/user-menu/user-menu.variants.php`:

```php
<?php

declare(strict_types=1);

$authData = require __DIR__ . '/../../_fixtures/auth-data.php';
$user = $authData['user'];

return [
    'defaults' => [
        'userName' => $user['name'],
        'userEmail' => $user['email'],
        'userInitials' => $user['initials'],
        'avatarUrl' => $user['avatarUrl'],
        'editProfileUrl' => '/account/edit_profile',
        'logoutUrl' => '/login/do_logout',
        'isOpen' => 'false',
    ],
];
```

- [ ] **Step 3: Create user-menu.stories.json**

Create `public/packages/inno_frontend/components/molecules/user-menu/user-menu.stories.json`:

```json
{
  "title": "Molecules/User Menu",
  "argTypes": {
    "userName": { "control": "text" },
    "userEmail": { "control": "text" },
    "userInitials": { "control": "text" },
    "avatarUrl": { "control": "text" },
    "editProfileUrl": { "control": "text" },
    "logoutUrl": { "control": "text" },
    "isOpen": { "control": { "type": "select" }, "options": ["true", "false"] }
  },
  "stories": [
    { "name": "Closed", "parameters": { "server": { "id": "molecules/user-menu" } } },
    {
      "name": "Open",
      "args": { "isOpen": "true" },
      "parameters": { "server": { "id": "molecules/user-menu" } }
    },
    {
      "name": "Initials Only",
      "args": { "avatarUrl": "", "isOpen": "true" },
      "parameters": { "server": { "id": "molecules/user-menu" } }
    },
    {
      "name": "With Avatar",
      "args": { "isOpen": "true" },
      "parameters": { "server": { "id": "molecules/user-menu" } }
    }
  ]
}
```

- [ ] **Step 4: Create user-menu.scss**

Create `public/packages/inno_frontend/components/molecules/user-menu/user-menu.scss`:

```scss
.user-menu {
  position: relative;

  &__trigger {
    display: flex;
    align-items: center;
    padding: 0;
    border: none;
    background: none;
    cursor: pointer;
    border-radius: var(--radius-full);
    min-width: 44px;
    min-height: 44px;
    justify-content: center;

    &:focus-visible {
      outline: 2px solid var(--color-action-focus);
      outline-offset: 2px;
    }
  }

  &__dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + var(--space-2));
    min-width: 240px;
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border-secondary);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    z-index: var(--z-index-dropdown);
    padding: var(--space-2) 0;
  }

  &__info {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
  }

  &__details {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  &__name {
    font-size: var(--text-sm);
    font-weight: var(--font-weight-medium);
    color: var(--color-text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  &__email {
    font-size: var(--text-xs);
    color: var(--color-text-tertiary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  &__divider {
    border: none;
    border-top: 1px solid var(--color-border-secondary);
    margin: var(--space-1) 0;
  }

  &__item {
    display: block;
    padding: var(--space-2) var(--space-4);
    font-size: var(--text-sm);
    color: var(--color-text-primary);
    text-decoration: none;
    min-height: 44px;
    display: flex;
    align-items: center;

    &:hover {
      background: var(--color-neutral-50);
    }

    &:focus-visible {
      outline: 2px solid var(--color-action-focus);
      outline-offset: -2px;
    }

    &--danger {
      color: var(--color-error-600);

      &:hover {
        background: var(--color-error-50);
      }
    }
  }
}
```

- [ ] **Step 5: Create user-menu.ts**

Create `public/packages/inno_frontend/components/molecules/user-menu/user-menu.ts`:

```typescript
document.querySelectorAll('[data-user-menu]').forEach((menu) => {
  const trigger = menu.querySelector('[data-user-menu-trigger]') as HTMLButtonElement | null;
  const dropdown = menu.querySelector('.user-menu__dropdown') as HTMLElement | null;
  if (!trigger || !dropdown) return;

  const menuItems = dropdown.querySelectorAll<HTMLAnchorElement>('.user-menu__item');

  function open() {
    menu.classList.add('user-menu--open');
    trigger!.setAttribute('aria-expanded', 'true');
    dropdown!.removeAttribute('hidden');
    menuItems[0]?.focus();
  }

  function close() {
    menu.classList.remove('user-menu--open');
    trigger!.setAttribute('aria-expanded', 'false');
    dropdown!.setAttribute('hidden', '');
    trigger!.focus();
  }

  function isOpen(): boolean {
    return trigger!.getAttribute('aria-expanded') === 'true';
  }

  trigger.addEventListener('click', () => {
    isOpen() ? close() : open();
  });

  // Close on Escape
  menu.addEventListener('keydown', (e: Event) => {
    const keyEvent = e as KeyboardEvent;
    if (keyEvent.key === 'Escape' && isOpen()) {
      close();
    }
  });

  // Close on click outside
  document.addEventListener('click', (e: Event) => {
    if (isOpen() && !menu.contains(e.target as Node)) {
      close();
    }
  });

  // Arrow key navigation within menu items
  dropdown.addEventListener('keydown', (e: Event) => {
    const keyEvent = e as KeyboardEvent;
    const items = Array.from(menuItems);
    const current = document.activeElement as HTMLElement;
    const index = items.indexOf(current as HTMLAnchorElement);

    if (keyEvent.key === 'ArrowDown') {
      keyEvent.preventDefault();
      const next = items[(index + 1) % items.length];
      next?.focus();
    } else if (keyEvent.key === 'ArrowUp') {
      keyEvent.preventDefault();
      const prev = items[(index - 1 + items.length) % items.length];
      prev?.focus();
    }
  });
});
```

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/user-menu/
git commit -m "feat(design-system): add user-menu molecule with dropdown and keyboard nav"
```

---

## Task 5: Avatar Upload Molecule

**Files:**
- Create: `public/packages/inno_frontend/components/molecules/avatar-upload/avatar-upload.php`
- Create: `public/packages/inno_frontend/components/molecules/avatar-upload/avatar-upload.variants.php`
- Create: `public/packages/inno_frontend/components/molecules/avatar-upload/avatar-upload.stories.json`
- Create: `public/packages/inno_frontend/components/molecules/avatar-upload/avatar-upload.scss`

- [ ] **Step 1: Create avatar-upload.php**

Create `public/packages/inno_frontend/components/molecules/avatar-upload/avatar-upload.php`:

```php
<?php
$hasAvatar = ($props['hasAvatar'] ?? 'false') === 'true';
$avatarUrl = $props['avatarUrl'] ?? '';
$userName = $props['userName'] ?? '';
$cropperHtml = $props['cropperHtml'] ?? '';
?>
<div class="avatar-upload">
    <div class="avatar-upload__preview">
        <?php
        $props = [
            'src' => $hasAvatar ? $avatarUrl : '',
            'name' => $userName,
            'size' => 'xl',
            'shape' => 'circle',
        ];
        include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
        ?>
    </div>

    <div class="avatar-upload__controls">
        <?php if ($cropperHtml !== ''): ?>
            <div class="avatar-upload__cropper">
                <?= $cropperHtml ?>
            </div>
        <?php else: ?>
            <?php
            $props = [
                'label' => 'Bild hochladen',
                'variant' => 'secondary',
                'size' => 'sm',
                'type' => 'button',
            ];
            include DIR_COMPONENTS . '/atoms/button/button.php';
            ?>
        <?php endif; ?>

        <?php if ($hasAvatar): ?>
            <?php
            $props = [
                'label' => 'Bild entfernen',
                'variant' => 'danger',
                'size' => 'sm',
                'type' => 'button',
            ];
            include DIR_COMPONENTS . '/atoms/button/button.php';
            ?>
        <?php endif; ?>
    </div>
</div>
```

- [ ] **Step 2: Create avatar-upload.variants.php**

Create `public/packages/inno_frontend/components/molecules/avatar-upload/avatar-upload.variants.php`:

```php
<?php

declare(strict_types=1);

$authData = require __DIR__ . '/../../_fixtures/auth-data.php';
$user = $authData['user'];

return [
    'defaults' => [
        'hasAvatar' => 'true',
        'avatarUrl' => $user['avatarUrl'],
        'userName' => $user['name'],
        'cropperHtml' => '',
    ],
];
```

- [ ] **Step 3: Create avatar-upload.stories.json**

Create `public/packages/inno_frontend/components/molecules/avatar-upload/avatar-upload.stories.json`:

```json
{
  "title": "Molecules/Avatar Upload",
  "argTypes": {
    "hasAvatar": { "control": { "type": "select" }, "options": ["true", "false"] },
    "avatarUrl": { "control": "text" },
    "userName": { "control": "text" },
    "cropperHtml": { "control": "text" }
  },
  "stories": [
    { "name": "With Avatar", "parameters": { "server": { "id": "molecules/avatar-upload" } } },
    {
      "name": "No Avatar",
      "args": { "hasAvatar": "false", "avatarUrl": "" },
      "parameters": { "server": { "id": "molecules/avatar-upload" } }
    }
  ]
}
```

- [ ] **Step 4: Create avatar-upload.scss**

Create `public/packages/inno_frontend/components/molecules/avatar-upload/avatar-upload.scss`:

```scss
.avatar-upload {
  display: flex;
  align-items: center;
  gap: var(--space-6);

  &__preview {
    flex-shrink: 0;

    .avatar--xl {
      width: 96px;
      height: 96px;
      font-size: var(--text-2xl);
    }
  }

  &__controls {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__cropper {
    // CMS avatar cropper styles — inherit from Concrete CMS
  }
}
```

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/avatar-upload/
git commit -m "feat(design-system): add avatar-upload molecule with preview and controls"
```

---

## Task 6: Member Card Molecule

**Files:**
- Create: `public/packages/inno_frontend/components/molecules/member-card/member-card.php`
- Create: `public/packages/inno_frontend/components/molecules/member-card/member-card.variants.php`
- Create: `public/packages/inno_frontend/components/molecules/member-card/member-card.stories.json`
- Create: `public/packages/inno_frontend/components/molecules/member-card/member-card.scss`

- [ ] **Step 1: Create member-card.php**

Create `public/packages/inno_frontend/components/molecules/member-card/member-card.php`:

```php
<?php
$userName = $props['userName'] ?? '';
$userInitials = $props['userInitials'] ?? '';
$avatarUrl = $props['avatarUrl'] ?? '';
$profileUrl = $props['profileUrl'] ?? '#';
$memberSince = $props['memberSince'] ?? '';
$role = $props['role'] ?? '';
?>
<a href="<?= htmlspecialchars($profileUrl) ?>" class="member-card">
    <div class="member-card__avatar">
        <?php
        $props = [
            'src' => $avatarUrl,
            'name' => $userName,
            'size' => 'lg',
            'shape' => 'circle',
        ];
        include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
        ?>
    </div>

    <div class="member-card__info">
        <span class="member-card__name"><?= htmlspecialchars($userName) ?></span>

        <?php if ($role !== ''): ?>
            <?php
            $props = [
                'label' => $role,
                'variant' => 'neutral',
                'size' => 'sm',
            ];
            include DIR_COMPONENTS . '/atoms/badge/badge.php';
            ?>
        <?php endif; ?>

        <?php if ($memberSince !== ''): ?>
            <span class="member-card__date">Mitglied seit <?= htmlspecialchars($memberSince) ?></span>
        <?php endif; ?>
    </div>
</a>
```

- [ ] **Step 2: Create member-card.variants.php**

Create `public/packages/inno_frontend/components/molecules/member-card/member-card.variants.php`:

```php
<?php

declare(strict_types=1);

$authData = require __DIR__ . '/../../_fixtures/auth-data.php';
$user = $authData['user'];

return [
    'defaults' => [
        'userName' => $user['name'],
        'userInitials' => $user['initials'],
        'avatarUrl' => $user['avatarUrl'],
        'profileUrl' => $user['profileUrl'],
        'memberSince' => $user['memberSince'],
        'role' => $user['role'],
    ],
];
```

- [ ] **Step 3: Create member-card.stories.json**

Create `public/packages/inno_frontend/components/molecules/member-card/member-card.stories.json`:

```json
{
  "title": "Molecules/Member Card",
  "argTypes": {
    "userName": { "control": "text" },
    "userInitials": { "control": "text" },
    "avatarUrl": { "control": "text" },
    "profileUrl": { "control": "text" },
    "memberSince": { "control": "text" },
    "role": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/member-card" } } },
    {
      "name": "Initials Only",
      "args": { "avatarUrl": "" },
      "parameters": { "server": { "id": "molecules/member-card" } }
    },
    {
      "name": "Without Role",
      "args": { "role": "" },
      "parameters": { "server": { "id": "molecules/member-card" } }
    }
  ]
}
```

- [ ] **Step 4: Create member-card.scss**

Create `public/packages/inno_frontend/components/molecules/member-card/member-card.scss`:

```scss
.member-card {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  padding: var(--space-4);
  border-radius: var(--radius-md);
  text-decoration: none;
  color: inherit;
  transition: background-color var(--motion-duration-fast) var(--motion-easing-standard);

  @media (prefers-reduced-motion: reduce) {
    transition: none;
  }

  &:hover {
    background: var(--color-neutral-50);
  }

  &:focus-visible {
    outline: 2px solid var(--color-action-focus);
    outline-offset: 2px;
  }

  &__avatar {
    flex-shrink: 0;
  }

  &__info {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    min-width: 0;
  }

  &__name {
    font-size: var(--text-base);
    font-weight: var(--font-weight-medium);
    color: var(--color-text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  &__date {
    font-size: var(--text-xs);
    color: var(--color-text-tertiary);
  }
}
```

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/member-card/
git commit -m "feat(design-system): add member-card molecule for members directory"
```

---

## Task 7: Storybook Aggregator Updates

**Files:**
- Modify: `public/packages/inno_frontend/components/_storybook/components.scss`
- Modify: `public/packages/inno_frontend/components/_storybook/components.ts`

- [ ] **Step 1: Add SCSS imports**

Add 5 new `@use` lines to `public/packages/inno_frontend/components/_storybook/components.scss`, in the molecules section (after existing molecule imports):

```scss
@use '../molecules/password-field/password-field';
@use '../molecules/auth-card/auth-card';
@use '../molecules/user-menu/user-menu';
@use '../molecules/avatar-upload/avatar-upload';
@use '../molecules/member-card/member-card';
```

- [ ] **Step 2: Add TS import for user-menu**

Add to `public/packages/inno_frontend/components/_storybook/components.ts`, after the existing imports:

```typescript
import '../molecules/user-menu/user-menu';
```

- [ ] **Step 3: Build to verify**

```bash
npm run development
```

Expected: builds without errors.

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/components/_storybook/components.scss \
  public/packages/inno_frontend/components/_storybook/components.ts
git commit -m "build: add new auth molecules to Storybook aggregators"
```

---

## Task 8: Verification

- [ ] **Step 1: Run full build**

```bash
npm run development
```

Expected: no errors.

- [ ] **Step 2: Run linting**

```bash
npm run lint:all
```

Expected: passes (may have existing warnings, no new errors).

- [ ] **Step 3: Run PHPStan**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/phpstan analyse --memory-limit=256M
```

Expected: no errors.

- [ ] **Step 4: Run unit tests**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/pest --testsuite=Unit
```

Expected: 76 tests pass (no test changes in this plan).

- [ ] **Step 5: Start Storybook and verify all new stories render**

```bash
npm run storybook
```

Open `http://localhost:6006`. Verify:
- "Molecules / Password Field" — 7 stories render
- "Molecules / Auth Card" — 3 stories render
- "Molecules / User Menu" — 4 stories render (test open/close toggle)
- "Molecules / Avatar Upload" — 2 stories render
- "Molecules / Member Card" — 3 stories render

Stop Storybook when verified.

- [ ] **Step 6: Final commit if any fixes needed**

```bash
git status
# Only commit if there are changes
git add -A && git commit -m "fix: resolve build/lint issues" || echo "Nothing to commit"
```
