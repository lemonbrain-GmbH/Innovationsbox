# Form Control Atoms — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 6 form control atoms (Input, Textarea, Checkbox, Radio, Toggle, Select) using design tokens and a11y helpers from prior sub-projects.

**Architecture:** Stateless PHP partials with consistent prop API. Native HTML form elements with custom CSS styling. Common props: name, id, value, disabled, required, invalid, describedBy. Visually-hidden inputs for custom checkbox/radio/toggle.

**Tech Stack:** PHP partials, SCSS, JSON stories

---

### Task 1: Input atom

**Files:**
- Create: `public/packages/inno_frontend/components/atoms/input/input.php`
- Create: `public/packages/inno_frontend/components/atoms/input/input.scss`
- Create: `public/packages/inno_frontend/components/atoms/input/input.variants.php`
- Create: `public/packages/inno_frontend/components/atoms/input/input.stories.json`

- [ ] **Step 1: Create input.php**

```php
<?php
$type = $props['type'] ?? 'text';
$id = $props['id'] ?? '';
$name = $props['name'] ?? '';
$value = $props['value'] ?? '';
$placeholder = $props['placeholder'] ?? '';
$size = $props['size'] ?? 'md';
$autocomplete = $props['autocomplete'] ?? '';
$disabled = ($props['disabled'] ?? 'false') === 'true';
$required = ($props['required'] ?? 'false') === 'true';
$invalid = ($props['invalid'] ?? 'false') === 'true';
$describedBy = $props['describedBy'] ?? '';

$classes = ['input', 'input--' . $size];
?>
<input
    type="<?= htmlspecialchars($type) ?>"
    id="<?= htmlspecialchars($id) ?>"
    name="<?= htmlspecialchars($name) ?>"
    value="<?= htmlspecialchars($value) ?>"
    class="<?= implode(' ', $classes) ?>"
    <?= $placeholder ? 'placeholder="' . htmlspecialchars($placeholder) . '"' : '' ?>
    <?= $autocomplete ? 'autocomplete="' . htmlspecialchars($autocomplete) . '"' : '' ?>
    <?= $disabled ? 'disabled' : '' ?>
    <?= $required ? 'required' : '' ?>
    aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
    <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
>
```

- [ ] **Step 2: Create input.scss**

```scss
@use "~@helpers/a11y" as a11y;

.input {
  display: inline-block;
  width: 100%;
  font-family: var(--text-font-family);
  font-size: var(--p-font-size, 16px);
  line-height: 1.5;
  color: var(--color-text-primary);
  background: var(--color-bg-surface);
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-md);
  padding: var(--space-3) var(--space-4);

  @include a11y.respect-motion {
    transition:
      border-color var(--duration-fast) var(--ease-standard),
      box-shadow var(--duration-fast) var(--ease-standard);
  }

  &::placeholder {
    color: var(--color-text-muted);
  }

  &:hover:not(:disabled) {
    border-color: var(--color-border-strong);
  }

  @include a11y.focus-visible-ring;

  &[aria-invalid="true"] {
    border-color: var(--color-action-danger);
  }

  &:disabled {
    background: var(--color-bg-subtle);
    color: var(--color-text-disabled);
    cursor: not-allowed;
  }

  &--sm {
    padding: var(--space-2) var(--space-3);
    font-size: 14px;
  }

  &--lg {
    padding: var(--space-4) var(--space-5);
    font-size: 18px;
  }
}
```

