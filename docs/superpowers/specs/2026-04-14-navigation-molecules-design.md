# Navigation Molecules — Sub-project 8

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Add Dropdown, Tabs, Breadcrumb, and Pagination molecules. Compose existing atoms, follow WAI-ARIA patterns, reuse existing tokens.

## Architecture

Stateless PHP partials for markup. Dropdown and Tabs ship a companion TypeScript file for interaction (keyboard + focus). Breadcrumb and Pagination are CSS-only (server-rendered links). All four follow the same prop/file conventions as existing molecules.

## 1. Dropdown Menu

**Files:**
- `molecules/dropdown/dropdown.php`
- `molecules/dropdown/dropdown.scss`
- `molecules/dropdown/dropdown.ts`
- `molecules/dropdown/dropdown.variants.php`
- `molecules/dropdown/dropdown.stories.json`

**Props:**
- `id` — base ID (required; menu id derived as `{id}-menu`)
- `triggerLabel` — button text (default: `Aktionen`)
- `items` — JSON array of `{label, href?, danger?: "true"/"false", divider?: "true"/"false", disabled?: "true"/"false"}`
- `position` — `bottom-start` | `bottom-end` | `top-start` | `top-end` (default: `bottom-start`)
- `variant` — button variant used for trigger: `ghost` | `secondary` (default: `ghost`)

**dropdown.php:**
```php
<?php
$id = $props['id'] ?? 'dropdown';
$triggerLabel = $props['triggerLabel'] ?? 'Aktionen';
$itemsJson = $props['items'] ?? '[]';
$position = $props['position'] ?? 'bottom-start';
$variant = $props['variant'] ?? 'ghost';

$items = json_decode($itemsJson, true) ?: [];
$menuId = $id . '-menu';
?>
<div class="dropdown dropdown--<?= htmlspecialchars($position) ?>" data-dropdown>
    <button
        type="button"
        class="button button--<?= htmlspecialchars($variant) ?> button--md dropdown__trigger"
        aria-haspopup="menu"
        aria-expanded="false"
        aria-controls="<?= htmlspecialchars($menuId) ?>"
        data-dropdown-trigger
    >
        <span><?= htmlspecialchars($triggerLabel) ?></span>
        <span class="dropdown__chevron" aria-hidden="true">▾</span>
    </button>
    <ul id="<?= htmlspecialchars($menuId) ?>" class="dropdown__menu" role="menu" hidden>
        <?php foreach ($items as $item): ?>
            <?php if (($item['divider'] ?? 'false') === 'true'): ?>
                <li role="none"><hr class="dropdown__divider"></li>
            <?php else:
                $classes = ['dropdown__item'];
                if (($item['danger'] ?? 'false') === 'true') {
                    $classes[] = 'dropdown__item--danger';
                }
                $disabled = ($item['disabled'] ?? 'false') === 'true';
            ?>
                <li role="none">
                    <a
                        href="<?= htmlspecialchars($item['href'] ?? '#') ?>"
                        role="menuitem"
                        class="<?= implode(' ', $classes) ?>"
                        <?= $disabled ? 'aria-disabled="true" tabindex="-1"' : '' ?>
                    ><?= htmlspecialchars($item['label'] ?? '') ?></a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>
```

**dropdown.ts:**
```ts
export function initDropdowns(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-dropdown]').forEach((dropdown) => {
        if (dropdown.dataset.dropdownInit === 'true') return;
        dropdown.dataset.dropdownInit = 'true';

        const trigger = dropdown.querySelector<HTMLButtonElement>('[data-dropdown-trigger]');
        const menu = dropdown.querySelector<HTMLElement>('.dropdown__menu');
        if (!trigger || !menu) return;

        const items = () =>
            Array.from(menu.querySelectorAll<HTMLElement>('[role="menuitem"]:not([aria-disabled="true"])'));

        const open = () => {
            menu.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            const first = items()[0];
            first?.focus();
            document.addEventListener('click', onOutside);
        };

        const close = (restoreFocus = true) => {
            menu.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            document.removeEventListener('click', onOutside);
            if (restoreFocus) trigger.focus();
        };

        const onOutside = (e: MouseEvent) => {
            if (!dropdown.contains(e.target as Node)) close(false);
        };

        trigger.addEventListener('click', () => {
            trigger.getAttribute('aria-expanded') === 'true' ? close() : open();
        });

        dropdown.addEventListener('keydown', (e: Event) => {
            const ke = e as KeyboardEvent;
            const list = items();
            const idx = list.indexOf(document.activeElement as HTMLElement);
            if (ke.key === 'Escape') {
                e.preventDefault();
                close();
            } else if (ke.key === 'ArrowDown') {
                e.preventDefault();
                if (menu.hidden) open();
                else list[(idx + 1) % list.length]?.focus();
            } else if (ke.key === 'ArrowUp') {
                e.preventDefault();
                list[(idx - 1 + list.length) % list.length]?.focus();
            } else if (ke.key === 'Home') {
                e.preventDefault();
                list[0]?.focus();
            } else if (ke.key === 'End') {
                e.preventDefault();
                list[list.length - 1]?.focus();
            }
        });
    });
}

if (typeof document !== 'undefined') {
    if (document.readyState !== 'loading') initDropdowns();
    else document.addEventListener('DOMContentLoaded', () => initDropdowns());
}
```

