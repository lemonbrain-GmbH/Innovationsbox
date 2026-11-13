# Storybook Design System & Component Library

**Date:** 2026-04-13
**Branch:** feature/ddd-package-struktur
**Status:** Approved

## Context

The Innovationsbox frontend uses a Concrete CMS theme (`inno_frontend`) with SCSS variables, fluid typography via `clamp()`, and vanilla TypeScript. There are 5 block templates (hero_image, content, feature_link, page_list, autonav), a header/footer, and a responsive container system. Currently there is no component library, no design token system, and no Storybook.

This spec defines a Storybook-based design system using Atomic Design methodology, PHP server-side rendering for 1:1 production fidelity, and a JSON-based design token pipeline.

---

## 1. Token System

### Source of truth

`resources/tokens/tokens.json` — a single JSON file defining all design tokens:

```json
{
  "color": {
    "primary": "#B7CD57",
    "secondary": "#89705B",
    "green": "#7B876F",
    "black": "#353535",
    "white": "#FFF",
    "light-overlay": "#FFFFFF80"
  },
  "font": {
    "family": { "heading": "Inter", "text": "Inter" },
    "weight": { "light": 300, "regular": 400, "semibold": 600, "bold": 700 }
  },
  "typography": {
    "h1": { "sizes": [22, 32, 48, 64], "lineHeights": [26, 38, 56, 78], "weight": 700, "color": "secondary" },
    "h2": { "sizes": [20, 24, 30, 40], "lineHeights": [26, 30, 36, 48], "weight": 600, "color": "black" },
    "h3": { "sizes": [18, 20, 24, 30], "lineHeights": [24, 26, 32, 38], "weight": 600, "color": "black" },
    "h4": { "sizes": [16, 18, 20, 24], "lineHeights": [22, 24, 28, 32], "weight": 600, "color": "black" },
    "h5": { "sizes": [14.5, 16.5, 18, 20], "lineHeights": [20, 22, 24, 28], "weight": 600, "color": "black" },
    "h6": { "sizes": [14.5, 16.5, 18, 20], "lineHeights": [20, 22, 24, 28], "weight": 600, "color": "black" },
    "body": { "sizes": [14.5, 16.5, 20, 24], "lineHeights": [20, 22, 28, 32], "weight": 300, "color": "black" },
    "lead": { "sizes": [18, 20, 24, 30], "lineHeights": [24, 26, 32, 38], "weight": 400, "color": "black" },
    "small": { "sizes": [12, 13, 14, 15], "lineHeights": [16, 18, 20, 22], "weight": 600, "color": "black" },
    "link": { "sizes": [14.5, 16.5, 20, 24], "lineHeights": [20, 22, 28, 32], "weight": 600, "color": "primary", "hoverColor": "green" },
    "button": { "sizes": [14.5, 16.5, 18, 20], "lineHeights": [20, 22, 24, 28], "weight": 600, "color": "white" }
  },
  "breakpoint": {
    "mobile": 480,
    "tablet": 768,
    "desktop": 1200,
    "wide": 1680
  },
  "spacing": {
    "base": [24, 32],
    "block": [30, 60]
  },
  "radius": {
    "default": 20,
    "small": 10
  },
  "shadow": {
    "default": "#0000001A 0 3px 10px"
  },
  "transition": {
    "default": "0.3s ease"
  }
}
```

### Build pipeline

A Node script `scripts/build-tokens.js` reads `tokens.json` and generates two SCSS files:

- `resources/css/global/_generated-variables.scss` — CSS custom properties in `:root` (replaces `variables_css.scss`)
- `resources/css/global/_generated-arrays.scss` — SCSS arrays for fluid typography scales (replaces `variables.scss`)

The existing `functions.scss` (`fluidVal()`/`fluidVar()`) stays hand-written and consumes the generated variables.

npm scripts:
```json
"tokens": "node scripts/build-tokens.js",
"prebuild": "npm run tokens",
"prestorybook": "npm run tokens"
```

Migration: `variables_css.scss` and `variables.scss` are deleted and replaced by the generated files. `main.scss` import paths are updated.

---

## 2. Component File Structure (Atomic Design)

Every component follows the same file pattern:

```
resources/components/<level>/<name>/
  <name>.php              ← pure partial: receives $props array, outputs HTML
  <name>.scss             ← styling (imports generated token variables)
  <name>.ts               ← optional interactivity (only where needed)
  <name>.variants.php     ← defines valid variants, props, defaults (single source of truth)
  <name>.stories.json     ← Storybook server story definition (references variants)
```