- [ ] **Step 3: Create input.variants.php**

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'type' => 'text',
        'id' => 'email',
        'name' => 'email',
        'value' => '',
        'placeholder' => 'name@beispiel.ch',
        'size' => 'md',
        'autocomplete' => 'email',
        'disabled' => 'false',
        'required' => 'false',
        'invalid' => 'false',
        'describedBy' => '',
    ],
];
```

- [ ] **Step 4: Create input.stories.json**

```json
{
  "title": "Atoms/Input",
  "argTypes": {
    "type": { "control": { "type": "select" }, "options": ["text", "email", "password", "number", "search", "tel", "url", "date"] },
    "id": { "control": "text" },
    "name": { "control": "text" },
    "value": { "control": "text" },
    "placeholder": { "control": "text" },
    "size": { "control": { "type": "select" }, "options": ["sm", "md", "lg"] },
    "autocomplete": { "control": "text" },
    "disabled": { "control": { "type": "select" }, "options": ["true", "false"] },
    "required": { "control": { "type": "select" }, "options": ["true", "false"] },
    "invalid": { "control": { "type": "select" }, "options": ["true", "false"] },
    "describedBy": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/input" } } },
    { "name": "Email", "args": { "type": "email" }, "parameters": { "server": { "id": "atoms/input" } } },
    { "name": "Password", "args": { "type": "password", "value": "secret123", "autocomplete": "current-password", "placeholder": "Passwort" }, "parameters": { "server": { "id": "atoms/input" } } },
    { "name": "Number", "args": { "type": "number", "value": "42", "placeholder": "0" }, "parameters": { "server": { "id": "atoms/input" } } },
    { "name": "Disabled", "args": { "disabled": "true", "value": "fixed@example.com" }, "parameters": { "server": { "id": "atoms/input" } } },
    { "name": "Invalid", "args": { "invalid": "true", "value": "not-an-email" }, "parameters": { "server": { "id": "atoms/input" } } },
    { "name": "Required", "args": { "required": "true" }, "parameters": { "server": { "id": "atoms/input" } } },
    { "name": "Small", "args": { "size": "sm" }, "parameters": { "server": { "id": "atoms/input" } } },
    { "name": "Large", "args": { "size": "lg" }, "parameters": { "server": { "id": "atoms/input" } } }
  ]
}
```

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/input/
git commit -m "feat(atoms): add Input atom"
```

---

### Task 2: Textarea atom

**Files:**
- Create: `public/packages/inno_frontend/components/atoms/textarea/textarea.php`
- Create: `public/packages/inno_frontend/components/atoms/textarea/textarea.scss`
- Create: `public/packages/inno_frontend/components/atoms/textarea/textarea.variants.php`
- Create: `public/packages/inno_frontend/components/atoms/textarea/textarea.stories.json`

- [ ] **Step 1: Create textarea.php**

```php
<?php
$id = $props['id'] ?? '';
$name = $props['name'] ?? '';
$value = $props['value'] ?? '';
$placeholder = $props['placeholder'] ?? '';
$rows = (int) ($props['rows'] ?? 4);
$resize = $props['resize'] ?? 'vertical';
$disabled = ($props['disabled'] ?? 'false') === 'true';
$required = ($props['required'] ?? 'false') === 'true';
$invalid = ($props['invalid'] ?? 'false') === 'true';
$describedBy = $props['describedBy'] ?? '';

$style = 'resize: ' . htmlspecialchars($resize) . ';';
?>
<textarea
    id="<?= htmlspecialchars($id) ?>"
    name="<?= htmlspecialchars($name) ?>"
    rows="<?= $rows ?>"
    class="textarea"
    style="<?= $style ?>"
    <?= $placeholder ? 'placeholder="' . htmlspecialchars($placeholder) . '"' : '' ?>
    <?= $disabled ? 'disabled' : '' ?>
    <?= $required ? 'required' : '' ?>
    aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
    <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
><?= htmlspecialchars($value) ?></textarea>
```

- [ ] **Step 2: Create textarea.scss**

```scss
@use "~@helpers/a11y" as a11y;

.textarea {
  display: inline-block;
  width: 100%;
  font-family: var(--text-font-family);
  font-size: 16px;
  line-height: 1.5;
  color: var(--color-text-primary);
  background: var(--color-bg-surface);
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-md);
  padding: var(--space-3) var(--space-4);
  min-height: calc(4 * 1.5em + 2 * var(--space-3));

  @include a11y.respect-motion {
    transition:
      border-color var(--duration-fast) var(--ease-standard),
      box-shadow var(--duration-fast) var(--ease-standard);
  }

  &::placeholder {
    color: var(--color-text-muted);
  }

  &:hover:not(:disabled) {
    border-color: var(--color-border-strong);
  }

  @include a11y.focus-visible-ring;

  &[aria-invalid="true"] {
    border-color: var(--color-action-danger);
  }

  &:disabled {
    background: var(--color-bg-subtle);
    color: var(--color-text-disabled);
    cursor: not-allowed;
  }
}
```

