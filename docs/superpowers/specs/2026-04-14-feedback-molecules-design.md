# Feedback Molecules — Sub-project 7

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Add 4 feedback molecules: Alert, Modal, Empty State, Skeleton Loader.

## Architecture

Stateless PHP partials using tokens and a11y helpers. Alert and Modal have minimal vanilla TypeScript for interactive behavior (dismiss, open/close). Modal uses native HTML5 `<dialog>` element — browser handles focus trap, Esc-to-close, inertness natively. Skeleton is pure CSS animation wrapped in `respect-motion`.

## 1. Alert

**Files:**
- `molecules/alert/alert.php`
- `molecules/alert/alert.scss`
- `molecules/alert/alert.ts`
- `molecules/alert/alert.variants.php`
- `molecules/alert/alert.stories.json`

**Props:**
- `variant` — `info` (default), `success`, `warning`, `error`
- `title` — text (optional)
- `message` — text (required)
- `dismissible` — "true"/"false" (default "false")
- `icon` — "true"/"false" (default "true")

**alert.php:**
```php
<?php
$variant = $props['variant'] ?? 'info';
$title = $props['title'] ?? '';
$message = $props['message'] ?? '';
$dismissible = ($props['dismissible'] ?? 'false') === 'true';
$showIcon = ($props['icon'] ?? 'true') === 'true';

$icons = [
    'info' => '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
    'success' => '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
    'warning' => '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
    'error' => '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
];

$role = $variant === 'error' ? 'alert' : 'status';
?>
<div class="alert alert--<?= htmlspecialchars($variant) ?>" role="<?= $role ?>">
    <?php if ($showIcon): ?>
        <span class="alert__icon" aria-hidden="true"><?= $icons[$variant] ?? '' ?></span>
    <?php endif; ?>
    <div class="alert__content">
        <?php if ($title): ?>
            <h4 class="alert__title"><?= htmlspecialchars($title) ?></h4>
        <?php endif; ?>
        <p class="alert__message"><?= htmlspecialchars($message) ?></p>
    </div>
    <?php if ($dismissible): ?>
        <button type="button" class="alert__dismiss" aria-label="Schliessen" data-alert-dismiss>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    <?php endif; ?>
</div>
```

**alert.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.alert {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-4);
  border: 1px solid;
  border-radius: var(--radius-md);
  font-family: var(--text-font-family);

  &--info {
    background: var(--color-status-info-bg);
    border-color: var(--color-status-info-border);
    color: var(--color-status-info-icon);
  }

  &--success {
    background: var(--color-status-success-bg);
    border-color: var(--color-status-success-border);
    color: var(--color-status-success-icon);
  }

  &--warning {
    background: var(--color-status-warning-bg);
    border-color: var(--color-status-warning-border);
    color: var(--color-status-warning-icon);
  }

  &--error {
    background: var(--color-status-error-bg);
    border-color: var(--color-status-error-border);
    color: var(--color-status-error-icon);
  }

  &__icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    display: inline-flex;

    svg {
      width: 100%;
      height: 100%;
    }
  }

  &__content {
    flex: 1;
    min-width: 0;
  }

  &__title {
    margin: 0 0 var(--space-1);
    font-size: 15px;
    font-weight: var(--font-weight-semibold);
    line-height: 1.4;
    color: inherit;
  }

  &__message {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    color: var(--color-text-primary);
  }

  &__dismiss {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: var(--radius-full);
    color: inherit;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;

    @include a11y.respect-motion {
      transition: background var(--duration-fast) var(--ease-standard);
    }

    &:hover {
      background: rgba(0, 0, 0, 0.05);
    }

    @include a11y.focus-visible-ring;
  }
}
```

**alert.ts:**
```ts
// Alert dismiss behavior
document.addEventListener('click', (event) => {
  const target = event.target as HTMLElement;
  const dismissButton = target.closest<HTMLElement>('[data-alert-dismiss]');
  if (!dismissButton) return;
  const alert = dismissButton.closest<HTMLElement>('.alert');
  alert?.remove();
});
```

**alert.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'variant' => 'info',
        'title' => 'Hinweis',
        'message' => 'Deine Änderungen wurden gespeichert.',
        'dismissible' => 'false',
        'icon' => 'true',
    ],
];
```