**dropdown.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.dropdown {
  position: relative;
  display: inline-block;

  &__trigger {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
  }

  &__chevron {
    font-size: 0.75em;
    line-height: 1;
  }

  &__menu {
    position: absolute;
    min-width: 180px;
    margin: 0;
    padding: var(--space-2) 0;
    list-style: none;
    background: var(--color-bg-surface);
    border: 1px solid var(--color-border-default);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    z-index: var(--z-dropdown);
  }

  &--bottom-start &__menu { top: calc(100% + var(--space-1)); left: 0; }
  &--bottom-end &__menu   { top: calc(100% + var(--space-1)); right: 0; }
  &--top-start &__menu    { bottom: calc(100% + var(--space-1)); left: 0; }
  &--top-end &__menu      { bottom: calc(100% + var(--space-1)); right: 0; }

  &__item {
    display: block;
    padding: var(--space-2) var(--space-4);
    color: var(--color-text-primary);
    text-decoration: none;
    font-size: 14px;

    @include a11y.respect-motion {
      transition: background var(--duration-fast) var(--ease-standard);
    }

    &:hover:not([aria-disabled="true"]) {
      background: var(--color-bg-subtle);
    }

    @include a11y.focus-visible-ring;

    &[aria-disabled="true"] {
      color: var(--color-text-disabled);
      cursor: not-allowed;
    }

    &--danger {
      color: var(--color-action-danger);

      &:hover {
        background: var(--color-status-error-subtle, var(--color-bg-subtle));
      }
    }
  }

  &__divider {
    margin: var(--space-2) 0;
    border: 0;
    border-top: 1px solid var(--color-border-default);
  }
}
```

**dropdown.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'demo-dropdown',
        'triggerLabel' => 'Aktionen',
        'items' => json_encode([
            ['label' => 'Bearbeiten', 'href' => '#edit'],
            ['label' => 'Duplizieren', 'href' => '#duplicate'],
            ['divider' => 'true'],
            ['label' => 'Löschen', 'href' => '#delete', 'danger' => 'true'],
        ]),
        'position' => 'bottom-start',
        'variant' => 'ghost',
    ],
];
```

**dropdown.stories.json:**
```json
{
  "title": "Molecules/Dropdown",
  "argTypes": {
    "id": { "control": "text" },
    "triggerLabel": { "control": "text" },
    "position": { "control": { "type": "select" }, "options": ["bottom-start", "bottom-end", "top-start", "top-end"] },
    "variant": { "control": { "type": "select" }, "options": ["ghost", "secondary"] }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/dropdown" } } },
    { "name": "Bottom End", "args": { "position": "bottom-end" }, "parameters": { "server": { "id": "molecules/dropdown" } } },
    { "name": "Secondary Trigger", "args": { "variant": "secondary" }, "parameters": { "server": { "id": "molecules/dropdown" } } }
  ]
}
```

## 2. Tabs

**Files:**
- `molecules/tabs/tabs.php`
- `molecules/tabs/tabs.scss`
- `molecules/tabs/tabs.ts`
- `molecules/tabs/tabs.variants.php`
- `molecules/tabs/tabs.stories.json`

**Props:**
- `id` — base ID (required; tab/panel IDs derived)
- `tabs` — JSON array of `{label, panelContent, disabled?: "true"/"false"}`
- `activeIndex` — integer string (default `"0"`)
- `variant` — `line` | `pill` (default `line`)
- `ariaLabel` — tablist label (default `Abschnitte`)