- [ ] **Step 3: Create textarea.variants.php**

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'message',
        'name' => 'message',
        'value' => '',
        'placeholder' => 'Beschreibe deine Projektidee…',
        'rows' => '4',
        'resize' => 'vertical',
        'disabled' => 'false',
        'required' => 'false',
        'invalid' => 'false',
        'describedBy' => '',
    ],
];
```

- [ ] **Step 4: Create textarea.stories.json**

```json
{
  "title": "Atoms/Textarea",
  "argTypes": {
    "id": { "control": "text" },
    "name": { "control": "text" },
    "value": { "control": "text" },
    "placeholder": { "control": "text" },
    "rows": { "control": { "type": "select" }, "options": ["2", "4", "6", "8"] },
    "resize": { "control": { "type": "select" }, "options": ["none", "vertical", "horizontal", "both"] },
    "disabled": { "control": { "type": "select" }, "options": ["true", "false"] },
    "required": { "control": { "type": "select" }, "options": ["true", "false"] },
    "invalid": { "control": { "type": "select" }, "options": ["true", "false"] },
    "describedBy": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/textarea" } } },
    { "name": "With Value", "args": { "value": "Eine Solaranlage auf dem Schuldach in Glarus." }, "parameters": { "server": { "id": "atoms/textarea" } } },
    { "name": "Disabled", "args": { "disabled": "true", "value": "Schreibgeschützt" }, "parameters": { "server": { "id": "atoms/textarea" } } },
    { "name": "Invalid", "args": { "invalid": "true", "value": "Zu kurz" }, "parameters": { "server": { "id": "atoms/textarea" } } }
  ]
}
```

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/textarea/
git commit -m "feat(atoms): add Textarea atom"
```

---

### Task 3: Checkbox atom

**Files:**
- Create: `public/packages/inno_frontend/components/atoms/checkbox/checkbox.php`
- Create: `public/packages/inno_frontend/components/atoms/checkbox/checkbox.scss`
- Create: `public/packages/inno_frontend/components/atoms/checkbox/checkbox.variants.php`
- Create: `public/packages/inno_frontend/components/atoms/checkbox/checkbox.stories.json`

- [ ] **Step 1: Create checkbox.php**

```php
<?php
$id = $props['id'] ?? '';
$name = $props['name'] ?? '';
$value = $props['value'] ?? 'yes';
$label = $props['label'] ?? 'Option';
$checked = ($props['checked'] ?? 'false') === 'true';
$disabled = ($props['disabled'] ?? 'false') === 'true';
$required = ($props['required'] ?? 'false') === 'true';
$invalid = ($props['invalid'] ?? 'false') === 'true';
$describedBy = $props['describedBy'] ?? '';
?>
<label class="checkbox<?= $disabled ? ' checkbox--disabled' : '' ?>">
    <input
        type="checkbox"
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?>"
        value="<?= htmlspecialchars($value) ?>"
        class="checkbox__input"
        <?= $checked ? 'checked' : '' ?>
        <?= $disabled ? 'disabled' : '' ?>
        <?= $required ? 'required' : '' ?>
        aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
        <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
    >
    <span class="checkbox__box" aria-hidden="true">
        <svg viewBox="0 0 16 16" class="checkbox__check" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 8l3 3 7-7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <span class="checkbox__label"><?= htmlspecialchars($label) ?></span>
</label>
```

- [ ] **Step 2: Create checkbox.scss**