**alert.stories.json:**
```json
{
  "title": "Molecules/Alert",
  "argTypes": {
    "variant": { "control": { "type": "select" }, "options": ["info", "success", "warning", "error"] },
    "title": { "control": "text" },
    "message": { "control": "text" },
    "dismissible": { "control": { "type": "select" }, "options": ["true", "false"] },
    "icon": { "control": "text" }
  },
  "stories": [
    { "name": "Info", "parameters": { "server": { "id": "molecules/alert" } } },
    { "name": "Success", "args": { "variant": "success", "title": "Erfolg", "message": "Deine Projektidee wurde eingereicht." }, "parameters": { "server": { "id": "molecules/alert" } } },
    { "name": "Warning", "args": { "variant": "warning", "title": "Achtung", "message": "Deine Session läuft in 5 Minuten ab." }, "parameters": { "server": { "id": "molecules/alert" } } },
    { "name": "Error", "args": { "variant": "error", "title": "Fehler", "message": "Die Datei konnte nicht hochgeladen werden." }, "parameters": { "server": { "id": "molecules/alert" } } },
    { "name": "Dismissible", "args": { "dismissible": "true" }, "parameters": { "server": { "id": "molecules/alert" } } },
    { "name": "Without Title", "args": { "title": "" }, "parameters": { "server": { "id": "molecules/alert" } } },
    { "name": "Without Icon", "args": { "icon": "false" }, "parameters": { "server": { "id": "molecules/alert" } } }
  ]
}
```

## 2. Modal

**Files:**
- `molecules/modal/modal.php`
- `molecules/modal/modal.scss`
- `molecules/modal/modal.ts`
- `molecules/modal/modal.variants.php`
- `molecules/modal/modal.stories.json`

**Props:**
- `id` — dialog ID (required)
- `title` — text (required)
- `content` — HTML (main body)
- `footer` — HTML (optional)
- `size` — `sm`, `md` (default), `lg`, `full`
- `open` — "true"/"false" (default "false" — when "true", renders with `open` attribute for Storybook preview)

**modal.php:**
```php
<?php
$id = $props['id'] ?? 'modal-' . uniqid();
$title = $props['title'] ?? '';
$content = $props['content'] ?? '';
$footer = $props['footer'] ?? '';
$size = $props['size'] ?? 'md';
$open = ($props['open'] ?? 'false') === 'true';

$titleId = $id . '-title';
?>
<dialog
    id="<?= htmlspecialchars($id) ?>"
    class="modal modal--<?= htmlspecialchars($size) ?>"
    aria-labelledby="<?= htmlspecialchars($titleId) ?>"
    <?= $open ? 'open' : '' ?>
>
    <div class="modal__header">
        <h2 id="<?= htmlspecialchars($titleId) ?>" class="modal__title"><?= htmlspecialchars($title) ?></h2>
        <button type="button" class="modal__close" aria-label="Schliessen" data-modal-close>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    </div>
    <div class="modal__body"><?= $content ?></div>
    <?php if ($footer): ?>
        <div class="modal__footer"><?= $footer ?></div>
    <?php endif; ?>
</dialog>
```

