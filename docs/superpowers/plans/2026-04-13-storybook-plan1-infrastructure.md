# Storybook Plan 1: Infrastructure & Token System

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Set up the design token pipeline, Storybook with `@storybook/server`, and the PHP story server — so that a component added later renders correctly with the project's theme.

**Architecture:** `tokens.json` is the single source of truth for design tokens. A Node script generates SCSS variables from it. Storybook uses `@storybook/server` to fetch rendered HTML from a lightweight PHP dev server. The compiled global CSS is loaded into Storybook's preview iframe.

**Tech Stack:** Storybook 8 (`@storybook/server`), PHP 8.3 (built-in server), Node.js 20, SCSS (Laravel Mix), `concurrently` for parallel processes

**Spec:** `docs/superpowers/specs/2026-04-13-storybook-design-system-design.md`

**Plan series:** This is Plan 1 of 4. Plan 2 = Atoms, Plan 3 = Molecules + Organisms, Plan 4 = Templates + Pages + Block migration.

---

### Task 1: Create `tokens.json`

**Files:**
- Create: `resources/tokens/tokens.json`

- [ ] **Step 1: Create the tokens directory**

```bash
mkdir -p resources/tokens
```

- [ ] **Step 2: Create `resources/tokens/tokens.json`**

This is the single source of truth for all design tokens, extracted from the existing `variables_css.scss` and `variables.scss`.

```json
{
  "color": {
    "green": "#7B876F",
    "brown": "#89705B",
    "lightgreen": "#B7CD57",
    "primary": "#B7CD57",
    "secondary": "#89705B",
    "white": "#FFF",
    "black": "#353535",
    "light-overlay": "#FFFFFF80"
  },
  "font": {
    "family": {
      "heading": "'Inter', sans-serif",
      "text": "'Inter', sans-serif"
    },
    "weight": {
      "light": 300,
      "regular": 400,
      "semibold": 600,
      "bold": 700
    }
  },
  "typography": {
    "h1": {
      "sizes": [22, 32, 48, 64],
      "lineHeights": [26, 38, 56, 78],
      "weight": 700,
      "color": "secondary",
      "family": "heading"
    },
    "h2": {
      "sizes": [20, 24, 30, 40],
      "lineHeights": [26, 30, 36, 48],
      "weight": 600,
      "color": "black",
      "family": "heading"
    },
    "h3": {
      "sizes": [18, 20, 24, 30],
      "lineHeights": [24, 26, 32, 38],
      "weight": 600,
      "color": "black",
      "family": "heading"
    },
    "h4": {
      "sizes": [16, 18, 20, 24],
      "lineHeights": [22, 24, 28, 32],
      "weight": 600,
      "color": "black",
      "family": "heading"
    },
    "h5": {
      "sizes": [14.5, 16.5, 18, 20],
      "lineHeights": [20, 22, 24, 28],
      "weight": 600,
      "color": "black",
      "family": "heading"
    },
    "h6": {
      "sizes": [14.5, 16.5, 18, 20],
      "lineHeights": [20, 22, 24, 28],
      "weight": 600,
      "color": "black",
      "family": "heading"
    },
    "body": {
      "sizes": [14.5, 16.5, 20, 24],
      "lineHeights": [20, 22, 28, 32],
      "weight": 300,
      "color": "black",
      "family": "text"
    },
    "lead": {
      "sizes": [18, 20, 24, 30],
      "lineHeights": [24, 26, 32, 38],
      "weight": 400,
      "color": "black",
      "family": "text"
    },
    "small": {
      "sizes": [12, 13, 14, 15],
      "lineHeights": [16, 18, 20, 22],
      "weight": 600,
      "color": "black",
      "family": "text"
    },
    "link": {
      "sizes": [14.5, 16.5, 20, 24],
      "lineHeights": [20, 22, 28, 32],
      "weight": 600,
      "color": "primary",
      "hoverColor": "green",
      "family": "text"
    },
    "list": {
      "sizes": [14.5, 16.5, 20, 24],
      "lineHeights": [20, 22, 28, 32],
      "weight": 400,
      "color": "black",
      "family": "text"
    },
    "button": {
      "sizes": [14.5, 16.5, 18, 20],
      "lineHeights": [20, 22, 24, 28],
      "weight": 600,
      "color": "white",
      "family": "text"
    }
  },
  "breakpoint": {
    "mobile": 480,
    "tablet": 768,
    "desktop": 1200,
    "wide": 1680
  },
  "container": {
    "maxWidth": 1620,
    "mediumWidth": 1340,
    "smallWidth": 1080,
    "width": "85vw",
    "widthMobile": "89vw",
    "widthMd": "80vw",
    "narrowWidth": "57vw"
  },
  "spacing": {
    "base": [24, 32],
    "block": [30, 60],
    "blockSpaceMax": 60,
    "blockSpaceMin": 30
  },
  "radius": {
    "default": 20,
    "small": 10
  },
  "shadow": {
    "default": "#0000001A 0 3px 10px"
  },
  "transition": {
    "default": "0.3s"
  }
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/tokens/tokens.json
git commit -m "feat(tokens): add tokens.json as single source of truth for design tokens"
```

