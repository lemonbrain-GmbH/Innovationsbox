# Design System Rebuild Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the design system based on the Figma redesign — dark header, Open Sans font, compact project cards, new footer, FAB, rename Projekträume→Innovationen, and adapt all pages for visual consistency.

**Architecture:** Token-first approach — update `tokens.json` and regenerate CSS variables, then cascade changes through atoms → molecules → organisms → pages. Components that need structural changes (header, project-card, FAB) get targeted rework.

**Tech Stack:** PHP (Concrete CMS partials), SCSS (via Laravel Mix/webpack), TypeScript, design tokens (JSON → SCSS generation via `scripts/build-tokens.js`)

## Global Constraints

- Figma Frame 2 (node `7:10`) is the source of truth for visual design
- Font: Open Sans (variable weight, replaces Inter)
- All existing accessibility features (focus rings, touch targets, reduced motion) must be preserved
- Component library follows Atomic Design (atoms → molecules → organisms)
- Each component has: `.php` (partial), `.scss` (styles), `.variants.php` (Storybook props)
- CSS variables generated from `tokens.json` via `npm run tokens`
- Webpack auto-discovers single page files from `resources/css/single_pages/` and `resources/js/single_pages/`
- Run `npm run tokens` after token changes, `npm run development` to rebuild assets
- Run `composer lint:php` and `npm run lint:all` before committing

---

### Task 1: Update Design Tokens and Font

**Files:**
- Modify: `resources/tokens/tokens.json` (font family entries at lines 148-151, add semantic tokens)
- Modify: `scripts/build-tokens.js` (add header/footer/card semantic token generation)
- Modify: `public/packages/inno_frontend/themes/innovationsbox/elements/header_top.php:66-79` (@font-face declarations)
- Modify: `.storybook/preview-head.html:5-18` (@font-face declarations)
- Create: `public/packages/inno_frontend/themes/innovationsbox/fonts/OpenSans-VariableFont_wdth,wght.ttf`
- Create: `public/packages/inno_frontend/themes/innovationsbox/fonts/OpenSans-Italic-VariableFont_wdth,wght.ttf`
- Generated: `resources/css/global/_generated-variables.scss` (auto-regenerated)
- Generated: `resources/css/global/_generated-arrays.scss` (auto-regenerated)

**Interfaces:**
- Consumes: nothing (first task)
- Produces: CSS custom properties `--heading-font-family`, `--text-font-family` (now Open Sans), `--color-header-bg`, `--color-footer-bg`, `--color-card-border`, `--header-shadow-height`, `--fab-size`, `--fab-bg`

- [ ] **Step 1: Download Open Sans variable font files**

```bash
# Download Open Sans variable fonts from Google Fonts
cd public/packages/inno_frontend/themes/innovationsbox/fonts/
curl -L -o OpenSans-VariableFont_wdth,wght.ttf "https://github.com/google/fonts/raw/main/ofl/opensans/OpenSans%5Bwdth%2Cwght%5D.ttf"
curl -L -o OpenSans-Italic-VariableFont_wdth,wght.ttf "https://github.com/google/fonts/raw/main/ofl/opensans/OpenSans-Italic%5Bwdth%2Cwght%5D.ttf"
```

Verify the files exist and are non-empty (should be ~500KB+ each).

- [ ] **Step 2: Update tokens.json — font family**

In `resources/tokens/tokens.json`, change the `font.family` entries:

```json
"font": {
    "family": {
      "heading": "'Open Sans', sans-serif",
      "text": "'Open Sans', sans-serif"
    },
```

- [ ] **Step 3: Update tokens.json — add new semantic tokens**

In `resources/tokens/tokens.json`, add to the `semantic` object:

Add a new `"header"` group after the `"action"` group:

```json
    "header": {
      "bg": "rgba(0, 0, 0, 0.8)",
      "shadow-height": "34px"
    },
    "footer": {
      "bg": "rgba(121, 105, 77, 0.7)"
    },
    "card": {
      "border": "#e4e4e4"
    },
    "fab": {
      "size": "87px",
      "bg": "var(--color-brand-primary-500)"
    }
```

- [ ] **Step 4: Update build-tokens.js to emit new semantic tokens**

In `scripts/build-tokens.js`, after the semantic aliases loop (around line 108), add generation for the new groups:

```javascript
  // header, footer, card, fab tokens
  for (const group of ['header', 'footer', 'card', 'fab']) {
    if (tokens.semantic[group]) {
      for (const [name, value] of Object.entries(tokens.semantic[group])) {
        lines.push(`  --${group}-${name}: ${value};`);
      }
    }
  }
  lines.push('');
```

- [ ] **Step 5: Update @font-face in header_top.php**

In `public/packages/inno_frontend/themes/innovationsbox/elements/header_top.php`, replace the `<style>` block (lines 66-79):

