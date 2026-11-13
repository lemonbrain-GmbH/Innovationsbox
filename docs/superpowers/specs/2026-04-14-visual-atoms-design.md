# Visual & Interactive Atoms — Sub-project 5

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Enhance Button and add 6 new atoms (Badge, Avatar, Icon, Divider, Spinner, Tooltip) using design tokens and a11y helpers.

## Architecture

All atoms are stateless PHP partials. Uses design tokens from Sub-project 1, a11y mixins from Sub-project 2, same patterns as Sub-projects 3-4. Button is the only update; the rest are new.

## 1. Button (enhanced)

**Files:**
- Modify: `atoms/button/button.php`
- Modify: `atoms/button/button.scss`
- Modify: `atoms/button/button.variants.php`
- Modify: `atoms/button/button.stories.json`

**Props:**
- `variant` — `primary` (default), `secondary`, `ghost`, `danger`
- `size` — `sm`, `md` (default), `lg` (legacy `default`/`small`/`large` still supported via alias)
- `label` — text
- `href` — if present, renders as `<a>`; else `<button>`
- `type` — `button` (default), `submit`, `reset` (only applies to `<button>`)
- `disabled` — "true"/"false"
- `loading` — "true"/"false"
- `iconLeft` — raw HTML for left icon (empty by default)
- `iconRight` — raw HTML for right icon
- `fullWidth` — "true"/"false"

**button.php:**
```php
<?php
$variant = $props['variant'] ?? 'primary';
$sizeInput = $props['size'] ?? 'md';
$sizeMap = ['default' => 'md', 'small' => 'sm', 'large' => 'lg'];
$size = $sizeMap[$sizeInput] ?? $sizeInput;
$label = $props['label'] ?? 'Button';
$href = $props['href'] ?? '';
$type = $props['type'] ?? 'button';
$disabled = ($props['disabled'] ?? 'false') === 'true';
$loading = ($props['loading'] ?? 'false') === 'true';
$iconLeft = $props['iconLeft'] ?? '';
$iconRight = $props['iconRight'] ?? '';
$fullWidth = ($props['fullWidth'] ?? 'false') === 'true';

$classes = ['button', 'button--' . $variant, 'button--' . $size];
if ($loading) {
    $classes[] = 'button--loading';
}
if ($fullWidth) {
    $classes[] = 'button--full-width';
}

$classAttr = implode(' ', $classes);

$inner = '';
if ($iconLeft) {
    $inner .= '<span class="button__icon button__icon--left" aria-hidden="true">' . $iconLeft . '</span>';
}
$inner .= '<span class="button__label">' . htmlspecialchars($label) . '</span>';
if ($iconRight) {
    $inner .= '<span class="button__icon button__icon--right" aria-hidden="true">' . $iconRight . '</span>';
}
if ($loading) {
    $inner .= '<span class="button__spinner" aria-hidden="true"><svg viewBox="0 0 24 24" class="spinner__svg"><circle cx="12" cy="12" r="10" class="spinner__track"/><circle cx="12" cy="12" r="10" class="spinner__arc"/></svg></span>';
}

if ($href) {
    $ariaDisabled = $disabled ? ' aria-disabled="true"' : '';
    $ariaBusy = $loading ? ' aria-busy="true"' : '';
    echo '<a href="' . htmlspecialchars($href) . '" class="' . $classAttr . '"' . $ariaDisabled . $ariaBusy . '>' . $inner . '</a>';
} else {
    $disabledAttr = $disabled || $loading ? ' disabled' : '';
    $ariaBusy = $loading ? ' aria-busy="true"' : '';
    echo '<button type="' . htmlspecialchars($type) . '" class="' . $classAttr . '"' . $disabledAttr . $ariaBusy . '>' . $inner . '</button>';
}
```