**modal.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.modal {
  width: calc(100% - var(--space-6));
  padding: 0;
  margin: auto;
  background: var(--color-bg-surface);
  border: 1px solid var(--color-border-subtle);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xl);
  color: var(--color-text-primary);
  font-family: var(--text-font-family);
  max-height: calc(100vh - var(--space-12));
  overflow: hidden;

  &[open] {
    display: flex;
    flex-direction: column;
  }

  &::backdrop {
    background: var(--color-bg-overlay);
  }

  &--sm { max-width: 400px; }
  &--md { max-width: 600px; }
  &--lg { max-width: 800px; }
  &--full { max-width: calc(100vw - var(--space-12)); }

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-5) var(--space-6);
    border-bottom: 1px solid var(--color-border-subtle);
  }

  &__title {
    margin: 0;
    font-size: 20px;
    font-weight: var(--font-weight-semibold);
    line-height: 1.3;
    color: var(--color-text-primary);
  }

  &__close {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: var(--radius-full);
    color: var(--color-text-muted);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;

    @include a11y.respect-motion {
      transition:
        color var(--duration-fast) var(--ease-standard),
        background var(--duration-fast) var(--ease-standard);
    }

    &:hover {
      color: var(--color-text-primary);
      background: var(--color-bg-subtle);
    }

    @include a11y.focus-visible-ring;
  }

  &__body {
    padding: var(--space-6);
    overflow-y: auto;
    flex: 1;
  }

  &__footer {
    display: flex;
    gap: var(--space-3);
    justify-content: flex-end;
    padding: var(--space-4) var(--space-6);
    border-top: 1px solid var(--color-border-subtle);
    background: var(--color-bg-subtle);
  }
}
```

**modal.ts:**
```ts
// Modal open/close behavior using native <dialog> API.
document.addEventListener('click', (event) => {
  const target = event.target as HTMLElement;

  // Open trigger: [data-modal-open="#modal-id"]
  const openTrigger = target.closest<HTMLElement>('[data-modal-open]');
  if (openTrigger) {
    const selector = openTrigger.dataset.modalOpen;
    if (selector) {
      const dialog = document.querySelector<HTMLDialogElement>(selector);
      dialog?.showModal();
      return;
    }
  }

  // Close trigger: button inside modal with [data-modal-close]
  const closeTrigger = target.closest<HTMLElement>('[data-modal-close]');
  if (closeTrigger) {
    const dialog = closeTrigger.closest<HTMLDialogElement>('dialog.modal');
    dialog?.close();
    return;
  }

  // Backdrop click (click landed on the dialog itself, not its content)
  if (target.tagName === 'DIALOG' && target.classList.contains('modal')) {
    const dialog = target as HTMLDialogElement;
    const rect = dialog.getBoundingClientRect();
    const clickedBackdrop =
      event.clientX < rect.left ||
      event.clientX > rect.right ||
      event.clientY < rect.top ||
      event.clientY > rect.bottom;
    if (clickedBackdrop) {
      dialog.close();
    }
  }
});
```

**modal.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'modal-demo',
        'title' => 'Projektidee einreichen',
        'content' => '<p>Möchtest du deine Projektidee für die Innovationsbox einreichen? Fülle das Formular aus und sende es ab. Wir prüfen jede Einreichung sorgfältig.</p>',
        'footer' => '<button type="button" class="button button--ghost button--md" data-modal-close>Abbrechen</button><button type="button" class="button button--primary button--md">Einreichen</button>',
        'size' => 'md',
        'open' => 'true',
    ],
];
```

**modal.stories.json:**
```json
{
  "title": "Molecules/Modal",
  "argTypes": {
    "id": { "control": "text" },
    "title": { "control": "text" },
    "content": { "control": "text" },
    "footer": { "control": "text" },
    "size": { "control": { "type": "select" }, "options": ["sm", "md", "lg", "full"] },
    "open": { "control": { "type": "select" }, "options": ["true", "false"] }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/modal" } } },
    { "name": "Small", "args": { "size": "sm" }, "parameters": { "server": { "id": "molecules/modal" } } },
    { "name": "Large", "args": { "size": "lg" }, "parameters": { "server": { "id": "molecules/modal" } } },
    { "name": "Without Footer", "args": { "footer": "" }, "parameters": { "server": { "id": "molecules/modal" } } }
  ]
}
```

## 3. Empty State

**Files:**
- `molecules/empty-state/empty-state.php`
- `molecules/empty-state/empty-state.scss`
- `molecules/empty-state/empty-state.variants.php`
- `molecules/empty-state/empty-state.stories.json`

**Props:**
- `illustration` — HTML (optional SVG or img markup)
- `title` — text (required)
- `description` — text (optional)
- `ctaLabel` — text (optional — if present, renders primary button)
- `ctaHref` — href for CTA
- `size` — `sm`, `md` (default), `lg`

**empty-state.php:**
```php
<?php
$illustration = $props['illustration'] ?? '';
$title = $props['title'] ?? '';
$description = $props['description'] ?? '';
$ctaLabel = $props['ctaLabel'] ?? '';
$ctaHref = $props['ctaHref'] ?? '#';
$size = $props['size'] ?? 'md';
?>
<div class="empty-state empty-state--<?= htmlspecialchars($size) ?>">
    <?php if ($illustration): ?>
        <div class="empty-state__illustration" aria-hidden="true"><?= $illustration ?></div>
    <?php endif; ?>
    <h3 class="empty-state__title"><?= htmlspecialchars($title) ?></h3>
    <?php if ($description): ?>
        <p class="empty-state__description"><?= htmlspecialchars($description) ?></p>
    <?php endif; ?>
    <?php if ($ctaLabel):
        $props = [
            'variant' => 'primary',
            'size' => $size === 'sm' ? 'sm' : 'md',
            'label' => $ctaLabel,
            'href' => $ctaHref,
        ];
        include DIR_COMPONENTS . '/atoms/button/button.php';
    endif; ?>
</div>
```