```scss
@use "~@helpers/a11y" as a11y;

.checkbox {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  cursor: pointer;
  user-select: none;
  font-family: var(--text-font-family);
  color: var(--color-text-primary);

  &--disabled {
    cursor: not-allowed;
    color: var(--color-text-disabled);
  }

  &__input {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  &__box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border: 2px solid var(--color-border-default);
    border-radius: var(--radius-sm);
    background: var(--color-bg-surface);
    flex-shrink: 0;

    @include a11y.respect-motion {
      transition:
        background var(--duration-fast) var(--ease-standard),
        border-color var(--duration-fast) var(--ease-standard);
    }
  }

  &__check {
    width: 14px;
    height: 14px;
    color: var(--color-action-primary-text);
    opacity: 0;

    @include a11y.respect-motion {
      transition: opacity var(--duration-fast) var(--ease-standard);
    }
  }

  &__input:checked + &__box {
    background: var(--color-action-primary);
    border-color: var(--color-action-primary);
  }

  &__input:checked + &__box .checkbox__check {
    opacity: 1;
  }

  &__input:focus-visible + &__box {
    outline: 2px solid var(--color-border-focus);
    outline-offset: 2px;
  }

  &__input[aria-invalid="true"] + &__box {
    border-color: var(--color-action-danger);
  }

  &__input:disabled + &__box {
    background: var(--color-bg-subtle);
    border-color: var(--color-border-subtle);
  }
}
```

- [ ] **Step 3: Create checkbox.variants.php**

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'terms',
        'name' => 'terms',
        'value' => 'yes',
        'label' => 'Ich akzeptiere die Datenschutzerklärung',
        'checked' => 'false',
        'disabled' => 'false',
        'required' => 'false',
        'invalid' => 'false',
        'describedBy' => '',
    ],
];
```

- [ ] **Step 4: Create checkbox.stories.json**

```json
{
  "title": "Atoms/Checkbox",
  "argTypes": {
    "id": { "control": "text" },
    "name": { "control": "text" },
    "value": { "control": "text" },
    "label": { "control": "text" },
    "checked": { "control": { "type": "select" }, "options": ["true", "false"] },
    "disabled": { "control": { "type": "select" }, "options": ["true", "false"] },
    "required": { "control": { "type": "select" }, "options": ["true", "false"] },
    "invalid": { "control": { "type": "select" }, "options": ["true", "false"] },
    "describedBy": { "control": "text" }
  },
  "stories": [
    { "name": "Unchecked", "parameters": { "server": { "id": "atoms/checkbox" } } },
    { "name": "Checked", "args": { "checked": "true" }, "parameters": { "server": { "id": "atoms/checkbox" } } },
    { "name": "Disabled", "args": { "disabled": "true" }, "parameters": { "server": { "id": "atoms/checkbox" } } },
    { "name": "Disabled Checked", "args": { "disabled": "true", "checked": "true" }, "parameters": { "server": { "id": "atoms/checkbox" } } },
    { "name": "Invalid", "args": { "invalid": "true" }, "parameters": { "server": { "id": "atoms/checkbox" } } },
    { "name": "Required", "args": { "required": "true" }, "parameters": { "server": { "id": "atoms/checkbox" } } }
  ]
}
```

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/checkbox/
git commit -m "feat(atoms): add Checkbox atom"
```

---

### Task 4: Radio atom

**Files:**
- Create: `public/packages/inno_frontend/components/atoms/radio/radio.php`
- Create: `public/packages/inno_frontend/components/atoms/radio/radio.scss`
- Create: `public/packages/inno_frontend/components/atoms/radio/radio.variants.php`
- Create: `public/packages/inno_frontend/components/atoms/radio/radio.stories.json`

- [ ] **Step 1: Create radio.php**

```php
<?php
$id = $props['id'] ?? '';
$name = $props['name'] ?? '';
$value = $props['value'] ?? '';
$label = $props['label'] ?? 'Option';
$checked = ($props['checked'] ?? 'false') === 'true';
$disabled = ($props['disabled'] ?? 'false') === 'true';
$required = ($props['required'] ?? 'false') === 'true';
$invalid = ($props['invalid'] ?? 'false') === 'true';
$describedBy = $props['describedBy'] ?? '';
?>
<label class="radio<?= $disabled ? ' radio--disabled' : '' ?>">
    <input
        type="radio"
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?>"
        value="<?= htmlspecialchars($value) ?>"
        class="radio__input"
        <?= $checked ? 'checked' : '' ?>
        <?= $disabled ? 'disabled' : '' ?>
        <?= $required ? 'required' : '' ?>
        aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
        <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
    >
    <span class="radio__circle" aria-hidden="true">
        <span class="radio__dot"></span>
    </span>
    <span class="radio__label"><?= htmlspecialchars($label) ?></span>
</label>
```