---

### Task 2: Create the token build script

**Files:**
- Create: `scripts/build-tokens.js`

- [ ] **Step 1: Create `scripts/build-tokens.js`**

This script reads `tokens.json` and generates two SCSS files that replace the current hand-written `variables_css.scss` and `variables.scss`.

```javascript
#!/usr/bin/env node
const fs = require('fs');
const path = require('path');

const TOKENS_PATH = path.resolve(__dirname, '../resources/tokens/tokens.json');
const OUT_DIR = path.resolve(__dirname, '../resources/css/global');

const tokens = JSON.parse(fs.readFileSync(TOKENS_PATH, 'utf-8'));

// ---------------------------------------------------------------------------
// 1. Generate _generated-variables.scss (CSS custom properties)
// ---------------------------------------------------------------------------
function buildCssVariables() {
  const lines = [
    '// AUTO-GENERATED from tokens.json — do not edit manually.',
    '// Run: npm run tokens',
    '',
    '@use "../helper/functions" as f;',
    '',
    ':root {',
  ];

  // Screen sizes (derived from breakpoints, consumed by functions.scss media queries)
  lines.push('  // screen sizes');
  lines.push(`  --upper-bound: #{f.$upperBound-factor}px;`);
  lines.push(`  --lower-bound: #{f.$lowerBound-factor}px;`);
  lines.push(`  --upper-bound-mobile: #{f.$upperBoundMobile-factor}px;`);
  lines.push('');

  // General values
  lines.push('  // general values');
  lines.push(`  --transition-time: ${tokens.transition.default};`);
  lines.push('  --lighten-amount: 20%;');
  lines.push('  --darken-amount: 20%;');
  lines.push('');

  // Spacing
  lines.push(`  --block-space-max-factor: ${tokens.spacing.blockSpaceMax};`);
  lines.push(`  --block-space-min-factor: ${tokens.spacing.blockSpaceMin};`);
  lines.push('');

  // Border radius
  lines.push(`  --border-radius: ${tokens.radius.default}px;`);
  lines.push(`  --border-radius-small: ${tokens.radius.small}px;`);
  lines.push('');
  lines.push(`  @media screen and (max-width: #{f.$lowerBound-factor}px) {`);
  lines.push('    --border-radius: var(--border-radius-small);');
  lines.push('  }');
  lines.push('');

  // Shadow
  lines.push(`  --box-shadow: ${tokens.shadow.default};`);
  lines.push('');

  // Colors
  lines.push('  // colors');
  for (const [name, value] of Object.entries(tokens.color)) {
    // Skip aliases that reference other colors by name
    if (name === 'primary') {
      lines.push(`  --color-primary: var(--color-lightgreen);`);
    } else if (name === 'secondary') {
      lines.push(`  --color-secondary: var(--color-brown);`);
    } else {
      lines.push(`  --color-${name}: ${value};`);
    }
  }
  lines.push('');

  // Font families
  lines.push(`  --heading-font-family: ${tokens.font.family.heading};`);
  lines.push(`  --text-font-family: ${tokens.font.family.text};`);
  lines.push('');

  // Typography CSS variables (per-element font-family, font-weight, color)
  lines.push('  // typography');
  const typoMap = {
    h1: 'h1', h2: 'h2', h3: 'h3', h4: 'h4', h5: 'h5', h6: 'h6',
    body: 'p', lead: 'lead', small: 'small', link: 'a', list: 'list', button: 'button',
  };

  for (const [tokenKey, cssPrefix] of Object.entries(typoMap)) {
    const t = tokens.typography[tokenKey];
    if (!t) continue;
    const familyVar = t.family === 'heading' ? '--heading-font-family' : '--text-font-family';

    lines.push(`  --${cssPrefix}-font-family: var(${familyVar});`);
    lines.push(`  --${cssPrefix}-font-weight: ${t.weight};`);

    if (tokenKey === 'link') {
      lines.push(`  --${cssPrefix}-font-color: var(--color-${t.color});`);
      lines.push(`  --${cssPrefix}-font-color-hover: var(--color-${t.hoverColor});`);
      lines.push(`  --${cssPrefix}-text-decoration: none;`);
      lines.push(`  --${cssPrefix}-text-decoration-hover: none;`);
    } else {
      lines.push(`  --${cssPrefix}-font-color: var(--color-${t.color});`);
    }
    lines.push('');
  }

  // Extra variables from original
  lines.push('  --font-weight-normal: 400;');
  lines.push('  --font-weight-bold: 600;');
  lines.push('  --list-style-type: none;');

  lines.push('}');

  return lines.join('\n') + '\n';
}