**empty-state.scss:**
```scss
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  font-family: var(--text-font-family);
  color: var(--color-text-primary);

  &--sm {
    padding: var(--space-6) var(--space-4);
    gap: var(--space-3);

    .empty-state__illustration {
      width: 80px;
      height: 80px;
    }
  }

  &--md {
    padding: var(--space-12) var(--space-6);
    gap: var(--space-4);

    .empty-state__illustration {
      width: 120px;
      height: 120px;
    }
  }

  &--lg {
    padding: var(--space-16) var(--space-8);
    gap: var(--space-5);

    .empty-state__illustration {
      width: 160px;
      height: 160px;
    }
  }

  &__illustration {
    color: var(--color-neutral-400);
    display: inline-flex;
    align-items: center;
    justify-content: center;

    svg, img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
  }

  &__title {
    margin: 0;
    font-size: 20px;
    font-weight: var(--font-weight-semibold);
    line-height: 1.3;
  }

  &__description {
    margin: 0;
    max-width: 40ch;
    font-size: 15px;
    line-height: 1.5;
    color: var(--color-text-secondary);
  }
}
```

**empty-state.variants.php:**
```php
<?php

declare(strict_types=1);

$defaultIllustration = '<svg viewBox="0 0 200 200" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><rect x="20" y="40" width="160" height="120" rx="8" fill="none" stroke="currentColor" stroke-width="4"/><path d="M60 80 L140 80 M60 100 L120 100 M60 120 L130 120" stroke="currentColor" stroke-width="4" stroke-linecap="round" fill="none"/></svg>';

return [
    'defaults' => [
        'illustration' => $defaultIllustration,
        'title' => 'Noch keine Projektideen',
        'description' => 'Sei die erste Person, die eine klimarelevante Idee für das Glarnerland einreicht.',
        'ctaLabel' => 'Idee einreichen',
        'ctaHref' => '/projektidee-einbringen',
        'size' => 'md',
    ],
];
```

**empty-state.stories.json:**
```json
{
  "title": "Molecules/Empty State",
  "argTypes": {
    "illustration": { "control": "text" },
    "title": { "control": "text" },
    "description": { "control": "text" },
    "ctaLabel": { "control": "text" },
    "ctaHref": { "control": "text" },
    "size": { "control": { "type": "select" }, "options": ["sm", "md", "lg"] }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/empty-state" } } },
    { "name": "Without CTA", "args": { "ctaLabel": "" }, "parameters": { "server": { "id": "molecules/empty-state" } } },
    { "name": "Without Description", "args": { "description": "" }, "parameters": { "server": { "id": "molecules/empty-state" } } },
    { "name": "Small", "args": { "size": "sm" }, "parameters": { "server": { "id": "molecules/empty-state" } } },
    { "name": "Large", "args": { "size": "lg" }, "parameters": { "server": { "id": "molecules/empty-state" } } }
  ]
}
```

## 4. Skeleton Loader

**Files:**
- `molecules/skeleton/skeleton.php`
- `molecules/skeleton/skeleton.scss`
- `molecules/skeleton/skeleton.variants.php`
- `molecules/skeleton/skeleton.stories.json`

**Props:**
- `variant` — `text`, `heading`, `rectangle`, `circle`, `image` (default `text`)
- `width` — CSS value (default `100%`)
- `height` — CSS value (default varies by variant)
- `count` — number of repeated lines for `text` variant (default 1)

**skeleton.php:**
```php
<?php
$variant = $props['variant'] ?? 'text';
$width = $props['width'] ?? '';
$height = $props['height'] ?? '';
$count = max(1, (int) ($props['count'] ?? 1));

// Default dimensions per variant
$defaults = [
    'text' => ['width' => '100%', 'height' => '1em'],
    'heading' => ['width' => '60%', 'height' => '1.5em'],
    'rectangle' => ['width' => '100%', 'height' => '120px'],
    'circle' => ['width' => '40px', 'height' => '40px'],
    'image' => ['width' => '100%', 'height' => '200px'],
];
$d = $defaults[$variant] ?? $defaults['text'];
$w = $width !== '' ? $width : $d['width'];
$h = $height !== '' ? $height : $d['height'];

// For text count > 1, vary widths for natural look
$widths = ['100%', '95%', '88%', '92%', '70%'];
?>
<?php if ($variant === 'text' && $count > 1): ?>
    <div class="skeleton-group" aria-hidden="true">
        <?php for ($i = 0; $i < $count; $i++):
            $lineWidth = $widths[$i % count($widths)];
        ?>
            <span class="skeleton skeleton--<?= htmlspecialchars($variant) ?>" style="width: <?= htmlspecialchars($lineWidth) ?>; height: <?= htmlspecialchars($h) ?>;"></span>
        <?php endfor; ?>
    </div>
<?php else: ?>
    <span class="skeleton skeleton--<?= htmlspecialchars($variant) ?>" style="width: <?= htmlspecialchars($w) ?>; height: <?= htmlspecialchars($h) ?>;" aria-hidden="true"></span>
<?php endif; ?>
```

