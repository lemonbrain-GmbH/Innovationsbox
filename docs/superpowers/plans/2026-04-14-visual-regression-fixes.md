# Visual Regression Fixes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix all visual regressions so the local CMS frontend matches the reference site exactly.

**Architecture:** Update the navigation-drawer component PHP to match reference HTML structure (nested lists, logo wrapper, semantic nav element). Fix autonav view.php to filter home page and stop double-encoding. Add lb-container wrapper in header.php. Fix z-index and add missing nested-link SCSS rules.

**Tech Stack:** PHP (Concrete CMS templates), SCSS, TypeScript (unchanged)

---

### Task 1: Fix header.php — add lb-container wrapper

**Files:**
- Modify: `public/packages/inno_frontend/themes/innovationsbox/elements/header.php`

- [ ] **Step 1: Add lb-container wrapper around nav area**

Replace the entire contents of `public/packages/inno_frontend/themes/innovationsbox/elements/header.php` with:

```php
<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?= $this->inc('elements/header_top.php'); ?>

<header class="page-header">
    <a href="#main-content-area" class="skip-link visually-hidden"><?= t('Zum Hauptinhalt springen') ?></a>

    <div class="lb-container">
        <?php
        $a = new GlobalArea('Header Nav');
        $a->display($c);
        ?>
    </div>

    <?php
    $a = new GlobalArea('Unsupported Browser');
    $a->display($c);
    ?>
</header>
```

The key change: wrapping the `Header Nav` global area in `<div class="lb-container">`. This matches the reference site's structure and provides the positioning context that prevents the hero image from overlapping the nav toggle.

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/themes/innovationsbox/elements/header.php
git commit -m "fix: add lb-container wrapper in header for nav positioning"
```

---

### Task 2: Rewrite navigation-drawer.php to match reference structure

**Files:**
- Modify: `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.php`

- [ ] **Step 1: Rewrite the component to match reference HTML**

Replace the entire contents of `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.php` with:

```php
<?php
$logoSrc = $props['logoSrc'] ?? '';
$logoAlt = $props['logoAlt'] ?? '';
$logoHref = $props['logoHref'] ?? '/';
$itemsJson = $props['items'] ?? '[]';
$items = json_decode($itemsJson, true) ?: [];
?>
<div class="nav-container">
    <?php if ($logoSrc): ?>
        <div class="main-logo">
            <a href="<?= htmlspecialchars($logoHref) ?>" aria-label="<?= htmlspecialchars($logoAlt) ?>">
                <img src="<?= htmlspecialchars($logoSrc) ?>" alt="<?= htmlspecialchars($logoAlt) ?>">
            </a>
        </div>
    <?php endif; ?>

    <button class="nav-toggle" aria-controls="main-nav" aria-expanded="false">
        <span class="sr-only"><?= t('Menü öffnen') ?></span>
        ☰
    </button>

    <nav class="nav-drawer" id="main-nav" aria-hidden="true">
        <button class="nav-close">
            <span class="sr-only"><?= t('Menü schliessen') ?></span>
            ✕
        </button>

        <ul>
            <?php
            $prevLevel = 1;
            foreach ($items as $i => $item):
                $level = (int) ($item['level'] ?? 1);
                $text = $item['text'] ?? '';
                $href = $item['href'] ?? '#';
                $target = $item['target'] ?? '_self';
                $active = ($item['active'] ?? 'false') === 'true';
                $hasSubmenu = ($item['hasSubmenu'] ?? false);
                $subDepth = (int) ($item['subDepth'] ?? 0);
                $class = $active ? ' class="nav-selected"' : '';

                // Open nested <ul> if going deeper
                if ($level > $prevLevel) {
                    echo '<ul>';
                }
                // Close nested levels if going shallower
                if ($level < $prevLevel) {
                    echo str_repeat('</ul></li>', $prevLevel - $level);
                }
            ?>
                <li<?= $class ?>><a href="<?= htmlspecialchars($href) ?>" target="<?= htmlspecialchars($target) ?>"<?= $class ?>><?= $text ?></a><?php
                if ($hasSubmenu) {
                    // Don't close <li> — nested <ul> will follow
                } else {
                    echo '</li>';
                    if ($subDepth > 0) {
                        echo str_repeat('</ul></li>', $subDepth);
                    }
                }
                $prevLevel = $level;
            endforeach;
            // Close any remaining open levels
            if ($prevLevel > 1) {
                echo str_repeat('</ul></li>', $prevLevel - 1);
            }
            ?>
        </ul>
    </nav>
    <div class="nav-overlay"></div>
