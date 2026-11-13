# Typography Atoms — Sub-project 3

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Enhance existing Heading/Paragraph atoms with `id`/`variant` props. Add missing Label, Caption, Code atoms. All using tokens from Sub-project 1.

## Architecture

Two categories of work:
1. **Enhance existing:** Heading and Paragraph get `id` (for anchors/associations) and expanded `variant` (default, muted, inverse)
2. **New atoms:** Label, Caption, Code — each with PHP partial, SCSS, variants, stories

Base h1-h6 and p styles stay in `global/presets.scss`. Atom-specific SCSS adds modifier classes only (`heading--muted`, `p--inverse`, etc.).

## Enhancements

### Heading

**heading.php:**

```php
<?php
$level = (int) ($props['level'] ?? 2);
$level = max(1, min(6, $level));
$text = $props['text'] ?? 'Überschrift';
$id = $props['id'] ?? '';
$variant = $props['variant'] ?? 'default';
$tag = 'h' . $level;

$classes = [];
if ($variant === 'muted') {
    $classes[] = 'heading--muted';
}
if ($variant === 'inverse') {
    $classes[] = 'heading--inverse';
}
$classAttr = $classes ? ' class="' . implode(' ', $classes) . '"' : '';
$idAttr = $id ? ' id="' . htmlspecialchars($id) . '"' : '';
?>
<<?= $tag ?><?= $idAttr ?><?= $classAttr ?>><?= htmlspecialchars($text) ?></<?= $tag ?>>
```

**heading.scss:**

```scss
// Atom: Heading
// Base h1-h6 styles live in global/presets.scss; this file adds variants.

.heading--muted {
  color: var(--color-text-muted);
}

.heading--inverse {
  color: var(--color-text-inverse);
}
```

**heading.variants.php** — add defaults for new props:
```php
'defaults' => [
    'level' => '2',
    'text' => $data['content']['introTitle'],
    'id' => '',
    'variant' => 'default',
],
```

**heading.stories.json** — add argTypes:
```json
"argTypes": {
  "level": { "control": { "type": "select" }, "options": ["1", "2", "3", "4", "5", "6"] },
  "text": { "control": "text" },
  "id": { "control": "text" },
  "variant": { "control": { "type": "select" }, "options": ["default", "muted", "inverse"] }
}
```

### Paragraph

**paragraph.php:**

```php
<?php
$variant = $props['variant'] ?? 'body';
$text = $props['text'] ?? '';
$id = $props['id'] ?? '';

$classes = [];
if ($variant === 'lead') $classes[] = 'lead';
if ($variant === 'small') $classes[] = 'small';
if ($variant === 'muted') $classes[] = 'p--muted';
if ($variant === 'inverse') $classes[] = 'p--inverse';

$classAttr = $classes ? ' class="' . implode(' ', $classes) . '"' : '';
$idAttr = $id ? ' id="' . htmlspecialchars($id) . '"' : '';
?>
<p<?= $idAttr ?><?= $classAttr ?>><?= htmlspecialchars($text) ?></p>
```

**paragraph.scss:**

```scss
// Atom: Paragraph
// Base p, .lead, .small styles live in global/presets.scss; this file adds variants.

.p--muted {
  color: var(--color-text-muted);
}

.p--inverse {
  color: var(--color-text-inverse);
}
```

**paragraph.variants.php** — add `id` default, keep existing.

**paragraph.stories.json** — expand argTypes:
```json
"argTypes": {
  "variant": { "control": { "type": "select" }, "options": ["body", "lead", "small", "muted", "inverse"] },
  "text": { "control": "text" },
  "id": { "control": "text" }
}
```

## New atoms

### Label

**`components/atoms/label/label.php`:**

```php
<?php
$text = $props['text'] ?? 'Label';
$for = $props['for'] ?? '';
$required = ($props['required'] ?? 'false') === 'true';
$disabled = ($props['disabled'] ?? 'false') === 'true';

$classes = ['label'];
if ($required) $classes[] = 'label--required';
if ($disabled) $classes[] = 'label--disabled';
?>
<label<?= $for ? ' for="' . htmlspecialchars($for) . '"' : '' ?> class="<?= implode(' ', $classes) ?>">
    <?= htmlspecialchars($text) ?><?php if ($required): ?><span class="label__required" aria-hidden="true">*</span><?php endif; ?>
</label>
```

**`components/atoms/label/label.scss`:**

```scss
@use "~@helpers/functions" as f;

.label {
  display: inline-block;
  font-family: var(--text-font-family);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  font-size: clamp(13px, 13px + 3 * (100vw - 1200px) / 480, 16px);
  line-height: 1.4;

  &--disabled {
    color: var(--color-text-disabled);
    cursor: not-allowed;
  }

  &__required {
    color: var(--color-text-error);
    margin-left: 2px;
  }
}
```