### Full component tree

```
resources/components/
  atoms/
    button/              ← CTA button (primary/secondary variants, sizes)
    heading/             ← h1-h6 (level prop controls tag + styling)
    paragraph/           ← body, lead, small text variants
    link/                ← styled anchor with hover state
    image/               ← responsive image (aspect ratio, object-fit, rounded)
    container/           ← lb-container, lb-container-narrow wrappers
    skip-link/           ← accessibility skip navigation link
    overlay/             ← white/dark blur overlay surface

  molecules/
    feature-button/      ← button + optional icon (from feature_link block)
    page-card/           ← thumbnail + title + link (single item from page_list)
    nav-item/            ← navigation link with active state
    accent-text/         ← content with decorative guillemets « »
    question-text/       ← content with decorative question mark ?
    partner-logo/        ← image + external link (footer partner)

  organisms/
    hero-image/          ← full-width image + overlay + heading + body + button
    page-list-grid/      ← grid container of page-card molecules
    navigation-drawer/   ← toggle + drawer + overlay + nav-items (with autonav TS)
    header/              ← skip-link + logo + navigation-drawer
    footer/              ← dark background + partner-logos + container
    content-block/       ← container + WYSIWYG content (accent/question support)

  templates/
    default-page/        ← header + page-header area + main slot + footer
    view-page/           ← header + hero-image + main slot + footer

  pages/
    homepage/            ← default-page filled with example content
    inner-page/          ← view-page filled with example content
```

### Composition rules

- Each level only imports from the level below (molecules import atoms, organisms import molecules, etc.)
- Every partial receives a single `$props` associative array and outputs HTML
- No global state, no side effects, no CMS dependencies in partials

### PHP partial convention

```php
// atoms/button/button.php
<?php
$variant = $props['variant'] ?? 'primary';
$size = $props['size'] ?? 'default';
$label = $props['label'] ?? '';
$href = $props['href'] ?? '#';
?>
<a class="button button--<?= $variant ?> button--<?= $size ?>" href="<?= htmlspecialchars($href) ?>">
  <?= htmlspecialchars($label) ?>
</a>
```

### DRY variant definitions

Each component defines its valid variants once in `<name>.variants.php`:

```php
// atoms/button/button.variants.php
<?php
return [
    'variants' => ['primary', 'secondary'],
    'sizes' => ['default', 'small', 'large'],
    'defaults' => ['variant' => 'primary', 'size' => 'default', 'label' => 'Button', 'href' => '#'],
];
```

Both the partial (for validation) and the story server (for auto-generating Storybook Controls) consume this file. One definition, two consumers.

---

## 3. Block Migration Strategy

Each of the 5 existing block templates is split into two files:

| Before (single file) | After |
|---|---|
| `blocks/<block>/.../view.php` (CMS data + HTML mixed) | `resources/components/<level>/<name>/<name>.php` (pure partial) |
| | `blocks/<block>/.../view.php` (thin CMS wrapper → includes partial) |

### CMS wrapper pattern

```php
// blocks/hero_image/templates/innovationsbox/view.php
<?php defined('C5_EXECUTE') or die;

$props = [
    'title' => $title ?? '',
    'body' => $body ?? '',
    'image' => $image ? $image->getURL() : '',
    'imageAlt' => $imageAlt ?? '',
    'buttonLabel' => $buttonText ?? '',
    'buttonHref' => $buttonLink ?? '#',
];

include DIR_COMPONENTS . '/organisms/hero-image/hero-image.php';
```

A constant `DIR_COMPONENTS` is defined once in `page_theme.php`:

```php
define('DIR_COMPONENTS', DIR_BASE . '/resources/components');
```

### SCSS migration

Existing block SCSS files in `resources/css/blocks/` move into the component folders. `webpack.mix.js` is updated to compile from the new locations and output to the same block paths Concrete CMS expects.

---

## 4. Storybook Server Architecture

### Two processes

1. **Storybook** (Node, port 6006) — the UI
2. **PHP story server** (PHP built-in server, port 6007) — renders component HTML on demand

### Request flow