</div>
```

Key changes from the old version:
- Outer element is `<div class="nav-container">` (not `<nav>`)
- Logo wrapped in `<div class="main-logo">`
- Button uses `<span class="sr-only">` instead of `aria-label`
- Nav drawer is `<nav class="nav-drawer">` (semantic)
- Items rendered with hierarchical nesting using `level`, `hasSubmenu`, `subDepth`
- Text output without `htmlspecialchars()` — CMS already escapes it
- `target` attribute added to links
- Overlay moved inside nav-container (after drawer)

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.php
git commit -m "fix: rewrite navigation-drawer to match reference HTML structure"
```

---

### Task 3: Update autonav view.php — fix data mapping

**Files:**
- Modify: `public/packages/inno_frontend/blocks/autonav/templates/innovationsbox/view.php`

- [ ] **Step 1: Update the view.php to pass nesting data and filter home page**

Replace the entire contents of `public/packages/inno_frontend/blocks/autonav/templates/innovationsbox/view.php` with:

```php
<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Block\Autonav\Controller $controller */

$navItems = $controller->getNavItems();
$c = \Concrete\Core\Page\Page::getCurrentPage();

if (count($navItems) === 0) {
    if (is_object($c) && $c->isEditMode()) { ?>
        <div class="ccm-edit-mode-disabled-item"><?=t('Empty Auto-Nav Block.')?></div>
    <?php }
    return;
}

// Map CMS nav items to component props (skip home page)
$items = [];
foreach ($navItems as $ni) {
    if ($ni->isHome) {
        continue;
    }

    $items[] = [
        'text' => $ni->name,
        'href' => $ni->url,
        'target' => $ni->target,
        'active' => $ni->isCurrent || $ni->inPath ? 'true' : 'false',
        'level' => (string) $ni->level,
        'hasSubmenu' => $ni->hasSubmenu,
        'subDepth' => (int) $ni->subDepth,
    ];
}

// Logo from site config (optional)
$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
$site = $app->make('site')->getSite();
$logoFile = $site->getAttribute('logo');
$logoSrc = is_object($logoFile) ? $logoFile->getRelativePath() : '';

$props = [
    'logoSrc' => $logoSrc,
    'logoAlt' => t('Innovationsbox Logo'),
    'logoHref' => '/',
    'items' => json_encode($items),
];

include DIR_COMPONENTS . '/organisms/navigation-drawer/navigation-drawer.php';
```

Key changes:
- Filter out home page with `if ($ni->isHome) continue`
- Pass `target`, `hasSubmenu`, `subDepth` for hierarchical rendering
- Don't apply `htmlspecialchars` to `$ni->name` (CMS already escapes)
- Logo alt text uses translation function

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/blocks/autonav/templates/innovationsbox/view.php
git commit -m "fix: filter home page from nav, pass nesting data to component"
```

---

### Task 4: Update navigation-drawer SCSS — z-index and nested link colors

**Files:**
- Modify: `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss`

- [ ] **Step 1: Add z-index to nav-container and nested link color rules**

Replace the entire contents of `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss` with:

```scss
@use "~@helpers/functions" as f;

.main-logo {
  width: clamp(0px, 50vw, 320px);
  margin: f.fluidVal(8, 16) 0;
}

.nav-container {
  display: flex;
  justify-content: center;
  position: relative;
  z-index: 10;

  .nav-drawer {
    position: fixed;
    top: 0;
    right: 0;
    width: 400px;
    max-width: 75vw;
    height: 100%;
    background: var(--color-white);
    box-shadow: -2px 0 10px rgb(0 0 0 / 10%);
    transform: translateX(100%);
    transition: transform 0.3s ease;
    padding: 2rem;
    z-index: 1000;

    &.active {
      transform: translateX(0);
    }
  }

  .nav-overlay {
    position: fixed;
    inset: 0;
    background: rgb(0 0 0 / 40%);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
    z-index: 999;

    &.active {
      opacity: 1;
      pointer-events: auto;
    }
  }

  .nav-toggle {
    position: absolute;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
  }

  .nav-close, .nav-toggle {
    margin-bottom: 1rem;
    aspect-ratio: 1;
    border-radius: 50%;
  }

  .nav-drawer ul {
    list-style: none;
    padding: 0;

    li {
      margin: 1rem 0;
    }

    a {
      text-decoration: none;
      font-size: f.fluidVal(18, 24);

      &.nav-selected {
        text-decoration: underline;
        text-decoration-color: var(--color-green);
      }
    }

    // Nested sub-menu link colors (matching reference)
    ul li a {
      color: var(--color-green);

      &.nav-selected {
        text-decoration-color: var(--color-primary);
      }

      &:hover {
        color: var(--color-brown);
      }
    }
  }
}
```

Key changes:
- Added `position: relative; z-index: 10;` to `.nav-container` (fixes V6 — hero overlap)
- Added `ul ul li a` rules for nested sub-link colors (fixes V7):
  - Sub-links green by default
  - Selected sub-links underlined with primary color
  - Hover turns brown

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss
git commit -m "fix: add z-index to nav-container, add nested link color rules"
```