// ---------------------------------------------------------------------------
// 2. Generate _generated-arrays.scss (SCSS arrays for responsive-fluid)
// ---------------------------------------------------------------------------
function buildScssArrays() {
  const lines = [
    '// AUTO-GENERATED from tokens.json — do not edit manually.',
    '// Run: npm run tokens',
    '',
  ];

  // Base sizes (used as aliases for p, a, list)
  lines.push(`$base-font-sizes: (${tokens.typography.body.sizes.join(', ')});`);
  lines.push(`$base-line-heights: (${tokens.typography.body.lineHeights.join(', ')});`);
  lines.push('');

  const arrayMap = {
    p: 'body', lead: 'lead', small: 'small',
    h1: 'h1', h2: 'h2', h3: 'h3', h4: 'h4', h5: 'h5', h6: 'h6',
    a: 'link', list: 'list', button: 'button',
  };

  for (const [scssPrefix, tokenKey] of Object.entries(arrayMap)) {
    const t = tokens.typography[tokenKey];
    // Use alias for body-derived entries
    if (tokenKey === 'body') {
      lines.push(`$${scssPrefix}-font-sizes: $base-font-sizes;`);
      lines.push(`$${scssPrefix}-line-heights: $base-line-heights;`);
    } else if (tokenKey === 'link' || tokenKey === 'list') {
      // link and list share body sizes in the original
      lines.push(`$${scssPrefix}-font-sizes: (${t.sizes.join(', ')});`);
      lines.push(`$${scssPrefix}-line-heights: (${t.lineHeights.join(', ')});`);
    } else {
      lines.push(`$${scssPrefix}-font-sizes: (${t.sizes.join(', ')});`);
      lines.push(`$${scssPrefix}-line-heights: (${t.lineHeights.join(', ')});`);
    }
    lines.push('');
  }

  return lines.join('\n');
}

// ---------------------------------------------------------------------------
// Write files
// ---------------------------------------------------------------------------
fs.mkdirSync(OUT_DIR, { recursive: true });

const varsContent = buildCssVariables();
const arraysContent = buildScssArrays();

fs.writeFileSync(path.join(OUT_DIR, '_generated-variables.scss'), varsContent);
fs.writeFileSync(path.join(OUT_DIR, '_generated-arrays.scss'), arraysContent);

console.log('Tokens built:');
console.log('  → resources/css/global/_generated-variables.scss');
console.log('  → resources/css/global/_generated-arrays.scss');
```

- [ ] **Step 2: Run the token build script**

```bash
node scripts/build-tokens.js
```

Expected output:
```
Tokens built:
  → resources/css/global/_generated-variables.scss
  → resources/css/global/_generated-arrays.scss
```

- [ ] **Step 3: Verify `_generated-variables.scss` contains `:root` with all CSS custom properties**

```bash
head -30 resources/css/global/_generated-variables.scss
```

Expected: File starts with `// AUTO-GENERATED from tokens.json`, contains `:root {`, has `--color-primary`, `--border-radius`, etc.

- [ ] **Step 4: Verify `_generated-arrays.scss` contains SCSS arrays**

```bash
head -20 resources/css/global/_generated-arrays.scss
```