**tabs.php:**
```php
<?php
$id = $props['id'] ?? 'tabs';
$tabsJson = $props['tabs'] ?? '[]';
$activeIndex = (int)($props['activeIndex'] ?? '0');
$variant = $props['variant'] ?? 'line';
$ariaLabel = $props['ariaLabel'] ?? 'Abschnitte';

$tabs = json_decode($tabsJson, true) ?: [];
?>
<div class="tabs tabs--<?= htmlspecialchars($variant) ?>" data-tabs>
    <div class="tabs__list" role="tablist" aria-label="<?= htmlspecialchars($ariaLabel) ?>">
        <?php foreach ($tabs as $i => $tab):
            $tabId = $id . '-tab-' . $i;
            $panelId = $id . '-panel-' . $i;
            $active = $i === $activeIndex;
            $disabled = ($tab['disabled'] ?? 'false') === 'true';
            $classes = ['tabs__tab'];
            if ($active) $classes[] = 'tabs__tab--active';
            if ($disabled) $classes[] = 'tabs__tab--disabled';
        ?>
            <button
                type="button"
                role="tab"
                id="<?= htmlspecialchars($tabId) ?>"
                aria-controls="<?= htmlspecialchars($panelId) ?>"
                aria-selected="<?= $active ? 'true' : 'false' ?>"
                tabindex="<?= $active ? '0' : '-1' ?>"
                class="<?= implode(' ', $classes) ?>"
                <?= $disabled ? 'disabled' : '' ?>
            ><?= htmlspecialchars($tab['label'] ?? '') ?></button>
        <?php endforeach; ?>
    </div>
    <?php foreach ($tabs as $i => $tab):
        $tabId = $id . '-tab-' . $i;
        $panelId = $id . '-panel-' . $i;
        $active = $i === $activeIndex;
    ?>
        <div
            role="tabpanel"
            id="<?= htmlspecialchars($panelId) ?>"
            aria-labelledby="<?= htmlspecialchars($tabId) ?>"
            class="tabs__panel"
            <?= $active ? '' : 'hidden' ?>
        ><?= $tab['panelContent'] ?? '' ?></div>
    <?php endforeach; ?>
</div>
```

**tabs.ts:**
```ts
export function initTabs(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-tabs]').forEach((wrapper) => {
        if (wrapper.dataset.tabsInit === 'true') return;
        wrapper.dataset.tabsInit = 'true';

        const tabs = Array.from(wrapper.querySelectorAll<HTMLButtonElement>('[role="tab"]'));
        const panels = Array.from(wrapper.querySelectorAll<HTMLElement>('[role="tabpanel"]'));

        const activate = (index: number, focus = true) => {
            tabs.forEach((tab, i) => {
                const selected = i === index;
                tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                tab.setAttribute('tabindex', selected ? '0' : '-1');
                tab.classList.toggle('tabs__tab--active', selected);
                panels[i].hidden = !selected;
            });
            if (focus) tabs[index].focus();
        };

        const enabled = (i: number) => !tabs[i].hasAttribute('disabled');

        const nextEnabled = (from: number, step: number): number => {
            const n = tabs.length;
            for (let k = 1; k <= n; k++) {
                const i = ((from + step * k) % n + n) % n;
                if (enabled(i)) return i;
            }
            return from;
        };

        tabs.forEach((tab, i) => {
            tab.addEventListener('click', () => enabled(i) && activate(i));
            tab.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'ArrowRight') { e.preventDefault(); activate(nextEnabled(i, 1)); }
                else if (e.key === 'ArrowLeft') { e.preventDefault(); activate(nextEnabled(i, -1)); }
                else if (e.key === 'Home') { e.preventDefault(); activate(nextEnabled(-1, 1)); }
                else if (e.key === 'End') { e.preventDefault(); activate(nextEnabled(tabs.length, -1)); }
            });
        });
    });
}

if (typeof document !== 'undefined') {
    if (document.readyState !== 'loading') initTabs();
    else document.addEventListener('DOMContentLoaded', () => initTabs());
}
```

