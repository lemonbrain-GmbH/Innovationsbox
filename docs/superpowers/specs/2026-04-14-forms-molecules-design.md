# Forms Molecules — Sub-project 6

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Add FormField (Label + Input + Hint + Error) and SearchBar (Input + Icon + Clear) molecules. Compose existing atoms from Sub-projects 3-5.

## Architecture

Stateless PHP partials that compose atoms via `include DIR_COMPONENTS . '/atoms/...'`. FormField decides which input atom to render based on `type` prop. SearchBar wraps an input with absolutely-positioned icon and clear button.

## 1. FormField

**Files:**
- `molecules/form-field/form-field.php`
- `molecules/form-field/form-field.scss`
- `molecules/form-field/form-field.variants.php`
- `molecules/form-field/form-field.stories.json`

**Props:**
- `label` — text
- `id` — input ID
- `name` — form field name
- `type` — `text`, `email`, `password`, `number`, `search`, `tel`, `url`, `date`, `textarea`, `select` (default: `text`)
- `value` — current value
- `placeholder` — text
- `hint` — optional helper text
- `error` — optional error message (when present, overrides hint + sets invalid)
- `required` — "true"/"false"
- `disabled` — "true"/"false"
- `options` — for type=select, JSON array of `{value, label}`
- `rows` — for type=textarea, number (default 4)

**form-field.php:**
```php
<?php
$label = $props['label'] ?? '';
$id = $props['id'] ?? '';
$name = $props['name'] ?? $id;
$type = $props['type'] ?? 'text';
$value = $props['value'] ?? '';
$placeholder = $props['placeholder'] ?? '';
$hint = $props['hint'] ?? '';
$error = $props['error'] ?? '';
$required = ($props['required'] ?? 'false') === 'true';
$disabled = ($props['disabled'] ?? 'false') === 'true';
$options = $props['options'] ?? '[]';
$rows = $props['rows'] ?? '4';

$hasError = $error !== '';
$hintId = $id . '-hint';
$describedBy = $hasError ? $hintId : ($hint !== '' ? $hintId : '');

$fieldClasses = ['form-field'];
if ($hasError) {
    $fieldClasses[] = 'form-field--error';
}
if ($disabled) {
    $fieldClasses[] = 'form-field--disabled';
}
?>
<div class="<?= implode(' ', $fieldClasses) ?>">
    <?php
    // Label
    $props = [
        'text' => $label,
        'for' => $id,
        'required' => $required ? 'true' : 'false',
        'disabled' => $disabled ? 'true' : 'false',
    ];
    include DIR_COMPONENTS . '/atoms/label/label.php';

    // Input (based on type)
    $commonProps = [
        'id' => $id,
        'name' => $name,
        'value' => $value,
        'placeholder' => $placeholder,
        'disabled' => $disabled ? 'true' : 'false',
        'required' => $required ? 'true' : 'false',
        'invalid' => $hasError ? 'true' : 'false',
        'describedBy' => $describedBy,
    ];

    if ($type === 'textarea') {
        $props = array_merge($commonProps, ['rows' => $rows, 'resize' => 'vertical']);
        include DIR_COMPONENTS . '/atoms/textarea/textarea.php';
    } elseif ($type === 'select') {
        $props = array_merge($commonProps, ['options' => $options, 'size' => 'md']);
        include DIR_COMPONENTS . '/atoms/select/select.php';
    } else {
        $props = array_merge($commonProps, ['type' => $type, 'size' => 'md']);
        include DIR_COMPONENTS . '/atoms/input/input.php';
    }

    // Hint or error message
    if ($hasError) {
        $props = ['text' => $error, 'variant' => 'error'];
        echo '<span id="' . htmlspecialchars($hintId) . '" class="caption caption--error">' . htmlspecialchars($error) . '</span>';
    } elseif ($hint !== '') {
        echo '<span id="' . htmlspecialchars($hintId) . '" class="caption caption--muted">' . htmlspecialchars($hint) . '</span>';
    }
    ?>
</div>
```