Expected: File contains `$base-font-sizes: (14.5, 16.5, 20, 24);`, `$h1-font-sizes: (22, 32, 48, 64);`, etc.

- [ ] **Step 5: Commit**

```bash
git add scripts/build-tokens.js resources/css/global/_generated-variables.scss resources/css/global/_generated-arrays.scss
git commit -m "feat(tokens): add build-tokens.js script and generated SCSS files"
```

---

### Task 3: Migrate SCSS to use generated token files

**Files:**
- Modify: `resources/css/main.scss`
- Modify: `resources/css/global/presets.scss`
- Delete: `resources/css/global/variables_css.scss`
- Delete: `resources/css/global/variables.scss`

- [ ] **Step 1: Update `resources/css/main.scss` to import generated files**

Replace the current content:

```scss
@use "../../node_modules/normalize.css/normalize.css";

@use "global/_generated-variables.scss";
@use "global/fonts.css";

// global
@use "global/mixins.scss";

@use "global/global.scss";
@use "global/cke.scss";

// layout
@use "layout/header.scss";
@use "layout/footer.scss";

.ccm-page {
  @import "global/presets.scss";
}
```

The only change is line 3: `global/variables_css.scss` → `global/_generated-variables.scss`.

- [ ] **Step 2: Update `resources/css/global/presets.scss` to import generated arrays**

Change line 2 from:
```scss
@use "./variables" as v;
```
to:
```scss
@use "./generated-arrays" as v;
```

All references to `v.$p-font-sizes`, `v.$h1-font-sizes`, etc. continue to work because the generated file uses the same variable names.

- [ ] **Step 3: Delete old variable files**

```bash
rm resources/css/global/variables_css.scss
rm resources/css/global/variables.scss
```

- [ ] **Step 4: Verify the SCSS still compiles**

```bash
npm run development
```

Expected: Build completes without errors. The compiled `public/packages/inno_frontend/themes/innovationsbox/css/skins/default.css` should be functionally identical.

- [ ] **Step 5: Commit**

```bash
git add resources/css/main.scss resources/css/global/presets.scss
git rm resources/css/global/variables_css.scss resources/css/global/variables.scss
git commit -m "refactor(tokens): migrate SCSS to use generated token files"
```

---

### Task 4: Update `flake.nix` and install npm dependencies

**Files:**
- Modify: `flake.nix`
- Modify: `package.json`

- [ ] **Step 1: Add PHP to `flake.nix`**

Replace the current `packages` list:

```nix
{
  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";
    claude-code.url = "github:sadjow/claude-code-nix";
  };

  outputs =
    { nixpkgs, claude-code, ... }:
    let
      system = "x86_64-linux";
      pkgs = import nixpkgs { inherit system; };
    in
    {
      devShells.${system}.default = pkgs.mkShell {
        packages = [
          claude-code.packages.${system}.default
          pkgs.nodejs_20
          pkgs.php83
        ];
      };
    };
}
```

The only change is adding `pkgs.php83`.

- [ ] **Step 2: Install Storybook and dependencies**

```bash
npm install --save-dev @storybook/server @storybook/addon-essentials @storybook/addon-links storybook concurrently
```

- [ ] **Step 3: Add npm scripts to `package.json`**

Add these to the `scripts` section of `package.json`:

```json
"tokens": "node scripts/build-tokens.js",
"prestorybook": "npm run tokens",
"storybook": "concurrently \"php -S localhost:6007 storybook-server/index.php\" \"storybook dev -p 6006\"",
"storybook:build": "npm run tokens && storybook build"
```

- [ ] **Step 4: Commit**

```bash
git add flake.nix package.json package-lock.json
git commit -m "feat(storybook): add PHP to flake.nix, install Storybook and concurrently"
```

---

### Task 5: Create the PHP story server

**Files:**
- Create: `storybook-server/index.php`
- Create: `storybook-server/bootstrap.php`
- Create: `storybook-server/render.php`

- [ ] **Step 1: Create the storybook-server directory**

```bash
mkdir -p storybook-server
```

- [ ] **Step 2: Create `storybook-server/bootstrap.php`**

Stubs for Concrete CMS functions that component partials might reference. Keeps partials portable.