**tabs.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.tabs {
  &__list {
    display: flex;
    gap: var(--space-2);
    border-bottom: 1px solid var(--color-border-default);
    margin-bottom: var(--space-4);
  }

  &__tab {
    appearance: none;
    background: transparent;
    border: none;
    padding: var(--space-3) var(--space-4);
    font-family: var(--text-font-family);
    font-size: 15px;
    color: var(--color-text-muted);
    cursor: pointer;
    position: relative;

    @include a11y.respect-motion {
      transition: color var(--duration-fast) var(--ease-standard);
    }

    &:hover:not(:disabled):not(.tabs__tab--active) {
      color: var(--color-text-primary);
    }

    @include a11y.focus-visible-ring;

    &--active {
      color: var(--color-primary);
      font-weight: 600;
    }

    &:disabled {
      color: var(--color-text-disabled);
      cursor: not-allowed;
    }
  }

  &--line &__tab--active::after {
    content: "";
    position: absolute;
    left: var(--space-4);
    right: var(--space-4);
    bottom: -1px;
    height: 2px;
    background: var(--color-primary);
    border-radius: var(--radius-sm);
  }

  &--pill &__list {
    border-bottom: none;
    gap: var(--space-2);
  }

  &--pill &__tab {
    border-radius: var(--radius-full);

    &--active {
      background: var(--color-primary);
      color: var(--color-text-on-primary, #fff);
    }
  }

  &__panel {
    color: var(--color-text-primary);
  }
}
```

**tabs.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'demo-tabs',
        'tabs' => json_encode([
            ['label' => 'Übersicht', 'panelContent' => '<p>Zusammenfassung der Projektidee.</p>'],
            ['label' => 'Details', 'panelContent' => '<p>Ausführliche Beschreibung und Kontext.</p>'],
            ['label' => 'Team', 'panelContent' => '<p>Beteiligte Personen und Rollen.</p>'],
        ]),
        'activeIndex' => '0',
        'variant' => 'line',
        'ariaLabel' => 'Abschnitte',
    ],
];
```

**tabs.stories.json:**
```json
{
  "title": "Molecules/Tabs",
  "argTypes": {
    "id": { "control": "text" },
    "activeIndex": { "control": "text" },
    "variant": { "control": { "type": "select" }, "options": ["line", "pill"] },
    "ariaLabel": { "control": "text" }
  },
  "stories": [
    { "name": "Line", "parameters": { "server": { "id": "molecules/tabs" } } },
    { "name": "Pill", "args": { "variant": "pill" }, "parameters": { "server": { "id": "molecules/tabs" } } },
    { "name": "Second Active", "args": { "activeIndex": "1" }, "parameters": { "server": { "id": "molecules/tabs" } } }
  ]
}
```

## 3. Breadcrumb

**Files:**
- `molecules/breadcrumb/breadcrumb.php`
- `molecules/breadcrumb/breadcrumb.scss`
- `molecules/breadcrumb/breadcrumb.variants.php`
- `molecules/breadcrumb/breadcrumb.stories.json`

**Props:**
- `items` — JSON array of `{label, href?}`; last item rendered as current (no link)
- `separator` — text (default `/`)
- `ariaLabel` — default `Brotkrumen-Navigation`

**breadcrumb.php:**
```php
<?php
$itemsJson = $props['items'] ?? '[]';
$separator = $props['separator'] ?? '/';
$ariaLabel = $props['ariaLabel'] ?? 'Brotkrumen-Navigation';

$items = json_decode($itemsJson, true) ?: [];
$last = count($items) - 1;
?>
<nav class="breadcrumb" aria-label="<?= htmlspecialchars($ariaLabel) ?>">
    <ol class="breadcrumb__list">
        <?php foreach ($items as $i => $item):
            $isLast = $i === $last;
        ?>
            <li class="breadcrumb__item<?= $isLast ? ' breadcrumb__item--current' : '' ?>"
                <?= $isLast ? 'aria-current="page"' : '' ?>>
                <?php if (!$isLast && !empty($item['href'])): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="breadcrumb__link"><?= htmlspecialchars($item['label'] ?? '') ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars($item['label'] ?? '') ?></span>
                <?php endif; ?>
                <?php if (!$isLast): ?>
                    <span class="breadcrumb__separator" aria-hidden="true"><?= htmlspecialchars($separator) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
```

**breadcrumb.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.breadcrumb {
  &__list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 14px;
  }

  &__item {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--color-text-muted);

    &--current {
      color: var(--color-text-primary);
      font-weight: 500;
    }
  }

  &__link {
    color: var(--color-text-muted);
    text-decoration: none;

    @include a11y.respect-motion {
      transition: color var(--duration-fast) var(--ease-standard);
    }

    &:hover {
      color: var(--color-primary);
      text-decoration: underline;
    }

    @include a11y.focus-visible-ring;
  }

  &__separator {
    color: var(--color-text-muted);
    pointer-events: none;
    user-select: none;
  }
}
```

**breadcrumb.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'items' => json_encode([
            ['label' => 'Start', 'href' => '/'],
            ['label' => 'Ideen', 'href' => '/ideen'],
            ['label' => 'Solarenergie im Quartier'],
        ]),
        'separator' => '/',
        'ariaLabel' => 'Brotkrumen-Navigation',
    ],
];
```