```php
    <style>
        @font-face {
            font-family: 'Open Sans';
            font-stretch: 75% 100%;
            src: url('<?= $themePath ?>/fonts/OpenSans-VariableFont_wdth,wght.ttf') format('truetype');
            font-weight: 300 800;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Open Sans';
            font-stretch: 75% 100%;
            src: url('<?= $themePath ?>/fonts/OpenSans-Italic-VariableFont_wdth,wght.ttf') format('truetype');
            font-weight: 300 800;
            font-style: italic;
            font-display: swap;
        }
    </style>
```

- [ ] **Step 6: Update @font-face in Storybook preview-head.html**

In `.storybook/preview-head.html`, replace the `<style>` block (lines 4-19):

```html
<style>
  @font-face {
    font-family: 'Open Sans';
    font-stretch: 75% 100%;
    src: url('/packages/inno_frontend/themes/innovationsbox/fonts/OpenSans-VariableFont_wdth,wght.ttf') format('truetype');
    font-weight: 300 800;
    font-style: normal;
    font-display: swap;
  }
  @font-face {
    font-family: 'Open Sans';
    font-stretch: 75% 100%;
    src: url('/packages/inno_frontend/themes/innovationsbox/fonts/OpenSans-Italic-VariableFont_wdth,wght.ttf') format('truetype');
    font-weight: 300 800;
    font-style: italic;
    font-display: swap;
  }
</style>
```

- [ ] **Step 7: Regenerate CSS variables and build**

```bash
npm run tokens
npm run development
```

Verify `resources/css/global/_generated-variables.scss` contains:
- `--heading-font-family: 'Open Sans', sans-serif;`
- `--text-font-family: 'Open Sans', sans-serif;`
- `--header-bg: rgba(0, 0, 0, 0.8);`
- `--footer-bg: rgba(121, 105, 77, 0.7);`
- `--card-border: #e4e4e4;`
- `--fab-size: 87px;`
- `--fab-bg: var(--color-brand-primary-500);`

- [ ] **Step 8: Verify build succeeds and lint passes**

```bash
npm run development
npm run lint:all
composer lint:php
```

- [ ] **Step 9: Commit**

```bash
git add resources/tokens/tokens.json scripts/build-tokens.js \
  resources/css/global/_generated-variables.scss resources/css/global/_generated-arrays.scss \
  public/packages/inno_frontend/themes/innovationsbox/elements/header_top.php \
  public/packages/inno_frontend/themes/innovationsbox/fonts/OpenSans-VariableFont_wdth,wght.ttf \
  public/packages/inno_frontend/themes/innovationsbox/fonts/OpenSans-Italic-VariableFont_wdth,wght.ttf \
  .storybook/preview-head.html
git commit -m "feat: switch font from Inter to Open Sans and add new design tokens"
```

---

### Task 2: Redesign Header

**Files:**
- Modify: `public/packages/inno_frontend/components/organisms/header/header.scss`
- Modify: `public/packages/inno_frontend/themes/innovationsbox/elements/header.php:88-129`

**Interfaces:**
- Consumes: CSS variables `--header-bg`, `--header-shadow-height` from Task 1
- Produces: Dark header with gradient shadow, white text/icons — affects all pages globally

- [ ] **Step 1: Update header.scss**

Replace the full content of `public/packages/inno_frontend/components/organisms/header/header.scss`:

```scss
@use "~@helpers/functions" as f;
@use "~@helpers/a11y" as a11y;

header.page-header {
  box-sizing: border-box;
  background-color: var(--header-bg, rgba(0, 0, 0, 0.8));
  position: relative;

  // Depth gradient — creates 3D layering effect below header
  &::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 100%;
    height: var(--header-shadow-height, 34px);
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0.15), transparent);
    pointer-events: none;
    z-index: var(--z-sticky, 1100);
  }

  // All text and links inside header become white
  color: var(--color-white);

  a {
    color: var(--color-white);
  }

  // Skip-link needs adjusted focus for dark bg
  .skip-link:focus {
    background: var(--color-white);
    color: var(--header-bg, rgba(0, 0, 0, 0.8));
  }
}

// Hidden by default — JS moves these into the nav drawer top bar
.page-header__auth {
  display: none;
}

// When placed inside the nav drawer top bar
.nav-drawer__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-4);
}

.nav-drawer__auth {
  display: flex;
  align-items: center;
  gap: var(--space-2);

  .ccm-page & a.button--primary {
    color: var(--color-white);
    text-decoration: none;

    &:hover {
      color: var(--color-white);
    }
  }

  .ccm-page & a.button--ghost {
    color: var(--color-neutral-500);
    text-decoration: none;

    &:hover {
      color: var(--color-neutral-700);
    }
  }
}
```

- [ ] **Step 2: Verify the navigation-drawer hamburger icon is visible**

The navigation-drawer toggle icon needs to be white against the dark header. Check `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss` — if the toggle icon color is set to a dark color, add an override in header.scss:

```scss
header.page-header {
  // ... existing rules ...

  .nav-drawer__toggle svg,
  .nav-drawer__toggle {
    color: var(--color-white);
  }
}
```

Only add this if the icon isn't already inheriting the white color from the parent.

- [ ] **Step 3: Build and visually verify**

```bash
npm run development
```