**form-field.scss:**
```scss
.form-field {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);

  .input,
  .textarea,
  .select {
    width: 100%;
  }

  .select-wrapper {
    width: 100%;
  }
}
```

**form-field.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'label' => 'E-Mail-Adresse',
        'id' => 'email',
        'name' => 'email',
        'type' => 'email',
        'value' => '',
        'placeholder' => 'name@beispiel.ch',
        'hint' => 'Wir versenden nie Spam.',
        'error' => '',
        'required' => 'false',
        'disabled' => 'false',
        'options' => json_encode([
            ['value' => 'ch', 'label' => 'Schweiz'],
            ['value' => 'de', 'label' => 'Deutschland'],
            ['value' => 'at', 'label' => 'Österreich'],
        ]),
        'rows' => '4',
    ],
];
```

**form-field.stories.json:**
```json
{
  "title": "Molecules/Form Field",
  "argTypes": {
    "label": { "control": "text" },
    "id": { "control": "text" },
    "name": { "control": "text" },
    "type": { "control": { "type": "select" }, "options": ["text", "email", "password", "number", "textarea", "select"] },
    "value": { "control": "text" },
    "placeholder": { "control": "text" },
    "hint": { "control": "text" },
    "error": { "control": "text" },
    "required": { "control": { "type": "select" }, "options": ["true", "false"] },
    "disabled": { "control": { "type": "select" }, "options": ["true", "false"] },
    "rows": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/form-field" } } },
    { "name": "Required", "args": { "required": "true" }, "parameters": { "server": { "id": "molecules/form-field" } } },
    { "name": "With Error", "args": { "error": "Bitte eine gültige E-Mail eingeben.", "value": "not-an-email" }, "parameters": { "server": { "id": "molecules/form-field" } } },
    { "name": "Without Hint", "args": { "hint": "" }, "parameters": { "server": { "id": "molecules/form-field" } } },
    { "name": "Disabled", "args": { "disabled": "true", "value": "fixed@example.com" }, "parameters": { "server": { "id": "molecules/form-field" } } },
    { "name": "Textarea", "args": { "type": "textarea", "label": "Beschreibung", "id": "description", "placeholder": "Beschreibe deine Projektidee…", "hint": "Mindestens 50 Zeichen." }, "parameters": { "server": { "id": "molecules/form-field" } } },
    { "name": "Select", "args": { "type": "select", "label": "Land", "id": "country", "placeholder": "Bitte auswählen", "hint": "" }, "parameters": { "server": { "id": "molecules/form-field" } } }
  ]
}
```

## 2. SearchBar

**Files:**
- `molecules/search-bar/search-bar.php`
- `molecules/search-bar/search-bar.scss`
- `molecules/search-bar/search-bar.variants.php`
- `molecules/search-bar/search-bar.stories.json`

**Props:**
- `id` — input ID
- `name` — form field name (default: `q`)
- `value` — current value
- `placeholder` — text (default: `Suchen…`)
- `clearable` — "true"/"false" (default: "true")
- `disabled` — "true"/"false"
- `ariaLabel` — text (default: `Suche`) — accessible label for the input

**search-bar.php:**
```php
<?php
$id = $props['id'] ?? 'search';
$name = $props['name'] ?? 'q';
$value = $props['value'] ?? '';
$placeholder = $props['placeholder'] ?? 'Suchen…';
$clearable = ($props['clearable'] ?? 'true') === 'true';
$disabled = ($props['disabled'] ?? 'false') === 'true';
$ariaLabel = $props['ariaLabel'] ?? 'Suche';