```php
<?php
declare(strict_types=1);

/**
 * Storybook bootstrap — stubs for Concrete CMS functions so that
 * component partials can render outside the CMS context.
 */

// Concrete CMS translation function
if (!function_exists('t')) {
    function t(string $text, ...$args): string {
        if (empty($args)) {
            return $text;
        }
        return vsprintf($text, $args);
    }
}

// Concrete CMS HTML escaping helper
if (!function_exists('h')) {
    function h(?string $text): string {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Component base path
define('DIR_COMPONENTS', dirname(__DIR__) . '/resources/components');
```

- [ ] **Step 3: Create `storybook-server/render.php`**

Renders a component partial with given props. Used by `index.php`.

```php
<?php
declare(strict_types=1);

/**
 * Renders a component partial and returns the HTML string.
 *
 * @param string $componentPath Relative path like "atoms/button"
 * @param array<string, mixed> $props Props to pass to the partial
 * @return string Rendered HTML
 */
function renderComponent(string $componentPath, array $props): string
{
    $componentName = basename($componentPath);
    $fullPath = DIR_COMPONENTS . '/' . $componentPath . '/' . $componentName . '.php';

    if (!file_exists($fullPath)) {
        http_response_code(404);
        return '<div style="color:red;padding:20px;">Component not found: '
            . htmlspecialchars($componentPath) . '</div>';
    }

    // Load variant defaults if available
    $variantsFile = DIR_COMPONENTS . '/' . $componentPath . '/' . $componentName . '.variants.php';
    if (file_exists($variantsFile)) {
        $variants = require $variantsFile;
        $defaults = $variants['defaults'] ?? [];
        $props = array_merge($defaults, $props);
    }

    ob_start();
    require $fullPath;
    return ob_get_clean();
}
```

- [ ] **Step 4: Create `storybook-server/index.php`**

The router. Handles both `@storybook/server` format (`?id=atoms/button&variant=primary`) and manual testing (`?component=atoms/button&variant=primary`).

```php
<?php
declare(strict_types=1);

// Allow Storybook (port 6006) to fetch from this server (port 6007)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

// Parse the component path — @storybook/server sends "id", manual testing uses "component"
$component = $_GET['id'] ?? $_GET['component'] ?? null;

if ($component === null) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing ?id= or ?component= parameter']);
    exit;
}

// Sanitize: only allow alphanumeric, hyphens, underscores, slashes
if (!preg_match('#^[a-zA-Z0-9/_-]+$#', $component)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid component path']);
    exit;
}

// All remaining query params become $props
$props = $_GET;
unset($props['id'], $props['component']);

// Render and output
header('Content-Type: text/html; charset=UTF-8');
echo renderComponent($component, $props);
```

- [ ] **Step 5: Test the PHP server starts without errors**

```bash
php -S localhost:6007 storybook-server/index.php &
SERVER_PID=$!
sleep 1
curl -s "http://localhost:6007/?component=nonexistent" | head -5
kill $SERVER_PID
```

Expected: Returns `<div style="color:red;padding:20px;">Component not found: nonexistent</div>`

- [ ] **Step 6: Commit**

```bash
git add storybook-server/
git commit -m "feat(storybook): add PHP story server with bootstrap, render, and router"
```

---

### Task 6: Configure Storybook

**Files:**
- Create: `.storybook/main.js`
- Create: `.storybook/preview.js`
- Create: `.storybook/preview-head.html`

- [ ] **Step 1: Create `.storybook/` directory**

```bash
mkdir -p .storybook
```

- [ ] **Step 2: Create `.storybook/main.js`**

```javascript
/** @type {import('@storybook/server').StorybookConfig} */
const config = {
  stories: [
    '../resources/components/**/*.stories.json',
  ],
  addons: [
    '@storybook/addon-essentials',
    '@storybook/addon-links',
  ],
  framework: {
    name: '@storybook/server',
    options: {},
  },
};

export default config;
```

- [ ] **Step 3: Create `.storybook/preview.js`**

Loads the compiled global CSS so all stories render with the project theme.

```javascript
import '../public/packages/inno_frontend/themes/innovationsbox/css/skins/default.css';

/** @type {import('@storybook/server').Preview} */
const preview = {
  parameters: {
    server: {
      url: 'http://localhost:6007',
    },
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
  },
};

export default preview;
```

- [ ] **Step 4: Create `.storybook/preview-head.html`**

Injects the Inter font-face declarations into the Storybook preview iframe. Uses relative paths that work in dev mode.