Open the app in a browser. Verify:
- Header has dark background
- Logo text and hamburger icon are white
- Gradient shadow appears below header
- Navigation drawer still opens and functions
- User menu (if logged in) is visible against dark bg
- Notification badges are visible

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/components/organisms/header/header.scss
# Add navigation-drawer.scss if modified
git commit -m "feat: redesign header with dark background and gradient shadow"
```

---

### Task 3: Update Footer

**Files:**
- Modify: `public/packages/inno_frontend/components/organisms/footer/footer.scss`

**Interfaces:**
- Consumes: CSS variable `--footer-bg` from Task 1
- Produces: Updated footer with semi-transparent brown background

- [ ] **Step 1: Update footer.scss**

In `public/packages/inno_frontend/components/organisms/footer/footer.scss`, change the background-color:

```scss
footer {
  background-color: var(--footer-bg, rgba(121, 105, 77, 0.7));
```

- [ ] **Step 2: Build and visually verify**

```bash
npm run development
```

Open the app. Verify:
- Footer has the warmer, semi-transparent brown
- White text is still readable
- Partner logos are visible
- Links are underlined and white

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/components/organisms/footer/footer.scss
git commit -m "feat: update footer background to semi-transparent brown"
```

---

### Task 4: Compact Project Card

**Files:**
- Modify: `public/packages/inno_frontend/components/molecules/project-card/project-card.php`
- Modify: `public/packages/inno_frontend/components/molecules/project-card/project-card.scss`
- Modify: `public/packages/inno_frontend/components/molecules/project-card/project-card.variants.php`
- Modify: `resources/js/single_pages/projektraeume.ts:110-153` (renderCard function)

**Interfaces:**
- Consumes: CSS variable `--card-border` from Task 1
- Produces: Compact card without description, image at top, Figma-matching layout

- [ ] **Step 1: Update project-card.php — add showDescription prop and image**

Replace the full content of `public/packages/inno_frontend/components/molecules/project-card/project-card.php`:

```php
<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

$id = (string) ($props['id'] ?? '');
$title = (string) ($props['title'] ?? '');
$description = (string) ($props['description'] ?? '');
$status = (string) ($props['status'] ?? 'active');
$initiatorName = (string) ($props['initiatorName'] ?? '');
$initiatorInitials = (string) ($props['initiatorInitials'] ?? '');
$memberCount = (int) ($props['memberCount'] ?? 0);
$latestUpdateTitle = (string) ($props['latestUpdateTitle'] ?? '');
$latestUpdateDate = (string) ($props['latestUpdateDate'] ?? '');
$createdAt = (string) ($props['createdAt'] ?? '');
$categoryName = (string) ($props['categoryName'] ?? '');
$initiatorAvatarUrl = (string) ($props['initiatorAvatarUrl'] ?? '');
$imageUrl = (string) ($props['imageUrl'] ?? '');
$showDescription = filter_var($props['showDescription'] ?? false, FILTER_VALIDATE_BOOLEAN);
$isActive = $status === 'active';

$statusLabel = match ($status) {
    'active' => 'Aktiv',
    'completed' => 'Abgeschlossen',
    default => ucfirst($status),
};
$statusVariant = $isActive ? 'success' : 'neutral';
?>
<article class="project-card<?= $isActive ? '' : ' project-card--completed' ?>" aria-labelledby="project-card-title-<?= htmlspecialchars($id) ?>">
    <?php if ($imageUrl !== ''): ?>
        <div class="project-card__image">
            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="" loading="lazy">
        </div>
    <?php endif; ?>

    <div class="project-card__body">
        <div class="project-card__header">
            <?php if ($categoryName !== ''): ?>
                <span class="project-card__badge project-card__badge--category"><?= htmlspecialchars($categoryName) ?></span>
            <?php endif; ?>
            <?php
            $props = ['label' => $statusLabel, 'variant' => $statusVariant, 'size' => 'sm'];
            include DIR_COMPONENTS . '/atoms/badge/badge.php';
            ?>
        </div>

        <h3 class="project-card__title" id="project-card-title-<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($title) ?></h3>

        <?php if ($showDescription && $description !== ''): ?>
            <p class="project-card__description"><?= htmlspecialchars($description) ?></p>
        <?php endif; ?>

        <div class="project-card__meta">
            <div class="project-card__meta-item">
                <svg class="project-card__meta-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                <span><strong><?= $memberCount ?></strong> Mitglieder</span>
            </div>
            <?php if ($createdAt !== ''): ?>
                <span class="project-card__age">Erstellt: <?= htmlspecialchars($createdAt) ?></span>
            <?php endif; ?>
        </div>

        <div class="project-card__footer">
            <div class="project-card__initiator">
                <?php
                $props = ['src' => $initiatorAvatarUrl, 'name' => $initiatorName, 'size' => 'sm', 'shape' => 'circle'];
                include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
                ?>
                <div class="project-card__initiator-info">
                    <span class="project-card__initiator-name"><?= htmlspecialchars($initiatorName) ?></span>
                    <span class="project-card__initiator-role">Initiator·in</span>
                </div>
            </div>
            <?php if ($isActive): ?>
                <?php
                $props = ['label' => 'Mitmachen', 'variant' => 'primary', 'size' => 'sm'];
                include DIR_COMPONENTS . '/atoms/button/button.php';
                ?>
            <?php else: ?>
                <span class="project-card__status-label"><?= htmlspecialchars($statusLabel) ?></span>
            <?php endif; ?>
        </div>
    </div>
</article>
```

- [ ] **Step 2: Update project-card.scss**

Replace the full content of `public/packages/inno_frontend/components/molecules/project-card/project-card.scss`:

```scss
@use "~@helpers/functions" as f;

.project-card {
  display: flex;
  flex-direction: column;
  border-radius: var(--radius-lg, 20px);
  background: var(--color-bg-surface, #fff);
  border: 2px solid var(--card-border, #e4e4e4);
  overflow: hidden;
  transition: transform 200ms ease, box-shadow 200ms ease;

  @media (prefers-reduced-motion: reduce) {
    transition: none;
  }

  &:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md, 0 4px 12px rgb(0 0 0 / 12%));
  }

  // --- Image ---
  &__image {
    width: 100%;
    aspect-ratio: 16 / 9;
    overflow: hidden;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }

  // --- Body ---
  &__body {
    display: flex;
    flex-direction: column;
    gap: var(--space-3, 0.75rem);
    padding: var(--space-4, 1rem) var(--space-5, 1.25rem);
  }

  // --- Header ---
  &__header {
    display: flex;
    gap: var(--space-2, 0.5rem);
    align-items: center;
    flex-wrap: wrap;
  }

  &__badge {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;

    &--success {
      color: var(--color-brand-primary-700, #7d8f35);
      background: var(--color-brand-primary-50, #f7faeb);
    }

    &--neutral,
    &--warning {
      color: var(--color-text-tertiary, #888);
      background: var(--color-bg-muted, #f0f0f0);
    }

    &--category {
      color: var(--color-text-secondary, #555);
      background: var(--color-bg-muted, #f0f0f0);
      font-size: 0.625rem;
    }
  }

  // --- Title ---
  &__title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: var(--font-weight-semibold, 600);
    line-height: 1.3;
    color: var(--color-text-primary, #1a1a1a);
  }

  &--completed &__title,
  &--archived &__title {
    color: var(--color-text-secondary, #555);
  }

  // --- Description (hidden by default, available for future use) ---
  &__description {
    margin: 0;
    font-size: 0.875rem;
    color: var(--color-text-secondary, #666);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  // --- Meta ---
  &__meta {
    display: flex;
    align-items: center;
    gap: var(--space-4, 1rem);
    padding-top: var(--space-3, 0.75rem);
    border-top: 1px solid var(--card-border, #e4e4e4);
  }

  &__meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: var(--color-text-secondary, #666);
  }

  &__meta-icon {
    flex-shrink: 0;
    color: var(--color-text-tertiary, #999);
  }

  &__meta-item strong {
    font-weight: var(--font-weight-semibold, 600);
    color: var(--color-text-primary, #333);
  }

  &__age {
    font-size: 0.75rem;
    color: var(--color-text-tertiary, #999);
    margin-left: auto;
  }

  // --- Footer ---
  &__footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: var(--space-3, 0.75rem);
    border-top: 1px solid var(--card-border, #e4e4e4);
  }

  // --- Initiator ---
  &__initiator {
    display: flex;
    align-items: center;
    gap: var(--space-3, 0.75rem);
  }

  &__avatar {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: var(--color-brand-primary-100, #ecf3d1);
    color: var(--color-brand-primary-800, #5e6b25);
    border: 2px solid var(--color-brand-primary-300, #ccde80);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 700;
    flex-shrink: 0;

    &--muted {
      background: var(--color-bg-muted, #f0f0f0);
      color: var(--color-text-tertiary, #888);
      border-color: var(--card-border, #e4e4e4);
    }
  }

  &__avatar-img {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid var(--color-brand-primary-300, #ccde80);
  }

  &__initiator-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  &__initiator-name {
    font-size: 0.8125rem;
    font-weight: var(--font-weight-medium, 500);
    color: var(--color-text-primary, #333);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  &__initiator-role {
    font-size: 0.6875rem;
    color: var(--color-text-tertiary, #999);
  }

  // --- Buttons ---
  &__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1.25rem;
    border-radius: 12px;
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
    transition: background-color 150ms ease, box-shadow 150ms ease;
    white-space: nowrap;

    @media (prefers-reduced-motion: reduce) {
      transition: none;
    }

    &--primary {
      background: var(--color-brand-primary-500, #b7cd57);
      color: var(--color-brand-primary-900, #3f4718);
      box-shadow: 0 1px 2px rgb(0 0 0 / 10%);

      &:hover {
        background: var(--color-brand-primary-600, #9db247);
        box-shadow: 0 2px 4px rgb(0 0 0 / 15%);
        color: var(--color-brand-primary-900, #3f4718);
        text-decoration: none;
      }
    }

    &--secondary {
      background: var(--color-bg-muted, #f0f0f0);
      color: var(--color-text-secondary, #555);
      box-shadow: 0 1px 2px rgb(0 0 0 / 5%);

      &:hover {
        background: var(--color-border-default, #d8d8d8);
        box-shadow: 0 2px 4px rgb(0 0 0 / 10%);
        color: var(--color-text-primary, #333);
        text-decoration: none;
      }
    }
  }

  // --- Completed/archived state ---
  &--completed,
  &--archived {
    .project-card__meta-item {
      color: var(--color-text-tertiary, #888);
    }

    .project-card__description {
      color: var(--color-text-tertiary, #888);
    }
  }

  &__status-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-text-tertiary, #888);
  }
}
```

- [ ] **Step 3: Update project-card.variants.php**

Add `showDescription` and `imageUrl` to the variants file defaults:

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => '1',
        'title' => 'Fernwärmenetz fürs Glarner Hinterland',
        'description' => 'Planung eines Fernwärmenetzes für die Gemeinden Glarus Süd mit erneuerbaren Energiequellen.',
        'status' => 'active',
        'initiatorName' => 'Blitzgschiid',
        'initiatorInitials' => 'BG',
        'initiatorAvatarUrl' => '',
        'memberCount' => 2,
        'latestUpdateTitle' => '',
        'latestUpdateDate' => '',
        'createdAt' => '15.3.26',
        'categoryName' => 'Wohnen',
        'imageUrl' => '',
        'showDescription' => false,
    ],
];
```

- [ ] **Step 4: Update renderCard() in projektraeume.ts**

In `resources/js/single_pages/projektraeume.ts`, replace the `renderCard` function (lines 110-154):

```typescript
function renderCard(space: ProjectSpaceCard): string {
  const isActive = space.status === 'active';
  const statusLabel = getStatusLabel(space.status);

  return `
    <article class="project-card project-card--${space.status}">
      <div class="project-card__body">
        <div class="project-card__header">
          ${space.categoryName ? `<span class="project-card__badge project-card__badge--category">${escapeHtml(space.categoryName)}</span>` : ''}
          <span class="project-card__badge project-card__badge--${getStatusVariant(space.status)}">${statusLabel}</span>
        </div>
        <h3 class="project-card__title">${escapeHtml(space.title)}</h3>
        <div class="project-card__meta">
          <div class="project-card__meta-item">
            <svg class="project-card__meta-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            <span><strong>${String(space.memberCount)}</strong> Mitglieder</span>
          </div>
          <span class="project-card__age">Erstellt: ${formatDate(space.createdAt)}</span>
        </div>
        <div class="project-card__footer">
          <div class="project-card__initiator">
            ${space.initiatorAvatarUrl ? `<img class="project-card__avatar-img" src="${escapeHtml(space.initiatorAvatarUrl)}" alt="${escapeHtml(space.initiatorName)}">` : `<div class="project-card__avatar${isActive ? '' : ' project-card__avatar--muted'}">${escapeHtml(getInitials(space.initiatorName))}</div>`}
            <div class="project-card__initiator-info">
              <span class="project-card__initiator-name">${escapeHtml(space.initiatorName)}</span>
              <span class="project-card__initiator-role">Initiator·in</span>
            </div>
          </div>
          ${getActionButton(space)}
        </div>
      </div>
    </article>`;
}
```

Key changes: removed accent bar, removed description, removed update section, moved age to meta row, restructured layout.

- [ ] **Step 5: Build and visually verify**

```bash
npm run development
```

Open `/projektraeume` in browser. Verify:
- Cards show without descriptions
- Cards have 2px border, 20px radius, no accent bar
- Badges, meta info, initiator row, and action buttons display correctly
- Hover lift effect works
- Completed/archived cards show muted styling

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/project-card/
git add resources/js/single_pages/projektraeume.ts
git commit -m "feat: compact project card layout matching Figma design"
```