- [ ] **Step 2: Create radio.scss**

```scss
@use "~@helpers/a11y" as a11y;

.radio {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  cursor: pointer;
  user-select: none;
  font-family: var(--text-font-family);
  color: var(--color-text-primary);

  &--disabled {
    cursor: not-allowed;
    color: var(--color-text-disabled);
  }

  &__input {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  &__circle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border: 2px solid var(--color-border-default);
    border-radius: var(--radius-full);
    background: var(--color-bg-surface);
    flex-shrink: 0;

    @include a11y.respect-motion {
      transition: border-color var(--duration-fast) var(--ease-standard);
    }
  }

  &__dot {
    width: 8px;
    height: 8px;
    border-radius: var(--radius-full);
    background: var(--color-action-primary);
    transform: scale(0);

    @include a11y.respect-motion {
      transition: transform var(--duration-fast) var(--ease-standard);
    }
  }

  &__input:checked + &__circle {
    border-color: var(--color-action-primary);
  }

  &__input:checked + &__circle .radio__dot {
    transform: scale(1);
  }

  &__input:focus-visible + &__circle {
    outline: 2px solid var(--color-border-focus);
    outline-offset: 2px;
  }

  &__input[aria-invalid="true"] + &__circle {
    border-color: var(--color-action-danger);
  }

  &__input:disabled + &__circle {
    background: var(--color-bg-subtle);
    border-color: var(--color-border-subtle);
  }
}
```

- [ ] **Step 3: Create radio.variants.php**

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'size-md',
        'name' => 'size',
        'value' => 'md',
        'label' => 'Mittel',
        'checked' => 'false',
        'disabled' => 'false',
        'required' => 'false',
        'invalid' => 'false',
        'describedBy' => '',
    ],
];
```

- [ ] **Step 4: Create radio.stories.json**

```json
{
  "title": "Atoms/Radio",
  "argTypes": {
    "id": { "control": "text" },
    "name": { "control": "text" },
    "value": { "control": "text" },
    "label": { "control": "text" },
    "checked": { "control": { "type": "select" }, "options": ["true", "false"] },
    "disabled": { "control": { "type": "select" }, "options": ["true", "false"] },
    "required": { "control": { "type": "select" }, "options": ["true", "false"] },
    "invalid": { "control": { "type": "select" }, "options": ["true", "false"] },
    "describedBy": { "control": "text" }
  },
  "stories": [
    { "name": "Unchecked", "parameters": { "server": { "id": "atoms/radio" } } },
    { "name": "Checked", "args": { "checked": "true" }, "parameters": { "server": { "id": "atoms/radio" } } },
    { "name": "Disabled", "args": { "disabled": "true" }, "parameters": { "server": { "id": "atoms/radio" } } },
    { "name": "Disabled Checked", "args": { "disabled": "true", "checked": "true" }, "parameters": { "server": { "id": "atoms/radio" } } }
  ]
}
```

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/radio/
git commit -m "feat(atoms): add Radio atom"
```

---

### Task 5: Toggle atom

**Files:**
- Create: `public/packages/inno_frontend/components/atoms/toggle/toggle.php`
- Create: `public/packages/inno_frontend/components/atoms/toggle/toggle.scss`
- Create: `public/packages/inno_frontend/components/atoms/toggle/toggle.variants.php`
- Create: `public/packages/inno_frontend/components/atoms/toggle/toggle.stories.json`

- [ ] **Step 1: Create toggle.php**