```html
<style>
  @font-face {
    font-family: 'Inter';
    font-stretch: 0% 100%;
    src: url('/packages/inno_frontend/themes/innovationsbox/fonts/Inter-VariableFont_opsz,wght.ttf') format('truetype');
    font-weight: 100 900;
    font-style: normal;
  }
  @font-face {
    font-family: 'Inter';
    font-stretch: 0% 100%;
    src: url('/packages/inno_frontend/themes/innovationsbox/fonts/Inter-Italic-VariableFont_opsz,wght.ttf') format('truetype');
    font-weight: 100 900;
    font-style: italic;
  }
</style>
```

- [ ] **Step 5: Commit**

```bash
git add .storybook/
git commit -m "feat(storybook): add Storybook config with server framework and theme CSS"
```

---

### Task 7: Create a smoke-test component to verify the full stack

**Files:**
- Create: `resources/components/atoms/button/button.php`
- Create: `resources/components/atoms/button/button.variants.php`
- Create: `resources/components/atoms/button/button.stories.json`
- Create: `resources/components/atoms/button/button.scss`

This is a single atom to prove the entire pipeline works end-to-end: tokens → generated SCSS → component partial → PHP server → Storybook. The button is the simplest visual component and was already designed in the spec.

- [ ] **Step 1: Create directory structure**

```bash
mkdir -p resources/components/atoms/button
```

- [ ] **Step 2: Create `resources/components/atoms/button/button.variants.php`**

```php
<?php
return [
    'variants' => ['primary', 'secondary'],
    'sizes' => ['default', 'small', 'large'],
    'defaults' => [
        'variant' => 'primary',
        'size' => 'default',
        'label' => 'Button',
        'href' => '#',
    ],
];
```

- [ ] **Step 3: Create `resources/components/atoms/button/button.php`**

```php
<?php
/**
 * Atom: Button
 *
 * @param array $props {
 *   @type string $variant  'primary' | 'secondary'
 *   @type string $size     'default' | 'small' | 'large'
 *   @type string $label    Button text
 *   @type string $href     Link target URL
 * }
 */

$variant = $props['variant'] ?? 'primary';
$size = $props['size'] ?? 'default';
$label = $props['label'] ?? 'Button';
$href = $props['href'] ?? '#';

$classes = 'button button--' . htmlspecialchars($variant);
if ($size !== 'default') {
    $classes .= ' button--' . htmlspecialchars($size);
}
?>
<a class="<?= $classes ?>" href="<?= htmlspecialchars($href) ?>">
    <?= htmlspecialchars($label) ?>
</a>
```

- [ ] **Step 4: Create `resources/components/atoms/button/button.scss`**

This extracts the button styles from `presets.scss` into the component. For now it's a reference file — the actual button styling still comes from the global CSS loaded in Storybook. Component-specific SCSS will be compiled separately in Plan 4 (webpack integration).

```scss
// Atom: Button
// Note: Button base styles currently live in global/presets.scss (:where(button, .button) block).
// This file will hold button-specific overrides and the component's variant classes.

.button {
  &--secondary {
    background-color: var(--color-secondary);

    &:hover {
      background-color: var(--color-brown);
    }
  }

  &--small {
    padding: 4px 12px;
    font-size: 14px;
  }

  &--large {
    padding: 12px 32px;
    font-size: 20px;
  }
}
```

- [ ] **Step 5: Create `resources/components/atoms/button/button.stories.json`**

```json
{
  "title": "Atoms/Button",
  "stories": [
    {
      "name": "Primary",
      "parameters": {
        "server": {
          "id": "atoms/button",
          "params": {
            "variant": "primary",
            "label": "Projektidee einreichen",
            "href": "#"
          }
        }
      }
    },
    {
      "name": "Secondary",
      "parameters": {
        "server": {
          "id": "atoms/button",
          "params": {
            "variant": "secondary",
            "label": "Mehr erfahren",
            "href": "#"
          }
        }
      }
    },
    {
      "name": "Small",
      "parameters": {
        "server": {
          "id": "atoms/button",
          "params": {
            "variant": "primary",
            "size": "small",
            "label": "Klein",
            "href": "#"
          }
        }
      }
    },
    {
      "name": "Large",
      "parameters": {
        "server": {
          "id": "atoms/button",
          "params": {
            "variant": "primary",
            "size": "large",
            "label": "Gross",
            "href": "#"
          }
        }
      }
    }
  ]
}
```