**skeleton.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.skeleton {
  display: block;
  background: var(--color-neutral-200);
  border-radius: var(--radius-sm);
  background-image: linear-gradient(
    90deg,
    var(--color-neutral-200) 0%,
    var(--color-neutral-100) 50%,
    var(--color-neutral-200) 100%
  );
  background-size: 200% 100%;

  @include a11y.respect-motion {
    animation: skeleton-shimmer 1.5s ease-in-out infinite;
  }

  &--text,
  &--heading {
    display: inline-block;
    vertical-align: middle;
    border-radius: var(--radius-sm);
  }

  &--rectangle,
  &--image {
    border-radius: var(--radius-md);
  }

  &--circle {
    border-radius: var(--radius-full);
  }
}

.skeleton-group {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

@keyframes skeleton-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
```

**skeleton.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'variant' => 'text',
        'width' => '',
        'height' => '',
        'count' => '1',
    ],
];
```

**skeleton.stories.json:**
```json
{
  "title": "Molecules/Skeleton",
  "argTypes": {
    "variant": { "control": { "type": "select" }, "options": ["text", "heading", "rectangle", "circle", "image"] },
    "width": { "control": "text" },
    "height": { "control": "text" },
    "count": { "control": "text" }
  },
  "stories": [
    { "name": "Text", "parameters": { "server": { "id": "molecules/skeleton" } } },
    { "name": "Text Multi-line", "args": { "count": "3" }, "parameters": { "server": { "id": "molecules/skeleton" } } },
    { "name": "Heading", "args": { "variant": "heading" }, "parameters": { "server": { "id": "molecules/skeleton" } } },
    { "name": "Rectangle", "args": { "variant": "rectangle" }, "parameters": { "server": { "id": "molecules/skeleton" } } },
    { "name": "Circle", "args": { "variant": "circle" }, "parameters": { "server": { "id": "molecules/skeleton" } } },
    { "name": "Circle Large", "args": { "variant": "circle", "width": "80px", "height": "80px" }, "parameters": { "server": { "id": "molecules/skeleton" } } },
    { "name": "Image", "args": { "variant": "image" }, "parameters": { "server": { "id": "molecules/skeleton" } } }
  ]
}
```

## Wiring

**`resources/css/main.scss`** — add 4 `@use` lines in molecules section (alphabetical):
```scss
@use "../../public/packages/inno_frontend/components/molecules/alert/alert";
@use "../../public/packages/inno_frontend/components/molecules/empty-state/empty-state";
@use "../../public/packages/inno_frontend/components/molecules/modal/modal";
@use "../../public/packages/inno_frontend/components/molecules/skeleton/skeleton";
```

**`_storybook/components.scss`** — same 4 lines in Molecules section.

**`_storybook/components.ts`** — add 2 imports (for molecules with JS):
```ts
import '../molecules/alert/alert';
import '../molecules/modal/modal';
```

## What Doesn't Change

- Atoms
- Tokens, a11y infrastructure
- Other molecules, organisms, templates
- Existing JS (navigation-drawer)
- Storybook config

## Verification

1. `npm run development` — compiles without errors
2. All 4 molecules return 200:
   - `molecules/alert`
   - `molecules/modal` (renders with `open` attribute — visible in preview)
   - `molecules/empty-state`
   - `molecules/skeleton`
3. Modal uses native `<dialog>` — Esc closes, focus traps inside, backdrop dims background
4. Alert dismiss button removes alert from DOM
5. Empty State CTA button renders as primary button (composition works)
6. Skeleton animation stops with `prefers-reduced-motion: reduce`
7. Axe panel shows no violations on any story