**breadcrumb.stories.json:**
```json
{
  "title": "Molecules/Breadcrumb",
  "argTypes": {
    "separator": { "control": "text" },
    "ariaLabel": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/breadcrumb" } } },
    { "name": "Chevron Separator", "args": { "separator": "›" }, "parameters": { "server": { "id": "molecules/breadcrumb" } } }
  ]
}
```

## 4. Pagination

**Files:**
- `molecules/pagination/pagination.php`
- `molecules/pagination/pagination.scss`
- `molecules/pagination/pagination.variants.php`
- `molecules/pagination/pagination.stories.json`

**Props:**
- `currentPage` — integer string (1-indexed)
- `totalPages` — integer string
- `baseUrl` — base href (e.g. `/ideen`)
- `pageParam` — query param name (default `page`)
- `siblingCount` — integer string (default `1`)
- `ariaLabel` — default `Seitennavigation`

**Page-list algorithm (PHP):**
```php
function buildPageList(int $current, int $total, int $siblings): array {
    $pages = [];
    $left = max(2, $current - $siblings);
    $right = min($total - 1, $current + $siblings);

    $pages[] = 1;
    if ($left > 2) $pages[] = '...';
    for ($i = $left; $i <= $right; $i++) $pages[] = $i;
    if ($right < $total - 1) $pages[] = '...';
    if ($total > 1) $pages[] = $total;

    return $pages;
}
```

**pagination.php:**
```php
<?php
$current = (int)($props['currentPage'] ?? '1');
$total = (int)($props['totalPages'] ?? '1');
$baseUrl = $props['baseUrl'] ?? '';
$pageParam = $props['pageParam'] ?? 'page';
$siblings = (int)($props['siblingCount'] ?? '1');
$ariaLabel = $props['ariaLabel'] ?? 'Seitennavigation';

if ($total <= 1) return;

$buildHref = function (int $page) use ($baseUrl, $pageParam): string {
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    return $baseUrl . $sep . $pageParam . '=' . $page;
};

$pages = [];
$left = max(2, $current - $siblings);
$right = min($total - 1, $current + $siblings);
$pages[] = 1;
if ($left > 2) $pages[] = '...';
for ($i = $left; $i <= $right; $i++) $pages[] = $i;
if ($right < $total - 1) $pages[] = '...';
if ($total > 1) $pages[] = $total;

$hasPrev = $current > 1;
$hasNext = $current < $total;
?>
<nav class="pagination" aria-label="<?= htmlspecialchars($ariaLabel) ?>">
    <ul class="pagination__list">
        <li>
            <?php if ($hasPrev): ?>
                <a href="<?= htmlspecialchars($buildHref($current - 1)) ?>" class="pagination__link pagination__link--prev" aria-label="Vorherige Seite" rel="prev">‹</a>
            <?php else: ?>
                <span class="pagination__link pagination__link--prev pagination__link--disabled" aria-hidden="true">‹</span>
            <?php endif; ?>
        </li>
        <?php foreach ($pages as $p): ?>
            <li>
                <?php if ($p === '...'): ?>
                    <span class="pagination__ellipsis" aria-hidden="true">…</span>
                <?php elseif ($p === $current): ?>
                    <a href="<?= htmlspecialchars($buildHref($p)) ?>" class="pagination__link pagination__link--current" aria-current="page"><?= $p ?></a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($buildHref($p)) ?>" class="pagination__link"><?= $p ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <li>
            <?php if ($hasNext): ?>
                <a href="<?= htmlspecialchars($buildHref($current + 1)) ?>" class="pagination__link pagination__link--next" aria-label="Nächste Seite" rel="next">›</a>
            <?php else: ?>
                <span class="pagination__link pagination__link--next pagination__link--disabled" aria-hidden="true">›</span>
            <?php endif; ?>
        </li>
    </ul>
</nav>
```