**button.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  position: relative;
  font-family: var(--text-font-family);
  font-weight: var(--font-weight-semibold);
  text-decoration: none;
  border: 1px solid transparent;
  border-radius: var(--radius-md);
  cursor: pointer;
  white-space: nowrap;

  @include a11y.respect-motion {
    transition:
      background-color var(--duration-fast) var(--ease-standard),
      border-color var(--duration-fast) var(--ease-standard),
      color var(--duration-fast) var(--ease-standard);
  }

  @include a11y.focus-visible-ring;

  &:disabled,
  &[aria-disabled="true"] {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
  }

  // Variants
  &--primary {
    background: var(--color-action-primary);
    color: var(--color-action-primary-text);
    border-color: var(--color-action-primary);

    &:hover {
      background: var(--color-action-primary-hover);
      border-color: var(--color-action-primary-hover);
    }
  }

  &--secondary {
    background: var(--color-action-secondary);
    color: var(--color-action-secondary-text);
    border-color: var(--color-action-secondary);

    &:hover {
      background: var(--color-action-secondary-hover);
      border-color: var(--color-action-secondary-hover);
    }
  }

  &--ghost {
    background: transparent;
    color: var(--color-text-primary);
    border-color: var(--color-border-default);

    &:hover {
      background: var(--color-bg-subtle);
      border-color: var(--color-border-strong);
    }
  }

  &--danger {
    background: var(--color-action-danger);
    color: var(--color-action-danger-text);
    border-color: var(--color-action-danger);

    &:hover {
      background: var(--color-action-danger-hover);
      border-color: var(--color-action-danger-hover);
    }
  }

  // Sizes
  &--sm {
    padding: var(--space-2) var(--space-3);
    font-size: 14px;
    line-height: 1.3;
  }

  &--md {
    padding: var(--space-3) var(--space-5);
    font-size: 16px;
    line-height: 1.4;
  }

  &--lg {
    padding: var(--space-4) var(--space-6);
    font-size: 18px;
    line-height: 1.4;
  }

  // Full width
  &--full-width {
    display: flex;
    width: 100%;
  }

  // Loading state — hide label/icons, show spinner centered
  &--loading {
    pointer-events: none;

    .button__label,
    .button__icon {
      opacity: 0;
    }
  }

  &__spinner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    color: inherit;

    .spinner__svg {
      width: 100%;
      height: 100%;

      @include a11y.respect-motion {
        animation: spinner-rotate 1s linear infinite;
      }
    }

    .spinner__track {
      stroke: currentColor;
      stroke-width: 2;
      fill: none;
      opacity: 0.2;
    }

    .spinner__arc {
      stroke: currentColor;
      stroke-width: 2;
      fill: none;
      stroke-linecap: round;
      stroke-dasharray: 62.8;
      stroke-dashoffset: 47.1;
    }
  }

  &__icon {
    display: inline-flex;
    align-items: center;
    line-height: 1;
  }
}

@keyframes spinner-rotate {
  to {
    transform: rotate(360deg);
  }
}
```

**button.variants.php:**
```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';

return [
    'variants' => ['primary', 'secondary', 'ghost', 'danger'],
    'sizes' => ['sm', 'md', 'lg'],
    'defaults' => [
        'variant' => 'primary',
        'size' => 'md',
        'label' => $data['buttons']['submit'],
        'href' => '#',
        'type' => 'button',
        'disabled' => 'false',
        'loading' => 'false',
        'iconLeft' => '',
        'iconRight' => '',
        'fullWidth' => 'false',
    ],
];
```

**button.stories.json:**
```json
{
  "title": "Atoms/Button",
  "argTypes": {
    "variant": { "control": { "type": "select" }, "options": ["primary", "secondary", "ghost", "danger"] },
    "size": { "control": { "type": "select" }, "options": ["sm", "md", "lg"] },
    "label": { "control": "text" },
    "href": { "control": "text" },
    "type": { "control": { "type": "select" }, "options": ["button", "submit", "reset"] },
    "disabled": { "control": { "type": "select" }, "options": ["true", "false"] },
    "loading": { "control": { "type": "select" }, "options": ["true", "false"] },
    "fullWidth": { "control": { "type": "select" }, "options": ["true", "false"] }
  },
  "stories": [
    { "name": "Primary", "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Secondary", "args": { "variant": "secondary" }, "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Ghost", "args": { "variant": "ghost" }, "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Danger", "args": { "variant": "danger", "label": "Löschen" }, "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Small", "args": { "size": "sm" }, "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Large", "args": { "size": "lg" }, "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Disabled", "args": { "disabled": "true" }, "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Loading", "args": { "loading": "true" }, "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Full Width", "args": { "fullWidth": "true" }, "parameters": { "server": { "id": "atoms/button" } } }
  ]
}
```

## 2. Badge

**Files (new):**
- `atoms/badge/badge.php`
- `atoms/badge/badge.scss`
- `atoms/badge/badge.variants.php`
- `atoms/badge/badge.stories.json`

**Props:** `label`, `variant` (neutral/primary/success/warning/error/info), `size` (sm/md), `shape` (rounded/pill), `icon` (optional HTML).

**badge.php:**
```php
<?php
$label = $props['label'] ?? 'Badge';
$variant = $props['variant'] ?? 'neutral';
$size = $props['size'] ?? 'md';
$shape = $props['shape'] ?? 'rounded';
$icon = $props['icon'] ?? '';