```
Browser → Storybook (6006)
              ↓ fetch /render?component=atoms/button&variant=primary&label=Click+me
          PHP server (6007)
              ↓ includes resources/components/atoms/button/button.php with $props
          Returns HTML string
              ↓
          Storybook injects into preview iframe (with global CSS loaded)
```

### PHP server structure

```
storybook-server/
  index.php           ← router: parses ?component=...&...props, renders partial
  bootstrap.php       ← stubs for CMS functions: t(), h(), etc.
  render.php          ← includes partial, captures output with ob_start/ob_get_clean
```

`index.php` logic:
1. Parse `component` query param (e.g. `atoms/button`)
2. Parse remaining query params as `$props`
3. Validate component path exists under `resources/components/`
4. Include `bootstrap.php` (stubs)
5. Include `render.php` which loads the partial with `$props` and returns HTML
6. Output with `Content-Type: text/html`

### Storybook config

```
.storybook/
  main.js             ← @storybook/server config, story discovery from resources/components/**/*.stories.json
  preview.js          ← loads compiled global CSS (default.css) + Inter font
  preview-head.html   ← font-face declarations for Inter
```

### Story file format

```json
{
  "title": "Atoms/Button",
  "stories": [
    {
      "name": "Primary",
      "parameters": {
        "server": { "id": "atoms/button", "params": { "variant": "primary", "label": "Projektidee einreichen", "href": "#" } }
      }
    },
    {
      "name": "Secondary",
      "parameters": {
        "server": { "id": "atoms/button", "params": { "variant": "secondary", "label": "Mehr erfahren", "href": "#" } }
      }
    }
  ]
}
```

### npm scripts

```json
"storybook": "concurrently \"php -S localhost:6007 storybook-server/index.php\" \"storybook dev -p 6006\"",
"storybook:build": "storybook build"
```

---

## 5. Dev Environment (flake.nix)

Add PHP to the Nix dev shell for the story server:

```nix
packages = [
  claude-code.packages.${system}.default
  pkgs.nodejs_20
  pkgs.php83
];
```

The story server uses PHP's built-in web server (`php -S localhost:6007`). No Docker dependency for Storybook development.

---

## 6. Webpack / Build Integration

`webpack.mix.js` changes:

- Component SCSS files are compiled alongside existing block SCSS
- Block output paths remain the same (Concrete CMS expects them at specific locations)
- Global CSS (`main.scss`) imports the generated token files instead of the old hand-written variable files
- Storybook loads the same compiled `default.css` that the CMS uses

---

## 7. Testing

Three levels:

**1. Visual: Storybook itself**
- Every component has stories covering all variants
- Living documentation and visual regression baseline

**2. Structural: PHP partial validation**
- `<name>.variants.php` defines valid props and defaults
- Story server validates incoming props before rendering
- Invalid props return clear error in Storybook

**3. E2E: Playwright**
- `tests/e2e/specs/storybook/atoms.spec.ts` — verify atom stories render without error
- `tests/e2e/specs/storybook/organisms.spec.ts` — verify complex organisms render correctly
- Runs with existing `npm run test:e2e` pipeline

No snapshot testing — visual review in Storybook plus structural E2E checks are sufficient at this project size.

---

## 8. What Changes, What Stays

**New files/directories:**
- `resources/tokens/tokens.json` — design token source of truth
- `scripts/build-tokens.js` — token → SCSS generator
- `resources/components/` — full Atomic Design component tree
- `.storybook/` — Storybook configuration
- `storybook-server/` — PHP story rendering server
- `tests/e2e/specs/storybook/` — Storybook E2E tests

**Modified:**
- `flake.nix` — add `pkgs.php83`
- `package.json` — add Storybook + concurrently dependencies, new scripts
- `webpack.mix.js` — updated SCSS compile paths for components
- `resources/css/main.scss` — import generated token files
- `resources/css/global/variables_css.scss` → deleted (replaced by generated)
- `resources/css/global/variables.scss` → deleted (replaced by generated)
- `public/packages/inno_frontend/themes/innovationsbox/page_theme.php` — add `DIR_COMPONENTS` constant
- Block `view.php` templates — refactored to thin wrappers that include component partials

**Unchanged:**
- `resources/css/global/helper/functions.scss` — fluid functions stay hand-written
- `resources/css/global/global.scss`, `presets.scss`, `fonts.css` — base styles unchanged
- `resources/js/main.ts` — entry point unchanged
- All PHP package controllers and DDD architecture
- All backend packages (`inno_project_idea`, etc.)