---

### Task 5: Create FAB Component

**Files:**
- Create: `public/packages/inno_frontend/components/atoms/fab/fab.php`
- Create: `public/packages/inno_frontend/components/atoms/fab/fab.scss`
- Create: `public/packages/inno_frontend/components/atoms/fab/fab.variants.php`
- Create: `public/packages/inno_frontend/components/atoms/fab/fab.stories.json`
- Modify: `resources/css/main.scss` (add fab import)
- Modify: `public/packages/inno_frontend/themes/innovationsbox/elements/footer.php` (add FAB before footer)

**Interfaces:**
- Consumes: CSS variables `--fab-size`, `--fab-bg` from Task 1
- Produces: FAB component visible on every page, linking to idea submission

- [ ] **Step 1: Create fab.php**

Create `public/packages/inno_frontend/components/atoms/fab/fab.php`:

```php
<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

$href = (string) ($props['href'] ?? '/projektidee/einreichen');
$label = (string) ($props['label'] ?? 'Neue Projektidee einreichen');
?>
<a href="<?= htmlspecialchars($href) ?>" class="fab" aria-label="<?= htmlspecialchars($label) ?>">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
</a>
```

- [ ] **Step 2: Create fab.scss**

Create `public/packages/inno_frontend/components/atoms/fab/fab.scss`:

```scss
@use "~@helpers/a11y" as a11y;

.fab {
  position: fixed;
  bottom: var(--space-6, 24px);
  right: var(--space-6, 24px);
  width: var(--fab-size, 87px);
  height: var(--fab-size, 87px);
  border-radius: 50%;
  background: var(--fab-bg, var(--color-brand-primary-500, #b7cd57));
  color: var(--color-white);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-md, 0 3px 10px rgba(0, 0, 0, 0.1));
  text-decoration: none;
  z-index: var(--z-dropdown, 1000);
  transition: transform 150ms ease, background-color 150ms ease, box-shadow 150ms ease;

  @media (prefers-reduced-motion: reduce) {
    transition: none;
  }

  &:hover {
    background: var(--color-brand-primary-600, #9db247);
    transform: scale(1.05);
    box-shadow: var(--shadow-lg, 0 10px 25px rgba(0, 0, 0, 0.1));
    color: var(--color-white);
    text-decoration: none;
  }

  &:active {
    transform: scale(0.98);
  }

  @include a11y.focus-visible-ring;
}
```

- [ ] **Step 3: Create fab.variants.php**

Create `public/packages/inno_frontend/components/atoms/fab/fab.variants.php`:

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'href' => '/projektidee/einreichen',
        'label' => 'Neue Projektidee einreichen',
    ],
];
```

- [ ] **Step 4: Create fab.stories.json**

Create `public/packages/inno_frontend/components/atoms/fab/fab.stories.json`:

```json
{
  "title": "Atoms/FAB",
  "stories": [
    {
      "name": "Default",
      "props": {}
    }
  ]
}
```

- [ ] **Step 5: Add FAB import to main.scss**

In `resources/css/main.scss`, add the fab import alongside other atom imports (after the last atom import line):

```scss
@use "../public/packages/inno_frontend/components/atoms/fab/fab";
```

- [ ] **Step 6: Add FAB to the theme footer template**

In `public/packages/inno_frontend/themes/innovationsbox/elements/footer.php`, add the FAB include just before the `<footer>` tag (line 11):

```php
<?php
$props = [];
include DIR_COMPONENTS . '/atoms/fab/fab.php';
?>