$classes = ['badge', 'badge--' . $variant, 'badge--' . $size, 'badge--' . $shape];
?>
<span class="<?= implode(' ', $classes) ?>">
    <?php if ($icon): ?><span class="badge__icon" aria-hidden="true"><?= $icon ?></span><?php endif; ?>
    <?= htmlspecialchars($label) ?>
</span>
```

**badge.scss:**
```scss
.badge {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  font-family: var(--text-font-family);
  font-weight: var(--font-weight-medium);
  line-height: 1;
  white-space: nowrap;
  border: 1px solid transparent;

  &--sm {
    padding: 2px 8px;
    font-size: 11px;
  }

  &--md {
    padding: 4px 10px;
    font-size: 13px;
  }

  &--rounded {
    border-radius: var(--radius-sm);
  }

  &--pill {
    border-radius: var(--radius-full);
  }

  &--neutral {
    background: var(--color-neutral-100);
    color: var(--color-neutral-900);
    border-color: var(--color-neutral-200);
  }

  &--primary {
    background: var(--color-brand-primary-50);
    color: var(--color-brand-primary-900);
    border-color: var(--color-brand-primary-200);
  }

  &--success {
    background: var(--color-status-success-bg);
    color: var(--color-status-success-icon);
    border-color: var(--color-status-success-border);
  }

  &--warning {
    background: var(--color-status-warning-bg);
    color: var(--color-status-warning-icon);
    border-color: var(--color-status-warning-border);
  }

  &--error {
    background: var(--color-status-error-bg);
    color: var(--color-status-error-icon);
    border-color: var(--color-status-error-border);
  }

  &--info {
    background: var(--color-status-info-bg);
    color: var(--color-status-info-icon);
    border-color: var(--color-status-info-border);
  }

  &__icon {
    display: inline-flex;
    width: 12px;
    height: 12px;
  }
}
```

**badge.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'label' => 'Neu',
        'variant' => 'neutral',
        'size' => 'md',
        'shape' => 'rounded',
        'icon' => '',
    ],
];
```

**badge.stories.json:**
```json
{
  "title": "Atoms/Badge",
  "argTypes": {
    "label": { "control": "text" },
    "variant": { "control": { "type": "select" }, "options": ["neutral", "primary", "success", "warning", "error", "info"] },
    "size": { "control": { "type": "select" }, "options": ["sm", "md"] },
    "shape": { "control": { "type": "select" }, "options": ["rounded", "pill"] },
    "icon": { "control": "text" }
  },
  "stories": [
    { "name": "Neutral", "parameters": { "server": { "id": "atoms/badge" } } },
    { "name": "Primary", "args": { "variant": "primary", "label": "Aktiv" }, "parameters": { "server": { "id": "atoms/badge" } } },
    { "name": "Success", "args": { "variant": "success", "label": "Genehmigt" }, "parameters": { "server": { "id": "atoms/badge" } } },
    { "name": "Warning", "args": { "variant": "warning", "label": "Prüfung" }, "parameters": { "server": { "id": "atoms/badge" } } },
    { "name": "Error", "args": { "variant": "error", "label": "Abgelehnt" }, "parameters": { "server": { "id": "atoms/badge" } } },
    { "name": "Info", "args": { "variant": "info", "label": "Info" }, "parameters": { "server": { "id": "atoms/badge" } } },
    { "name": "Pill", "args": { "variant": "primary", "shape": "pill", "label": "3 neu" }, "parameters": { "server": { "id": "atoms/badge" } } },
    { "name": "Small", "args": { "size": "sm", "label": "SM" }, "parameters": { "server": { "id": "atoms/badge" } } }
  ]
}
```