$showClear = $clearable && $value !== '' && !$disabled;
?>
<div class="search-bar<?= $disabled ? ' search-bar--disabled' : '' ?>">
    <span class="search-bar__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 0 0 1.48-5.34c-.47-2.78-2.79-5-5.59-5.34a6.505 6.505 0 0 0-7.27 7.27c.34 2.8 2.56 5.12 5.34 5.59a6.5 6.5 0 0 0 5.34-1.48l.27.28v.79l4.25 4.25c.41.41 1.08.41 1.49 0 .41-.41.41-1.08 0-1.49L15.5 14zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
        </svg>
    </span>
    <input
        type="search"
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?>"
        value="<?= htmlspecialchars($value) ?>"
        class="search-bar__input"
        placeholder="<?= htmlspecialchars($placeholder) ?>"
        aria-label="<?= htmlspecialchars($ariaLabel) ?>"
        <?= $disabled ? 'disabled' : '' ?>
    >
    <?php if ($showClear): ?>
        <button type="reset" class="search-bar__clear" aria-label="Suche löschen">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    <?php endif; ?>
</div>
```

**search-bar.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.search-bar {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;

  &__icon {
    position: absolute;
    left: var(--space-3);
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-text-muted);
    pointer-events: none;
    width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
  }

  &__input {
    width: 100%;
    font-family: var(--text-font-family);
    font-size: 16px;
    line-height: 1.5;
    color: var(--color-text-primary);
    background: var(--color-bg-surface);
    border: 1px solid var(--color-border-default);
    border-radius: var(--radius-md);
    padding: var(--space-3) var(--space-10) var(--space-3) var(--space-10);

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

    &:disabled {
      background: var(--color-bg-subtle);
      color: var(--color-text-disabled);
      cursor: not-allowed;
    }

    // Hide native browser cancel button (we have our own)
    &::-webkit-search-cancel-button {
      appearance: none;
    }
  }

  &__clear {
    position: absolute;
    right: var(--space-2);
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: var(--radius-full);
    color: var(--color-text-muted);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    z-index: 1;

    @include a11y.respect-motion {
      transition:
        background var(--duration-fast) var(--ease-standard),
        color var(--duration-fast) var(--ease-standard);
    }

    &:hover {
      color: var(--color-text-primary);
      background: var(--color-bg-subtle);
    }

    @include a11y.focus-visible-ring;
  }
}
```

**search-bar.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'search',
        'name' => 'q',
        'value' => '',
        'placeholder' => 'Projektideen suchen…',
        'clearable' => 'true',
        'disabled' => 'false',
        'ariaLabel' => 'Suche',
    ],
];
```

**search-bar.stories.json:**
```json
{
  "title": "Molecules/Search Bar",
  "argTypes": {
    "id": { "control": "text" },
    "name": { "control": "text" },
    "value": { "control": "text" },
    "placeholder": { "control": "text" },
    "clearable": { "control": { "type": "select" }, "options": ["true", "false"] },
    "disabled": { "control": { "type": "select" }, "options": ["true", "false"] },
    "ariaLabel": { "control": "text" }
  },
  "stories": [
    { "name": "Empty", "parameters": { "server": { "id": "molecules/search-bar" } } },
    { "name": "With Value", "args": { "value": "Solarenergie" }, "parameters": { "server": { "id": "molecules/search-bar" } } },
    { "name": "Not Clearable", "args": { "value": "Windkraft", "clearable": "false" }, "parameters": { "server": { "id": "molecules/search-bar" } } },
    { "name": "Disabled", "args": { "disabled": "true", "value": "Read-only" }, "parameters": { "server": { "id": "molecules/search-bar" } } }
  ]
}
```

## Wiring

**`resources/css/main.scss`** — add 2 `@use` lines in molecules section (alphabetical):
```scss
@use "../../public/packages/inno_frontend/components/molecules/form-field/form-field";
@use "../../public/packages/inno_frontend/components/molecules/search-bar/search-bar";
```

**`_storybook/components.scss`** — same 2 lines in Molecules section.

## What Doesn't Change

- Atoms (form-field composes them as-is)
- Tokens, a11y infrastructure
- Other molecules, organisms, templates
- Storybook config

## Verification

1. `npm run development` — compiles without errors
2. Both molecules return 200:
   - `molecules/form-field`
   - `molecules/search-bar`
3. FormField error state shows error caption and `aria-invalid="true"` on input
4. FormField type=textarea renders textarea, type=select renders select
5. SearchBar clear button only appears when value present
6. Tab through — focus ring works on input and clear button
7. Axe panel shows no violations