<footer>
```

- [ ] **Step 7: Remove the "Projektidee einreichen" button from projektraeume page**

In `public/packages/inno_frontend/single_pages/projektraeume.php`, remove the CTA button from the header (line 13):

```php
<!-- Remove this line: -->
<a href="/projektidee/einreichen" class="btn btn--primary"><?= t('Projektidee einreichen') ?></a>
```

The header section should become:

```php
    <header class="projektraeume-page__header">
        <div class="projektraeume-page__header-text">
            <h1 class="projektraeume-page__title"><?= t('Projekträume') ?></h1>
            <p class="projektraeume-page__subtitle"><?= t('Laufende und abgeschlossene Klimaprojekte unserer Community') ?></p>
        </div>
    </header>
```

Note: The title/subtitle text will be updated in Task 6 (rename).

- [ ] **Step 8: Build and visually verify**

```bash
npm run development
```

Open the app. Verify:
- FAB appears in bottom-right corner on every page
- FAB is 87px, green, with white "+" icon
- Hover scales up slightly and darkens
- Click navigates to idea submission
- FAB doesn't overlap important content
- FAB has proper focus ring for keyboard navigation

- [ ] **Step 9: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/fab/
git add resources/css/main.scss
git add public/packages/inno_frontend/themes/innovationsbox/elements/footer.php
git add public/packages/inno_frontend/single_pages/projektraeume.php
git commit -m "feat: add floating action button for idea submission on all pages"
```

---

### Task 6: Rename Projekträume → Innovationen

**Files:**
- Rename: `public/packages/inno_frontend/single_pages/projektraeume.php` → `innovationen.php`
- Rename: `public/packages/inno_frontend/controllers/single_page/projektraeume.php` → `innovationen.php`
- Rename: `resources/css/single_pages/projektraeume.scss` → `innovationen.scss`
- Rename: `resources/js/single_pages/projektraeume.ts` → `innovationen.ts`
- Modify: `public/packages/inno_frontend/controller.php:158-166,313` (asset registration and single page path)
- Modify: `public/packages/inno_frontend/components/molecules/user-menu/user-menu.php` (menu link)
- Modify: `resources/js/single_pages/projektraeume.ts` → `innovationen.ts` (empty message text)

**Interfaces:**
- Consumes: All prior tasks (this is a rename + content update)
- Produces: All references changed from "Projekträume" to "Innovationen"

- [ ] **Step 1: Rename single page template**

```bash
git mv public/packages/inno_frontend/single_pages/projektraeume.php \
       public/packages/inno_frontend/single_pages/innovationen.php
```

- [ ] **Step 2: Update single page template content**

In `public/packages/inno_frontend/single_pages/innovationen.php`, update the class name and text:

Change `projektraeume-page` to `innovationen-page` throughout, and update text:

```php
<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');
?>
<div class="innovationen-page">
    <header class="innovationen-page__header">
        <div class="innovationen-page__header-text">
            <h1 class="innovationen-page__title"><?= t('Innovationen') ?></h1>
            <p class="innovationen-page__subtitle"><?= t('Alle laufenden und abgeschlossenen Innovationen unserer Community') ?></p>
        </div>
    </header>

    <div class="innovationen-page__toolbar">
        <div class="innovationen-page__search">
            <input type="search" class="innovationen-page__search-input" id="project-search"
                   placeholder="<?= t('Innovationen durchsuchen...') ?>" aria-label="<?= t('Suche') ?>">
        </div>
        <nav class="innovationen-page__filters" id="project-filters" aria-label="<?= t('Filter') ?>">
            <button class="unstyled-button innovationen-page__filter innovationen-page__filter--active" data-value="" type="button"><?= t('Alle') ?></button>
            <button class="unstyled-button innovationen-page__filter" data-value="active" type="button"><?= t('Aktiv') ?></button>
            <button class="unstyled-button innovationen-page__filter" data-value="completed" type="button"><?= t('Abgeschlossen') ?></button>
            <button class="unstyled-button innovationen-page__filter" data-value="archived" type="button" id="filter-archived" hidden><?= t('Archiviert') ?></button>
        </nav>
        <span class="innovationen-page__separator"></span>
        <nav class="innovationen-page__filters innovationen-page__filters--categories" id="category-filters" aria-label="<?= t('Kategorie-Filter') ?>">
            <button class="unstyled-button innovationen-page__filter innovationen-page__filter--active" data-category="" type="button"><?= t('Alle') ?></button>
        </nav>
    </div>

    <div class="innovationen-page__grid" id="project-spaces-grid">
        <div class="innovationen-page__loading">
            <?php
    $props = ['size' => 'lg'];
include DIR_COMPONENTS . '/atoms/spinner/spinner.php';
?>
        </div>
    </div>

    <div class="innovationen-page__pagination" id="project-pagination"></div>
</div>
```

- [ ] **Step 3: Rename and update controller**

```bash
git mv public/packages/inno_frontend/controllers/single_page/projektraeume.php \
       public/packages/inno_frontend/controllers/single_page/innovationen.php
```

Update the class name and asset group in the new file:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage;

use Concrete\Core\Page\Controller\PageController;

class Innovationen extends PageController
{
    public function view(): void
    {
        $this->requireAsset('inno_frontend/innovationen');
    }
}
```

- [ ] **Step 4: Rename SCSS file and update class names**

```bash
git mv resources/css/single_pages/projektraeume.scss \
       resources/css/single_pages/innovationen.scss
```

In `resources/css/single_pages/innovationen.scss`, replace all `projektraeume-page` with `innovationen-page`:

```scss
@use "../helper/functions" as f;
@use "../global/_generated-arrays" as t;
@use "../../../public/packages/inno_frontend/components/molecules/project-card/project-card";

.innovationen-page {
```

And update every `&__` selector — the BEM class prefix changes from `.projektraeume-page` to `.innovationen-page`. This is a find-and-replace of `projektraeume-page` → `innovationen-page` throughout the file.

- [ ] **Step 5: Rename TypeScript file and update content**

```bash
git mv resources/js/single_pages/projektraeume.ts \
       resources/js/single_pages/innovationen.ts
```

In `resources/js/single_pages/innovationen.ts`, update:
- Line 160: loading spinner class: `projektraeume-page__loading` → `innovationen-page__loading`
- Line 171: empty message: `'Keine Projekträume gefunden.'` → `'Keine Innovationen gefunden.'` and class `projektraeume-page__empty` → `innovationen-page__empty`
- All other `projektraeume-page__filter` references → `innovationen-page__filter`

- [ ] **Step 6: Update controller.php asset registration**

In `public/packages/inno_frontend/controller.php`, update lines 158-166 and line 313:

Replace `projektraeume` with `innovationen` in asset registration:

```php
        // Single page assets — innovationen
        $al->register('css', 'inno_frontend.innovationen', 'themes/innovationsbox/css/single_pages/innovationen.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'inno_frontend.innovationen', 'themes/innovationsbox/js/single_pages/innovationen.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        $al->registerGroup('inno_frontend/innovationen', [
            ...
            ['css', 'inno_frontend.innovationen'],
            ['javascript', 'inno_frontend.innovationen'],
        ]);
```

And update the single page path:

```php
            '/innovationen' => ['name' => 'Innovationen', 'excludeNav' => false],
```

- [ ] **Step 7: Update user-menu link**

Check `public/packages/inno_frontend/components/molecules/user-menu/user-menu.php` for any references to `/projektraeume` and update to `/innovationen`.

- [ ] **Step 8: Update grid columns to 3**

In `resources/css/single_pages/innovationen.scss`, update the grid to 3 columns to match the Figma:

```scss
  &__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-4);

    @media (width <= 1024px) {
      grid-template-columns: repeat(2, 1fr);
    }

    @media (width <= 768px) {
      grid-template-columns: 1fr;
    }
  }