## 3. Avatar

**Props:** `src`, `alt`, `name`, `size` (xs/sm/md/lg/xl), `shape` (circle/square).

**avatar.php:**
```php
<?php
$src = $props['src'] ?? '';
$alt = $props['alt'] ?? '';
$name = $props['name'] ?? '';
$size = $props['size'] ?? 'md';
$shape = $props['shape'] ?? 'circle';

// Derive up to 2 initials from $name
$initials = '';
if ($name) {
    $words = preg_split('/\s+/', trim($name));
    $first = mb_substr($words[0] ?? '', 0, 1);
    $second = count($words) > 1 ? mb_substr(end($words), 0, 1) : '';
    $initials = mb_strtoupper($first . $second);
}

$classes = ['avatar', 'avatar--' . $size, 'avatar--' . $shape];
?>
<?php if ($src): ?>
<span class="<?= implode(' ', $classes) ?>">
    <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($alt ?: $name) ?>" class="avatar__img">
</span>
<?php else: ?>
<span class="<?= implode(' ', $classes) ?>" <?= $name ? 'aria-label="' . htmlspecialchars($name) . '"' : 'aria-hidden="true"' ?>>
    <span class="avatar__initials"><?= htmlspecialchars($initials) ?></span>
</span>
<?php endif; ?>
```

**avatar.scss:**
```scss
.avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: hidden;
  background: var(--color-brand-accent-500);
  color: var(--color-text-inverse);
  font-family: var(--text-font-family);
  font-weight: var(--font-weight-semibold);

  &--xs { width: 24px; height: 24px; font-size: 10px; }
  &--sm { width: 32px; height: 32px; font-size: 13px; }
  &--md { width: 40px; height: 40px; font-size: 16px; }
  &--lg { width: 48px; height: 48px; font-size: 19px; }
  &--xl { width: 64px; height: 64px; font-size: 25px; }

  &--circle {
    border-radius: var(--radius-full);
  }

  &--square {
    border-radius: var(--radius-md);
  }

  &__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  &__initials {
    line-height: 1;
  }
}
```

**avatar.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'src' => '',
        'alt' => '',
        'name' => 'Anna Müller',
        'size' => 'md',
        'shape' => 'circle',
    ],
];
```

**avatar.stories.json:**
```json
{
  "title": "Atoms/Avatar",
  "argTypes": {
    "src": { "control": "text" },
    "alt": { "control": "text" },
    "name": { "control": "text" },
    "size": { "control": { "type": "select" }, "options": ["xs", "sm", "md", "lg", "xl"] },
    "shape": { "control": { "type": "select" }, "options": ["circle", "square"] }
  },
  "stories": [
    { "name": "Initials", "parameters": { "server": { "id": "atoms/avatar" } } },
    { "name": "With Image", "args": { "src": "https://picsum.photos/seed/person/200/200", "alt": "Anna Müller" }, "parameters": { "server": { "id": "atoms/avatar" } } },
    { "name": "Square", "args": { "shape": "square" }, "parameters": { "server": { "id": "atoms/avatar" } } },
    { "name": "Small", "args": { "size": "sm" }, "parameters": { "server": { "id": "atoms/avatar" } } },
    { "name": "Large", "args": { "size": "lg" }, "parameters": { "server": { "id": "atoms/avatar" } } },
    { "name": "XL", "args": { "size": "xl" }, "parameters": { "server": { "id": "atoms/avatar" } } }
  ]
}
```

## 4. Icon

**Props:** `svg` (raw SVG markup), `label` (empty = decorative), `size` (xs/sm/md/lg/xl).

**icon.php:**
```php
<?php
$svg = $props['svg'] ?? '';
$label = $props['label'] ?? '';
$size = $props['size'] ?? 'md';