```php
<?php
$id = $props['id'] ?? '';
$name = $props['name'] ?? '';
$value = $props['value'] ?? 'yes';
$label = $props['label'] ?? 'Option';
$checked = ($props['checked'] ?? 'false') === 'true';
$disabled = ($props['disabled'] ?? 'false') === 'true';
$required = ($props['required'] ?? 'false') === 'true';
$invalid = ($props['invalid'] ?? 'false') === 'true';
$describedBy = $props['describedBy'] ?? '';
?>
<label class="toggle<?= $disabled ? ' toggle--disabled' : '' ?>">
    <input
        type="checkbox"
        role="switch"
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?>"
        value="<?= htmlspecialchars($value) ?>"
        class="toggle__input"
        <?= $checked ? 'checked' : '' ?>
        <?= $disabled ? 'disabled' : '' ?>
        <?= $required ? 'required' : '' ?>
        aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
        <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
    >
    <span class="toggle__track" aria-hidden="true">
        <span class="toggle__thumb"></span>
    </span>
    <span class="toggle__label"><?= htmlspecialchars($label) ?></span>
</label>
```

- [ ] **Step 2: Create toggle.scss**

```scss
@use "~@helpers/a11y" as a11y;

.toggle {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  cursor: pointer;
  user-select: none;
  font-family: var(--text-font-family);
  color: var(--color-text-primary);

  &--disabled {
    cursor: not-allowed;
    color: var(--color-text-disabled);
  }

  &__input {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  &__track {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 22px;
    border-radius: var(--radius-full);
    background: var(--color-neutral-300);
    flex-shrink: 0;

    @include a11y.respect-motion {
      transition: background var(--duration-fast) var(--ease-standard);
    }
  }

  &__thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 18px;
    height: 18px;
    border-radius: var(--radius-full);
    background: var(--color-bg-surface);
    box-shadow: var(--shadow-sm);

    @include a11y.respect-motion {
      transition: transform var(--duration-fast) var(--ease-standard);
    }
  }

  &__input:checked + &__track {
    background: var(--color-action-primary);
  }

  &__input:checked + &__track .toggle__thumb {
    transform: translateX(18px);
  }

  &__input:focus-visible + &__track {
    outline: 2px solid var(--color-border-focus);
    outline-offset: 2px;
  }

  &__input:disabled + &__track {
    background: var(--color-bg-subtle);
  }
}
```

- [ ] **Step 3: Create toggle.variants.php**

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'notifications',
        'name' => 'notifications',
        'value' => 'yes',
        'label' => 'E-Mail-Benachrichtigungen',
        'checked' => 'false',
        'disabled' => 'false',
        'required' => 'false',
        'invalid' => 'false',
        'describedBy' => '',
    ],
];
```

- [ ] **Step 4: Create toggle.stories.json**

```json
{
  "title": "Atoms/Toggle",
  "argTypes": {
    "id": { "control": "text" },
    "name": { "control": "text" },
    "value": { "control": "text" },
    "label": { "control": "text" },
    "checked": { "control": { "type": "select" }, "options": ["true", "false"] },
    "disabled": { "control": { "type": "select" }, "options": ["true", "false"] },
    "required": { "control": { "type": "select" }, "options": ["true", "false"] },
    "invalid": { "control": { "type": "select" }, "options": ["true", "false"] },
    "describedBy": { "control": "text" }
  },
  "stories": [
    { "name": "Off", "parameters": { "server": { "id": "atoms/toggle" } } },
    { "name": "On", "args": { "checked": "true" }, "parameters": { "server": { "id": "atoms/toggle" } } },
    { "name": "Disabled", "args": { "disabled": "true" }, "parameters": { "server": { "id": "atoms/toggle" } } },
    { "name": "Disabled On", "args": { "disabled": "true", "checked": "true" }, "parameters": { "server": { "id": "atoms/toggle" } } }
  ]
}
```

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/toggle/
git commit -m "feat(atoms): add Toggle atom"
```

---

### Task 6: Select atom

**Files:**
- Create: `public/packages/inno_frontend/components/atoms/select/select.php`
- Create: `public/packages/inno_frontend/components/atoms/select/select.scss`
- Create: `public/packages/inno_frontend/components/atoms/select/select.variants.php`
- Create: `public/packages/inno_frontend/components/atoms/select/select.stories.json`

- [ ] **Step 1: Create select.php**