**pagination.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.pagination {
  &__list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-1);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  &__link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 var(--space-3);
    color: var(--color-text-primary);
    background: var(--color-bg-surface);
    border: 1px solid var(--color-border-default);
    border-radius: var(--radius-md);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;

    @include a11y.respect-motion {
      transition:
        background var(--duration-fast) var(--ease-standard),
        border-color var(--duration-fast) var(--ease-standard),
        color var(--duration-fast) var(--ease-standard);
    }

    &:hover:not(&--current):not(&--disabled) {
      background: var(--color-bg-subtle);
      border-color: var(--color-border-strong);
    }

    @include a11y.focus-visible-ring;

    &--current {
      background: var(--color-primary);
      border-color: var(--color-primary);
      color: var(--color-text-on-primary, #fff);
      pointer-events: none;
    }

    &--disabled {
      color: var(--color-text-disabled);
      background: var(--color-bg-subtle);
      cursor: not-allowed;
    }
  }

  &__ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    color: var(--color-text-muted);
  }
}
```

**pagination.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'currentPage' => '5',
        'totalPages' => '10',
        'baseUrl' => '/ideen',
        'pageParam' => 'page',
        'siblingCount' => '1',
        'ariaLabel' => 'Seitennavigation',
    ],
];
```

**pagination.stories.json:**
```json
{
  "title": "Molecules/Pagination",
  "argTypes": {
    "currentPage": { "control": "text" },
    "totalPages": { "control": "text" },
    "baseUrl": { "control": "text" },
    "pageParam": { "control": "text" },
    "siblingCount": { "control": "text" },
    "ariaLabel": { "control": "text" }
  },
  "stories": [
    { "name": "Middle", "parameters": { "server": { "id": "molecules/pagination" } } },
    { "name": "First Page", "args": { "currentPage": "1" }, "parameters": { "server": { "id": "molecules/pagination" } } },
    { "name": "Last Page", "args": { "currentPage": "10" }, "parameters": { "server": { "id": "molecules/pagination" } } },
    { "name": "Few Pages", "args": { "currentPage": "2", "totalPages": "3" }, "parameters": { "server": { "id": "molecules/pagination" } } },
    { "name": "Many Pages", "args": { "currentPage": "50", "totalPages": "100" }, "parameters": { "server": { "id": "molecules/pagination" } } }
  ]
}
```

## Wiring

**`resources/css/main.scss`** — add 4 `@use` lines in molecules section (alphabetical):
```scss
@use "../../public/packages/inno_frontend/components/molecules/breadcrumb/breadcrumb";
@use "../../public/packages/inno_frontend/components/molecules/dropdown/dropdown";
@use "../../public/packages/inno_frontend/components/molecules/pagination/pagination";
@use "../../public/packages/inno_frontend/components/molecules/tabs/tabs";
```

**`_storybook/components.scss`** — same 4 lines in Molecules section.

**`_storybook/components.ts`** — add 2 imports (breadcrumb + pagination are CSS-only):
```ts
import '../molecules/dropdown/dropdown';
import '../molecules/tabs/tabs';
```

## What Doesn't Change

- Atoms (dropdown trigger composes the button atom)
- Tokens, a11y infrastructure
- Other molecules, organisms, templates
- Storybook config

## Verification

1. `npm run development` — compiles without errors
2. All 4 molecules return 200:
   - `molecules/dropdown`
   - `molecules/tabs`
   - `molecules/breadcrumb`
   - `molecules/pagination`
3. Dropdown: click toggles, Esc closes + restores focus to trigger, ArrowDown/Up navigate items, Home/End jump
4. Tabs: ArrowLeft/Right cycle focus+activation, Home/End jump, panels swap visibility, `aria-selected` + `tabindex` update
5. Breadcrumb: last item has `aria-current="page"` and no link, separators `aria-hidden`
6. Pagination: prev disabled on page 1, next disabled on last page, ellipses render when gaps exist, current has `aria-current="page"`
7. Axe panel: 0 violations on all 4