$classes = ['icon', 'icon--' . $size];
?>
<span class="<?= implode(' ', $classes) ?>" <?= $label ? 'role="img" aria-label="' . htmlspecialchars($label) . '"' : 'aria-hidden="true"' ?>>
    <?= $svg ?>
</span>
```

**icon.scss:**
```scss
.icon {
  display: inline-flex;
  flex-shrink: 0;
  line-height: 1;

  svg {
    width: 100%;
    height: 100%;
    fill: currentColor;
  }

  &--xs { width: 12px; height: 12px; }
  &--sm { width: 16px; height: 16px; }
  &--md { width: 20px; height: 20px; }
  &--lg { width: 24px; height: 24px; }
  &--xl { width: 32px; height: 32px; }
}
```

**icon.variants.php:** Includes stock SVGs for common icons (check, close, chevron-down, menu, search, info, warning, error, success). Stories use these.

```php
<?php

declare(strict_types=1);

$icons = [
    'check' => '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
    'close' => '<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
    'chevron-down' => '<svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6z"/></svg>',
    'menu' => '<svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>',
    'search' => '<svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 0 0 1.48-5.34c-.47-2.78-2.79-5-5.59-5.34a6.505 6.505 0 0 0-7.27 7.27c.34 2.8 2.56 5.12 5.34 5.59a6.5 6.5 0 0 0 5.34-1.48l.27.28v.79l4.25 4.25c.41.41 1.08.41 1.49 0 .41-.41.41-1.08 0-1.49L15.5 14zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>',
    'info' => '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
    'warning' => '<svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
    'error' => '<svg viewBox="0 0 24 24"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
];

return [
    'defaults' => [
        'svg' => $icons['check'],
        'label' => '',
        'size' => 'md',
    ],
    'icons' => $icons,
];
```

**icon.stories.json:**
```json
{
  "title": "Atoms/Icon",
  "argTypes": {
    "svg": { "control": "text" },
    "label": { "control": "text" },
    "size": { "control": { "type": "select" }, "options": ["xs", "sm", "md", "lg", "xl"] }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/icon" } } },
    { "name": "With Label", "args": { "label": "Bestätigen" }, "parameters": { "server": { "id": "atoms/icon" } } },
    { "name": "Small", "args": { "size": "sm" }, "parameters": { "server": { "id": "atoms/icon" } } },
    { "name": "Large", "args": { "size": "lg" }, "parameters": { "server": { "id": "atoms/icon" } } },
    { "name": "XL", "args": { "size": "xl" }, "parameters": { "server": { "id": "atoms/icon" } } }
  ]
}
```

## 5. Divider

**Props:** `orientation` (horizontal/vertical), `variant` (solid/dashed), `label` (optional).

**divider.php:**
```php
<?php
$orientation = $props['orientation'] ?? 'horizontal';
$variant = $props['variant'] ?? 'solid';
$label = $props['label'] ?? '';

$classes = ['divider', 'divider--' . $orientation, 'divider--' . $variant];
if ($label) {
    $classes[] = 'divider--labeled';
}
?>
<?php if ($label): ?>
<div class="<?= implode(' ', $classes) ?>" role="separator" aria-orientation="<?= htmlspecialchars($orientation) ?>">
    <span class="divider__label"><?= htmlspecialchars($label) ?></span>
</div>
<?php elseif ($orientation === 'vertical'): ?>
<div class="<?= implode(' ', $classes) ?>" role="separator" aria-orientation="vertical"></div>
<?php else: ?>
<hr class="<?= implode(' ', $classes) ?>">
<?php endif; ?>
```

**divider.scss:**
```scss
.divider {
  border: none;
  margin: 0;

  &--horizontal {
    width: 100%;
    height: 1px;
    background: var(--color-border-subtle);
  }

  &--horizontal.divider--dashed {
    background: none;
    border-top: 1px dashed var(--color-border-subtle);
    height: 0;
  }

  &--vertical {
    width: 1px;
    height: 100%;
    min-height: 16px;
    align-self: stretch;
    background: var(--color-border-subtle);
  }

  &--vertical.divider--dashed {
    background: none;
    border-left: 1px dashed var(--color-border-subtle);
    width: 0;
  }

  &--labeled {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    background: transparent;
    height: auto;
    color: var(--color-text-muted);
    font-family: var(--text-font-family);
    font-size: 13px;
  }

  &--labeled::before,
  &--labeled::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--color-border-subtle);
  }
}
```

**divider.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'orientation' => 'horizontal',
        'variant' => 'solid',
        'label' => '',
    ],
];
```