```php
<?php
$id = $props['id'] ?? '';
$name = $props['name'] ?? '';
$value = $props['value'] ?? '';
$placeholder = $props['placeholder'] ?? '';
$size = $props['size'] ?? 'md';
$optionsJson = $props['options'] ?? '[]';
$options = json_decode($optionsJson, true) ?: [];
$disabled = ($props['disabled'] ?? 'false') === 'true';
$required = ($props['required'] ?? 'false') === 'true';
$invalid = ($props['invalid'] ?? 'false') === 'true';
$describedBy = $props['describedBy'] ?? '';
?>
<div class="select-wrapper select-wrapper--<?= htmlspecialchars($size) ?>">
    <select
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?>"
        class="select"
        <?= $disabled ? 'disabled' : '' ?>
        <?= $required ? 'required' : '' ?>
        aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
        <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
    >
        <?php if ($placeholder): ?>
            <option value="" disabled <?= $value === '' ? 'selected' : '' ?>><?= htmlspecialchars($placeholder) ?></option>
        <?php endif; ?>
        <?php foreach ($options as $option): ?>
            <option value="<?= htmlspecialchars($option['value']) ?>" <?= $value === $option['value'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($option['label']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <span class="select__chevron" aria-hidden="true">▾</span>
</div>
```

- [ ] **Step 2: Create select.scss**

```scss
@use "~@helpers/a11y" as a11y;

.select-wrapper {
  position: relative;
  display: inline-block;
  width: 100%;
}

.select {
  appearance: none;
  -webkit-appearance: none;
  display: inline-block;
  width: 100%;
  font-family: var(--text-font-family);
  font-size: 16px;
  line-height: 1.5;
  color: var(--color-text-primary);
  background: var(--color-bg-surface);
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-md);
  padding: var(--space-3) var(--space-8) var(--space-3) var(--space-4);
  cursor: pointer;

  @include a11y.respect-motion {
    transition:
      border-color var(--duration-fast) var(--ease-standard),
      box-shadow var(--duration-fast) var(--ease-standard);
  }

  &:hover:not(:disabled) {
    border-color: var(--color-border-strong);
  }

  @include a11y.focus-visible-ring;

  &[aria-invalid="true"] {
    border-color: var(--color-action-danger);
  }

  &:disabled {
    background: var(--color-bg-subtle);
    color: var(--color-text-disabled);
    cursor: not-allowed;
  }
}

.select__chevron {
  position: absolute;
  right: var(--space-3);
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: var(--color-text-muted);
  font-size: 14px;
}

.select-wrapper--sm .select {
  padding: var(--space-2) var(--space-8) var(--space-2) var(--space-3);
  font-size: 14px;
}

.select-wrapper--lg .select {
  padding: var(--space-4) var(--space-10) var(--space-4) var(--space-5);
  font-size: 18px;
}
```

- [ ] **Step 3: Create select.variants.php**

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'country',
        'name' => 'country',
        'value' => '',
        'placeholder' => 'Land auswählen…',
        'size' => 'md',
        'options' => json_encode([
            ['value' => 'ch', 'label' => 'Schweiz'],
            ['value' => 'de', 'label' => 'Deutschland'],
            ['value' => 'at', 'label' => 'Österreich'],
            ['value' => 'li', 'label' => 'Liechtenstein'],
        ]),
        'disabled' => 'false',
        'required' => 'false',
        'invalid' => 'false',
        'describedBy' => '',
    ],
];
```

- [ ] **Step 4: Create select.stories.json**

```json
{
  "title": "Atoms/Select",
  "argTypes": {
    "id": { "control": "text" },
    "name": { "control": "text" },
    "value": { "control": "text" },
    "placeholder": { "control": "text" },
    "size": { "control": { "type": "select" }, "options": ["sm", "md", "lg"] },
    "disabled": { "control": { "type": "select" }, "options": ["true", "false"] },
    "required": { "control": { "type": "select" }, "options": ["true", "false"] },
    "invalid": { "control": { "type": "select" }, "options": ["true", "false"] },
    "describedBy": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/select" } } },
    { "name": "With Value", "args": { "value": "ch" }, "parameters": { "server": { "id": "atoms/select" } } },
    { "name": "Disabled", "args": { "disabled": "true", "value": "ch" }, "parameters": { "server": { "id": "atoms/select" } } },
    { "name": "Invalid", "args": { "invalid": "true" }, "parameters": { "server": { "id": "atoms/select" } } },
    { "name": "Small", "args": { "size": "sm" }, "parameters": { "server": { "id": "atoms/select" } } },
    { "name": "Large", "args": { "size": "lg" }, "parameters": { "server": { "id": "atoms/select" } } }
  ]
}
```

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/select/
git commit -m "feat(atoms): add Select atom"
```