```

- [ ] **Step 9: Build, lint, and verify**

```bash
npm run development
npm run lint:all
composer lint:php
```

- [ ] **Step 10: Commit**

```bash
git add -A public/packages/inno_frontend/single_pages/innovationen.php \
  public/packages/inno_frontend/controllers/single_page/innovationen.php \
  resources/css/single_pages/innovationen.scss \
  resources/js/single_pages/innovationen.ts \
  public/packages/inno_frontend/controller.php \
  public/packages/inno_frontend/components/molecules/user-menu/user-menu.php
# Also stage the deletions of old files
git add -u public/packages/inno_frontend/single_pages/projektraeume.php \
  public/packages/inno_frontend/controllers/single_page/projektraeume.php \
  resources/css/single_pages/projektraeume.scss \
  resources/js/single_pages/projektraeume.ts
git commit -m "feat: rename Projekträume to Innovationen"
```

---

### Task 7: Visual Consistency Sweep

**Files:**
- Potentially modify: various component SCSS files across the component library
- No structural changes — only color/font/spacing tweaks

**Interfaces:**
- Consumes: All prior tasks
- Produces: Visually consistent app across all pages

- [ ] **Step 1: Check login/register pages**

Open `/login` and `/register` in browser. Verify:
- Auth-card looks correct with Open Sans
- Buttons are styled properly
- Form fields render correctly
- Dark header looks right on these pages

- [ ] **Step 2: Check projektraum detail page**

Open a project space detail page. Verify:
- Sidebar harmonizes with new design
- Status-update-timeline looks correct
- Member cards render properly
- All text uses Open Sans

- [ ] **Step 3: Check moderation pages**

Open `/moderation` (as moderator). Verify:
- Table/list views look consistent
- Buttons and badges use correct tokens

- [ ] **Step 4: Check profile/account pages**

Open `/account/edit_profile`. Verify:
- Form fields render correctly
- Avatar upload works
- All typography uses Open Sans

- [ ] **Step 5: Check idea submission page**

Open `/projektidee/einreichen`. Verify:
- Form renders correctly
- CKEditor area looks fine
- Submit button styled properly

- [ ] **Step 6: Check homepage**

Open `/`. Verify:
- Hero image works with dark header
- Content blocks look consistent
- FAB is visible and doesn't overlap hero content

- [ ] **Step 7: Fix any visual inconsistencies found**

For each issue found, make targeted SCSS adjustments. Common fixes might include:
- Adjusting text colors that don't work against the new header
- Fixing button styles that reference old Inter metrics
- Adjusting spacing where Open Sans needs slightly different values

- [ ] **Step 8: Run full lint check**

```bash
npm run lint:all
composer lint:php
```

- [ ] **Step 9: Commit any fixes**

```bash
git add -A
git commit -m "fix: visual consistency adjustments for design system rebuild"
```

---

### Task 8: Update E2E Tests and Final Verification

**Files:**
- Modify: `tests/e2e/specs/projektraeume.spec.ts` (rename references)
- Modify: `tests/e2e/specs/00-smoke.spec.ts` (update URL references)
- Modify: `tests/e2e/specs/02-idea-to-space.spec.ts` (update references)
- Modify: `tests/e2e/specs/04-project-space.spec.ts` (update references)
- Modify: `tests/e2e/specs/01-auth.spec.ts` (update references if needed)

**Interfaces:**
- Consumes: All prior tasks
- Produces: Passing E2E test suite

- [ ] **Step 1: Update E2E test references**

Search all E2E test files for `projektraeume` and update to `innovationen`:

```bash
grep -rl "projektraeume" tests/e2e/
```

For each file found, update:
- URL paths: `/projektraeume` → `/innovationen`
- CSS selectors: `.projektraeume-page` → `.innovationen-page`
- Text expectations: "Projekträume" → "Innovationen"
- The empty state message: "Keine Projekträume gefunden" → "Keine Innovationen gefunden"

- [ ] **Step 2: Run linters**

```bash
npm run lint:all
composer lint:php
```

- [ ] **Step 3: Run unit tests**

```bash
composer test
```

- [ ] **Step 4: Build production assets**

```bash
npm run production
```

- [ ] **Step 5: Commit**

```bash
git add tests/e2e/
git commit -m "test: update E2E tests for Projekträume→Innovationen rename"
```

---

## Post-Implementation: CMS/Database Actions Checklist (Manual)

After all code is deployed, perform these actions in the CMS Dashboard:

- [ ] **Rename single page:** Dashboard → Sitemap → find "Projekträume" page → change name to "Innovationen" and path to `/innovationen`
- [ ] **Update navigation menu labels:** Any menu items referencing "Projekträume" → "Innovationen"
- [ ] **Update content block links:** Any content blocks on other pages that link to `/projektraeume` → update to `/innovationen`
- [ ] **Verify partner logos:** Ensure white/light versions of partner logos are uploaded for the new semi-transparent footer background