**divider.stories.json:**
```json
{
  "title": "Atoms/Divider",
  "argTypes": {
    "orientation": { "control": { "type": "select" }, "options": ["horizontal", "vertical"] },
    "variant": { "control": { "type": "select" }, "options": ["solid", "dashed"] },
    "label": { "control": "text" }
  },
  "stories": [
    { "name": "Solid", "parameters": { "server": { "id": "atoms/divider" } } },
    { "name": "Dashed", "args": { "variant": "dashed" }, "parameters": { "server": { "id": "atoms/divider" } } },
    { "name": "With Label", "args": { "label": "oder" }, "parameters": { "server": { "id": "atoms/divider" } } }
  ]
}
```

## 6. Spinner

**Props:** `size` (xs/sm/md/lg/xl), `label`.

**spinner.php:**
```php
<?php
$size = $props['size'] ?? 'md';
$label = $props['label'] ?? 'Wird geladen…';

$classes = ['spinner', 'spinner--' . $size];
?>
<span class="<?= implode(' ', $classes) ?>" role="status" aria-label="<?= htmlspecialchars($label) ?>">
    <svg viewBox="0 0 24 24" class="spinner__svg" aria-hidden="true">
        <circle cx="12" cy="12" r="10" class="spinner__track"></circle>
        <circle cx="12" cy="12" r="10" class="spinner__arc"></circle>
    </svg>
</span>
```

**spinner.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.spinner {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: inherit;
  flex-shrink: 0;

  &--xs { width: 12px; height: 12px; }
  &--sm { width: 16px; height: 16px; }
  &--md { width: 20px; height: 20px; }
  &--lg { width: 24px; height: 24px; }
  &--xl { width: 32px; height: 32px; }

  &__svg {
    width: 100%;
    height: 100%;

    @include a11y.respect-motion {
      animation: spinner-rotate 1s linear infinite;
    }
  }

  &__track {
    stroke: currentColor;
    stroke-width: 2;
    fill: none;
    opacity: 0.2;
  }

  &__arc {
    stroke: currentColor;
    stroke-width: 2;
    fill: none;
    stroke-linecap: round;
    stroke-dasharray: 62.8;
    stroke-dashoffset: 47.1;
  }
}

@keyframes spinner-rotate {
  to { transform: rotate(360deg); }
}
```

**spinner.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'size' => 'md',
        'label' => 'Wird geladen…',
    ],
];
```

**spinner.stories.json:**
```json
{
  "title": "Atoms/Spinner",
  "argTypes": {
    "size": { "control": { "type": "select" }, "options": ["xs", "sm", "md", "lg", "xl"] },
    "label": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/spinner" } } },
    { "name": "Small", "args": { "size": "sm" }, "parameters": { "server": { "id": "atoms/spinner" } } },
    { "name": "Large", "args": { "size": "lg" }, "parameters": { "server": { "id": "atoms/spinner" } } },
    { "name": "XL", "args": { "size": "xl" }, "parameters": { "server": { "id": "atoms/spinner" } } }
  ]
}
```

## 7. Tooltip

**Props:** `content`, `position` (top/right/bottom/left), `trigger` (HTML).

**tooltip.php:**
```php
<?php
$content = $props['content'] ?? '';
$position = $props['position'] ?? 'top';
$trigger = $props['trigger'] ?? 'Hover me';
$id = $props['id'] ?? 'tooltip-' . uniqid();
?>
<span class="tooltip tooltip--<?= htmlspecialchars($position) ?>">
    <span class="tooltip__trigger" tabindex="0" aria-describedby="<?= htmlspecialchars($id) ?>"><?= $trigger ?></span>
    <span class="tooltip__bubble" role="tooltip" id="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($content) ?></span>
</span>
```