**`components/atoms/label/label.variants.php`:**

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'text' => 'E-Mail-Adresse',
        'for' => 'email',
        'required' => 'false',
        'disabled' => 'false',
    ],
];
```

**`components/atoms/label/label.stories.json`:**

```json
{
  "title": "Atoms/Label",
  "argTypes": {
    "text": { "control": "text" },
    "for": { "control": "text" },
    "required": { "control": { "type": "select" }, "options": ["true", "false"] },
    "disabled": { "control": { "type": "select" }, "options": ["true", "false"] }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/label" } } },
    { "name": "Required", "args": { "required": "true" }, "parameters": { "server": { "id": "atoms/label" } } },
    { "name": "Disabled", "args": { "disabled": "true" }, "parameters": { "server": { "id": "atoms/label" } } }
  ]
}
```

### Caption

**`components/atoms/caption/caption.php`:**

```php
<?php
$text = $props['text'] ?? 'Caption';
$variant = $props['variant'] ?? 'default';

$classes = ['caption'];
if (in_array($variant, ['muted', 'error', 'success'], true)) {
    $classes[] = 'caption--' . $variant;
}
?>
<span class="<?= implode(' ', $classes) ?>"><?= htmlspecialchars($text) ?></span>
```

**`components/atoms/caption/caption.scss`:**

```scss
.caption {
  display: inline-block;
  font-family: var(--text-font-family);
  font-weight: var(--font-weight-regular);
  color: var(--color-text-secondary);
  font-size: clamp(12px, 12px + 2 * (100vw - 1200px) / 480, 14px);
  line-height: 1.33;

  &--muted {
    color: var(--color-text-muted);
  }

  &--error {
    color: var(--color-text-error);
  }

  &--success {
    color: var(--color-text-success);
  }
}
```

**`components/atoms/caption/caption.variants.php`:**

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'text' => 'Geben Sie Ihre E-Mail-Adresse ein.',
        'variant' => 'default',
    ],
];
```

**`components/atoms/caption/caption.stories.json`:**

```json
{
  "title": "Atoms/Caption",
  "argTypes": {
    "text": { "control": "text" },
    "variant": { "control": { "type": "select" }, "options": ["default", "muted", "error", "success"] }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/caption" } } },
    { "name": "Muted", "args": { "variant": "muted" }, "parameters": { "server": { "id": "atoms/caption" } } },
    { "name": "Error", "args": { "variant": "error", "text": "Bitte eine gültige E-Mail eingeben." }, "parameters": { "server": { "id": "atoms/caption" } } },
    { "name": "Success", "args": { "variant": "success", "text": "E-Mail-Adresse bestätigt." }, "parameters": { "server": { "id": "atoms/caption" } } }
  ]
}
```

### Code

**`components/atoms/code/code.php`:**

```php
<?php
$content = $props['content'] ?? 'code';
$block = ($props['block'] ?? 'false') === 'true';
?>
<?php if ($block): ?>
<pre class="code-block"><code class="code"><?= htmlspecialchars($content) ?></code></pre>
<?php else: ?>
<code class="code"><?= htmlspecialchars($content) ?></code>
<?php endif; ?>
```

**`components/atoms/code/code.scss`:**

```scss
.code {
  font-family: ui-monospace, 'Menlo', 'Monaco', 'Courier New', monospace;
  font-size: 0.9em;
  background: var(--color-bg-subtle);
  color: var(--color-text-primary);
  padding: 0.15em 0.35em;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border-subtle);
}

.code-block {
  background: var(--color-bg-subtle);
  border: 1px solid var(--color-border-subtle);
  border-radius: var(--radius-md);
  padding: var(--space-4);
  overflow-x: auto;
  margin: var(--space-4) 0;

  .code {
    background: transparent;
    border: none;
    padding: 0;
    font-size: inherit;
    display: block;
  }
}
```

**`components/atoms/code/code.variants.php`:**

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'content' => 'npm run storybook',
        'block' => 'false',
    ],
];
```

**`components/atoms/code/code.stories.json`:**

```json
{
  "title": "Atoms/Code",
  "argTypes": {
    "content": { "control": "text" },
    "block": { "control": { "type": "select" }, "options": ["true", "false"] }
  },
  "stories": [
    { "name": "Inline", "parameters": { "server": { "id": "atoms/code" } } },
    { "name": "Block", "args": { "block": "true", "content": "composer test\nnpm run production" }, "parameters": { "server": { "id": "atoms/code" } } }
  ]
}
```

## Wiring

**`resources/css/main.scss`** — add the 3 new atom @use lines after existing atoms:

```scss
@use "../../public/packages/inno_frontend/components/atoms/caption/caption";
@use "../../public/packages/inno_frontend/components/atoms/code/code";
@use "../../public/packages/inno_frontend/components/atoms/label/label";
```

**`public/packages/inno_frontend/components/_storybook/components.scss`** — add the same 3 lines to the atoms section.

## What Doesn't Change

- `global/presets.scss` — keeps base h1-h6, p, .lead, .small styles
- Existing components using Heading or Paragraph — backwards compatible (new props are optional)
- Token files
- Accessibility infrastructure

## Verification

1. `npm run development` — builds successfully
2. Storybook renders all atom stories (200 OK):
   - `atoms/heading` (existing, still works)
   - `atoms/paragraph` (existing, still works)
   - `atoms/label` (new)
   - `atoms/caption` (new)
   - `atoms/code` (new)
3. Existing organisms using Heading/Paragraph render unchanged
4. Axe-core a11y panel shows no violations on any new atom story