---

### Task 5: Update Storybook variants and stories for new props

**Files:**
- Modify: `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.variants.php`
- Modify: `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.stories.json`

- [ ] **Step 1: Update variants with hierarchical nav data**

Replace the entire contents of `navigation-drawer.variants.php` with:

```php
<?php

declare(strict_types=1);
return [
    'defaults' => [
        'logoSrc' => 'https://placehold.co/320x80/B7CD57/353535?text=Innovationsbox',
        'logoAlt' => 'Innovationsbox Logo',
        'logoHref' => '/',
        'items' => json_encode([
            ['text' => 'Projektidee finden', 'href' => '/projektidee-finden', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
            ['text' => 'Projektidee einbringen', 'href' => '/projektidee-einbringen', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
            ['text' => 'Übersicht Projektideen', 'href' => '/uebersicht-projektideen', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => true, 'subDepth' => 0],
            ['text' => 'Bauen', 'href' => '/uebersicht-projektideen/bauen', 'target' => '_self', 'active' => 'false', 'level' => '2', 'hasSubmenu' => false, 'subDepth' => 0],
            ['text' => 'Wohnen', 'href' => '/uebersicht-projektideen/wohnen', 'target' => '_self', 'active' => 'false', 'level' => '2', 'hasSubmenu' => false, 'subDepth' => 0],
            ['text' => 'Landwirtschaft', 'href' => '/uebersicht-projektideen/landwirtschaft', 'target' => '_self', 'active' => 'false', 'level' => '2', 'hasSubmenu' => false, 'subDepth' => 0],
            ['text' => 'Tourismus', 'href' => '/uebersicht-projektideen/tourismus', 'target' => '_self', 'active' => 'false', 'level' => '2', 'hasSubmenu' => false, 'subDepth' => 0],
            ['text' => 'Mobilität', 'href' => '/uebersicht-projektideen/mobilitaet', 'target' => '_self', 'active' => 'false', 'level' => '2', 'hasSubmenu' => false, 'subDepth' => 1],
            ['text' => 'Expertinnen-Pool', 'href' => '/expertinnen-pool', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
            ['text' => 'News', 'href' => '/news', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
            ['text' => 'Infos / FAQ', 'href' => '/infos-faq', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
            ['text' => 'Kontakt', 'href' => '/kontakt', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
        ]),
    ],
];
```

- [ ] **Step 2: Update stories with matching data**

Replace the entire contents of `navigation-drawer.stories.json` with:

```json
{
  "title": "Organisms/Navigation Drawer",
  "stories": [
    {
      "name": "Default",
      "parameters": {
        "server": {
          "id": "organisms/navigation-drawer"
        }
      }
    }
  ]
}
```

This story now uses the defaults from `variants.php` (no inline params needed since the defaults provide realistic data).

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.variants.php public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.stories.json
git commit -m "fix: update navigation-drawer stories with realistic hierarchical nav data"
```

---

### Task 6: Build and verify visually

- [ ] **Step 1: Run webpack build**

```bash
npm run development
```

Expected: compiles successfully.

- [ ] **Step 2: Verify in browser — homepage**

Navigate to `http://innovationsbox.local:8080/index.php` and check:
- Logo visible in header (if site attribute is configured)
- Nav toggle button is clickable (not covered by hero)
- Click nav toggle → drawer opens with properly nested items
- "Übersicht Projektideen" has sub-items indented (Bauen, Wohnen, etc.)
- No HTML entities visible (no `&Uuml;` or `&auml;`)
- No "Home" link in nav
- Sub-links have green color

- [ ] **Step 3: Verify Storybook**

```bash
php -S localhost:6007 storybook-server/index.php &
curl -s "http://localhost:6007/organisms/navigation-drawer"
```

Expected: renders navigation drawer with nested items matching reference structure.

- [ ] **Step 4: Commit build artifacts**

```bash
git add public/packages/inno_frontend/blocks/ public/packages/inno_frontend/themes/innovationsbox/js/
git commit -m "build: regenerate assets after visual regression fixes"
```