**tooltip.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.tooltip {
  position: relative;
  display: inline-block;

  &__trigger {
    cursor: help;
    @include a11y.focus-visible-ring;
  }

  &__bubble {
    position: absolute;
    z-index: var(--z-tooltip);
    padding: var(--space-2) var(--space-3);
    background: var(--color-bg-emphasis);
    color: var(--color-text-inverse);
    border-radius: var(--radius-sm);
    font-family: var(--text-font-family);
    font-size: 13px;
    line-height: 1.4;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;

    @include a11y.respect-motion {
      transition: opacity var(--duration-fast) var(--ease-standard);
    }
  }

  &:hover .tooltip__bubble,
  &:focus-within .tooltip__bubble {
    opacity: 1;
  }

  // Positions
  &--top .tooltip__bubble {
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-bottom: var(--space-2);
  }

  &--bottom .tooltip__bubble {
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-top: var(--space-2);
  }

  &--left .tooltip__bubble {
    right: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-right: var(--space-2);
  }

  &--right .tooltip__bubble {
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-left: var(--space-2);
  }

  // Arrow
  &__bubble::before {
    content: '';
    position: absolute;
    width: 0;
    height: 0;
    border: 4px solid transparent;
  }

  &--top .tooltip__bubble::before {
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border-top-color: var(--color-bg-emphasis);
  }

  &--bottom .tooltip__bubble::before {
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border-bottom-color: var(--color-bg-emphasis);
  }

  &--left .tooltip__bubble::before {
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    border-left-color: var(--color-bg-emphasis);
  }

  &--right .tooltip__bubble::before {
    right: 100%;
    top: 50%;
    transform: translateY(-50%);
    border-right-color: var(--color-bg-emphasis);
  }
}
```

**tooltip.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'content' => 'Zusätzliche Information',
        'position' => 'top',
        'trigger' => 'Hover me',
        'id' => 'tooltip-demo',
    ],
];
```

**tooltip.stories.json:**
```json
{
  "title": "Atoms/Tooltip",
  "argTypes": {
    "content": { "control": "text" },
    "position": { "control": { "type": "select" }, "options": ["top", "right", "bottom", "left"] },
    "trigger": { "control": "text" }
  },
  "stories": [
    { "name": "Top", "parameters": { "server": { "id": "atoms/tooltip" } } },
    { "name": "Bottom", "args": { "position": "bottom" }, "parameters": { "server": { "id": "atoms/tooltip" } } },
    { "name": "Left", "args": { "position": "left" }, "parameters": { "server": { "id": "atoms/tooltip" } } },
    { "name": "Right", "args": { "position": "right" }, "parameters": { "server": { "id": "atoms/tooltip" } } }
  ]
}
```

## Wiring

**`resources/css/main.scss`** — add 6 `@use` lines for new atoms (button already there):
```scss
@use "../../public/packages/inno_frontend/components/atoms/avatar/avatar";
@use "../../public/packages/inno_frontend/components/atoms/badge/badge";
@use "../../public/packages/inno_frontend/components/atoms/divider/divider";
@use "../../public/packages/inno_frontend/components/atoms/icon/icon";
@use "../../public/packages/inno_frontend/components/atoms/spinner/spinner";
@use "../../public/packages/inno_frontend/components/atoms/tooltip/tooltip";
```

**`_storybook/components.scss`** — same 6 lines.

## What Doesn't Change

- Existing button consumers — legacy sizes (`default`/`small`/`large`) still work via `$sizeMap` in button.php
- Other atoms, molecules, organisms
- Tokens, a11y infrastructure
- Storybook config

## Verification

1. `npm run development` — compiles without errors
2. Each atom returns 200:
   - `atoms/button`, `atoms/badge`, `atoms/avatar`, `atoms/icon`, `atoms/divider`, `atoms/spinner`, `atoms/tooltip`
3. Button loading state shows spinner
4. Existing button consumers (feature-button molecule, feature_link block) render unchanged
5. Tooltip shows on keyboard focus (tab to trigger)
6. Spinner animation stops when `prefers-reduced-motion: reduce` is set
7. Axe panel shows no violations on any story