---

### Task 7: Wire SCSS into theme + Storybook bundle

**Files:**
- Modify: `resources/css/main.scss`
- Modify: `public/packages/inno_frontend/components/_storybook/components.scss`

- [ ] **Step 1: Add 6 @use lines to resources/css/main.scss**

In the atoms section (alphabetical), add these lines (they should appear in order: caption, code, container, heading, image, label, link, overlay, paragraph, skip-link → and now also checkbox, input, radio, select, textarea, toggle).

The new atoms should be inserted in alphabetical position:

```scss
@use "../../public/packages/inno_frontend/components/atoms/button/button";
@use "../../public/packages/inno_frontend/components/atoms/caption/caption";
@use "../../public/packages/inno_frontend/components/atoms/checkbox/checkbox";
@use "../../public/packages/inno_frontend/components/atoms/code/code";
@use "../../public/packages/inno_frontend/components/atoms/container/container";
@use "../../public/packages/inno_frontend/components/atoms/heading/heading";
@use "../../public/packages/inno_frontend/components/atoms/image/image";
@use "../../public/packages/inno_frontend/components/atoms/input/input";
@use "../../public/packages/inno_frontend/components/atoms/label/label";
@use "../../public/packages/inno_frontend/components/atoms/link/link";
@use "../../public/packages/inno_frontend/components/atoms/overlay/overlay";
@use "../../public/packages/inno_frontend/components/atoms/paragraph/paragraph";
@use "../../public/packages/inno_frontend/components/atoms/radio/radio";
@use "../../public/packages/inno_frontend/components/atoms/select/select";
@use "../../public/packages/inno_frontend/components/atoms/skip-link/skip-link";
@use "../../public/packages/inno_frontend/components/atoms/textarea/textarea";
@use "../../public/packages/inno_frontend/components/atoms/toggle/toggle";
```

- [ ] **Step 2: Add same 6 lines to _storybook/components.scss**

In the Atoms section (alphabetical), add:

```scss
// Atoms
@use "../atoms/button/button";
@use "../atoms/caption/caption";
@use "../atoms/checkbox/checkbox";
@use "../atoms/code/code";
@use "../atoms/container/container";
@use "../atoms/heading/heading";
@use "../atoms/image/image";
@use "../atoms/input/input";
@use "../atoms/label/label";
@use "../atoms/link/link";
@use "../atoms/overlay/overlay";
@use "../atoms/paragraph/paragraph";
@use "../atoms/radio/radio";
@use "../atoms/select/select";
@use "../atoms/skip-link/skip-link";
@use "../atoms/textarea/textarea";
@use "../atoms/toggle/toggle";
```

- [ ] **Step 3: Build CSS**

```bash
npm run development
```

Expected: `webpack compiled successfully`

- [ ] **Step 4: Verify all 6 atoms render**

```bash
fuser -k 6007/tcp 2>/dev/null
nix develop --command bash -c "php -S localhost:6007 storybook-server/index.php" > /dev/null 2>&1 &
sleep 1
for comp in atoms/input atoms/textarea atoms/checkbox atoms/radio atoms/toggle atoms/select; do
  code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:6007/$comp")
  echo "$comp: $code"
done
```

Expected: all return 200.

- [ ] **Step 5: Verify existing components still work**

```bash
for comp in organisms/navigation-drawer organisms/footer pages/homepage; do
  code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:6007/$comp")
  echo "$comp: $code"
done
```

Expected: all return 200.

- [ ] **Step 6: Commit wiring + build artifacts**

```bash
git add resources/css/main.scss public/packages/inno_frontend/components/_storybook/components.scss public/packages/inno_frontend/themes/innovationsbox/css/skins/default.css public/packages/inno_frontend/storybook/components.css
git commit -m "feat(atoms): wire form control SCSS into theme and Storybook bundle"
```