- [ ] **Step 6: Build CSS first (Storybook needs the compiled theme CSS)**

```bash
npm run tokens && npm run development
```

Expected: `default.css` compiled without errors.

- [ ] **Step 7: Start Storybook and verify the button renders**

```bash
npm run storybook
```

Expected: Storybook opens at `http://localhost:6006`, sidebar shows `Atoms > Button` with 4 stories (Primary, Secondary, Small, Large). Each story fetches HTML from the PHP server and renders a styled button with the Inter font, lime-green primary color, and rounded corners.

- [ ] **Step 8: Stop Storybook (Ctrl+C) and commit**

```bash
git add resources/components/atoms/button/
git commit -m "feat(storybook): add button atom as smoke-test component"
```

---

### Task 8: Add Storybook E2E smoke test

**Files:**
- Create: `tests/e2e/specs/storybook/smoke.spec.ts`

- [ ] **Step 1: Create the test directory**

```bash
mkdir -p tests/e2e/specs/storybook
```

- [ ] **Step 2: Create `tests/e2e/specs/storybook/smoke.spec.ts`**

Tests that Storybook starts and the button atom renders correctly from the PHP server.

```typescript
import { test, expect } from '@playwright/test';

const STORYBOOK_URL = 'http://localhost:6006';
const PHP_SERVER_URL = 'http://localhost:6007';

test.describe('Storybook smoke tests', () => {
  test('PHP story server responds via ?id= param (Storybook format)', async ({ request }) => {
    const response = await request.get(
      `${PHP_SERVER_URL}/?id=atoms/button&variant=primary&label=Test`
    );
    expect(response.status()).toBe(200);
    const html = await response.text();
    expect(html).toContain('class="button button--primary"');
    expect(html).toContain('Test');
  });

  test('PHP story server responds via ?component= param (manual format)', async ({ request }) => {
    const response = await request.get(
      `${PHP_SERVER_URL}/?component=atoms/button&variant=secondary&label=Manual`
    );
    expect(response.status()).toBe(200);
    const html = await response.text();
    expect(html).toContain('button--secondary');
  });

  test('PHP story server returns 404 HTML for missing component', async ({ request }) => {
    const response = await request.get(
      `${PHP_SERVER_URL}/?id=nonexistent/thing`
    );
    expect(response.status()).toBe(404);
    const html = await response.text();
    expect(html).toContain('Component not found');
  });

  test('PHP story server validates component path', async ({ request }) => {
    const response = await request.get(
      `${PHP_SERVER_URL}/?id=../../../etc/passwd`
    );
    expect(response.status()).toBe(400);
  });

  test('button defaults from variants.php are applied when no props given', async ({ request }) => {
    const response = await request.get(
      `${PHP_SERVER_URL}/?id=atoms/button`
    );
    expect(response.status()).toBe(200);
    const html = await response.text();
    expect(html).toContain('class="button button--primary"');
    expect(html).toContain('Button');
  });
});
```

- [ ] **Step 3: Run the smoke test (requires PHP server running)**

```bash
php -S localhost:6007 storybook-server/index.php &
SERVER_PID=$!
sleep 1
npx playwright test tests/e2e/specs/storybook/smoke.spec.ts --config=tests/e2e/playwright.config.ts
kill $SERVER_PID
```

Expected: 5 tests passed.

- [ ] **Step 4: Commit**

```bash
git add tests/e2e/specs/storybook/smoke.spec.ts
git commit -m "test(storybook): add smoke tests for PHP story server and button component"
```

---

### Task 9: Update CLAUDE.md with Storybook commands

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Add Storybook section to Commands**

Find the `### Testing` section in CLAUDE.md and add a new `### Storybook` section right before it:

```markdown
### Storybook
```bash
npm run tokens           # Regenerate SCSS from tokens.json
npm run storybook        # Start Storybook (port 6006) + PHP story server (port 6007)
npm run storybook:build  # Build static Storybook site
```
Component library using Atomic Design (atoms → molecules → organisms → templates → pages).
Components are PHP partials rendered by a lightweight PHP server. Design tokens live in `resources/tokens/tokens.json`.
```

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: add Storybook commands to CLAUDE.md"
```
