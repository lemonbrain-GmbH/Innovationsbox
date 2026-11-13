# Design Token Foundation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Evolve `tokens.json` into a complete semantic token system with color scales, typography, spacing, radius, shadows, z-index, motion, and WCAG-AA-verified semantic aliases, while preserving existing component styles via legacy aliases.

**Architecture:** Update `tokens.json` with nested structure (brand scales, neutral, status, semantic aliases, etc.). Rewrite `scripts/build-tokens.js` to emit flat CSS custom properties plus legacy aliases. Create token documentation Storybook stories. No component changes.

**Tech Stack:** JSON (tokens), Node.js (build script), SCSS (output), PHP (Storybook partials)

---

### Task 1: Rewrite tokens.json with new structure

**Files:**
- Modify: `resources/tokens/tokens.json`

- [ ] **Step 1: Replace tokens.json contents**

Replace the entire contents of `resources/tokens/tokens.json` with:

```json
{
  "color": {
    "brand": {
      "primary": {
        "50": "#F7FAEB", "100": "#ECF3D1", "200": "#DCE8A8", "300": "#CCDE80", "400": "#BFD566",
        "500": "#B7CD57", "600": "#9DB247", "700": "#7D8F35", "800": "#5E6B25", "900": "#3F4718"
      },
      "secondary": {
        "50": "#F5F1ED", "100": "#E9E1D8", "200": "#D1BFAD", "300": "#B89D82", "400": "#A08458",
        "500": "#89705B", "600": "#6F5C4B", "700": "#55483A", "800": "#3C3329", "900": "#231F19"
      },
      "accent": {
        "50": "#F3F4F1", "100": "#E4E6E0", "200": "#C9CDC1", "300": "#AEB4A3", "400": "#929B85",
        "500": "#7B876F", "600": "#636D5A", "700": "#4B5345", "800": "#353B32", "900": "#1F231E"
      }
    },
    "neutral": {
      "50": "#FAFAF9", "100": "#F4F4F3", "200": "#E7E7E4", "300": "#D1D1CC", "400": "#A5A5A0",
      "500": "#71716C", "600": "#52524E", "700": "#3F3F3C", "800": "#2A2A28", "900": "#181816"
    },
    "status": {
      "success": {
        "50": "#F5FAF7", "100": "#EAF5EE", "200": "#C6F5D8", "300": "#9FEEBC", "400": "#67E595",
        "500": "#26D968", "600": "#20B757", "700": "#199044", "800": "#156031", "900": "#0B3219"
      },
      "warning": {
        "50": "#FBF8F4", "100": "#F7F2E9", "200": "#FCE6BF", "300": "#FBD493", "400": "#F8BB54",
        "500": "#F59E0A", "600": "#CE8408", "700": "#A26807", "800": "#6B470A", "900": "#382505"
      },
      "error": {
        "50": "#FBF4F4", "100": "#F6E9E9", "200": "#FAC2C2", "300": "#F69898", "400": "#F15B5B",
        "500": "#EB1414", "600": "#C51111", "700": "#9B0D0D", "800": "#670E0E", "900": "#360707"
      },
      "info": {
        "50": "#F4F7FB", "100": "#E9EEF7", "200": "#C0D7FC", "300": "#94BBFA", "400": "#5492F7",
        "500": "#0B64F4", "600": "#0954CD", "700": "#0742A1", "800": "#0A2F6B", "900": "#051938"
      }
    },
    "white": "#FFFFFF",
    "overlay": "rgba(0, 0, 0, 0.4)"
  },

  "semantic": {
    "text": {
      "primary": "var(--color-neutral-900)",
      "secondary": "var(--color-neutral-700)",
      "muted": "var(--color-neutral-500)",
      "disabled": "var(--color-neutral-400)",
      "inverse": "var(--color-white)",
      "link": "var(--color-brand-primary-700)",
      "link-hover": "var(--color-brand-accent-500)",
      "error": "var(--color-status-error-600)",
      "success": "var(--color-status-success-700)",
      "warning": "var(--color-status-warning-700)"
    },
    "bg": {
      "page": "var(--color-neutral-50)",
      "surface": "var(--color-white)",
      "subtle": "var(--color-neutral-100)",
      "emphasis": "var(--color-neutral-900)",
      "inverse": "var(--color-neutral-900)",
      "overlay": "var(--color-overlay)"
    },
    "border": {
      "subtle": "var(--color-neutral-200)",
      "default": "var(--color-neutral-300)",
      "strong": "var(--color-neutral-500)",
      "focus": "var(--color-brand-primary-500)"
    },
    "action": {
      "primary": "var(--color-brand-primary-500)",
      "primary-hover": "var(--color-brand-primary-600)",
      "primary-text": "var(--color-brand-primary-900)",
      "secondary": "var(--color-brand-secondary-500)",
      "secondary-hover": "var(--color-brand-secondary-600)",
      "secondary-text": "var(--color-white)",
      "danger": "var(--color-status-error-600)",
      "danger-hover": "var(--color-status-error-700)",
      "danger-text": "var(--color-white)"
    }
  },

  "font": {
    "family": {
      "heading": "'Inter', sans-serif",
      "text": "'Inter', sans-serif"
    },
    "weight": {
      "light": 300,
      "regular": 400,
      "medium": 500,
      "semibold": 600,
      "bold": 700
    },
    "tracking": {
      "tight": "-0.02em",
      "normal": "0",
      "wide": "0.05em"
    }
  },

  "textScale": {
    "xs":   { "sizes": [12, 12.5, 13, 14],       "lineHeights": [16, 16, 17, 18] },
    "sm":   { "sizes": [13, 14, 15, 16],         "lineHeights": [18, 20, 21, 22] },
    "base": { "sizes": [14.5, 16.5, 18, 20],     "lineHeights": [20, 23, 26, 28] },
    "md":   { "sizes": [16, 18, 20, 22],         "lineHeights": [22, 26, 28, 30] },
    "lg":   { "sizes": [18, 20, 22, 24],         "lineHeights": [24, 27, 30, 33] },
    "xl":   { "sizes": [20, 24, 27, 30],         "lineHeights": [26, 30, 34, 38] },
    "2xl":  { "sizes": [24, 30, 35, 40],         "lineHeights": [30, 36, 42, 48] },
    "3xl":  { "sizes": [32, 40, 44, 48],         "lineHeights": [38, 48, 52, 58] },
    "4xl":  { "sizes": [40, 48, 56, 64],         "lineHeights": [48, 56, 64, 72] }
  },

  "typography": {
    "h1": { "sizes": [22, 32, 48, 64], "lineHeights": [26, 38, 56, 78], "weight": 700, "color": "secondary", "family": "heading" },
    "h2": { "sizes": [20, 24, 30, 40], "lineHeights": [26, 30, 36, 48], "weight": 600, "color": "black", "family": "heading" },
    "h3": { "sizes": [18, 20, 24, 30], "lineHeights": [24, 26, 32, 38], "weight": 600, "color": "black", "family": "heading" },
    "h4": { "sizes": [16, 18, 20, 24], "lineHeights": [22, 24, 28, 32], "weight": 600, "color": "black", "family": "heading" },
    "h5": { "sizes": [14.5, 16.5, 18, 20], "lineHeights": [20, 22, 24, 28], "weight": 600, "color": "black", "family": "heading" },
    "h6": { "sizes": [14.5, 16.5, 18, 20], "lineHeights": [20, 22, 24, 28], "weight": 600, "color": "black", "family": "heading" },
    "body": { "sizes": [14.5, 16.5, 20, 24], "lineHeights": [20, 22, 28, 32], "weight": 300, "color": "black", "family": "text" },
    "lead": { "sizes": [18, 20, 24, 30], "lineHeights": [24, 26, 32, 38], "weight": 400, "color": "black", "family": "text" },
    "small": { "sizes": [12, 13, 14, 15], "lineHeights": [16, 18, 20, 22], "weight": 600, "color": "black", "family": "text" },
    "link": { "sizes": [14.5, 16.5, 20, 24], "lineHeights": [20, 22, 28, 32], "weight": 600, "color": "primary", "hoverColor": "green", "family": "text" },
    "list": { "sizes": [14.5, 16.5, 20, 24], "lineHeights": [20, 22, 28, 32], "weight": 400, "color": "black", "family": "text" },
    "button": { "sizes": [14.5, 16.5, 18, 20], "lineHeights": [20, 22, 24, 28], "weight": 600, "color": "white", "family": "text" }
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

  "space": {
    "0": 0,
    "1": 4,
    "2": 8,
    "3": 12,
    "4": 16,
    "5": 20,
    "6": 24,
    "8": 32,
    "10": 40,
    "12": 48,
    "16": 64,
    "20": 80,
    "24": 96,
    "32": 128
  },

  "spacing": {
    "base": [24, 32],
    "block": [30, 60],
    "blockSpaceMax": 60,
    "blockSpaceMin": 30
  },

  "radius": {
    "none": 0,
    "sm": 4,
    "md": 10,
    "lg": 20,
    "xl": 32,
    "full": 9999,
    "default": 20,
    "small": 10
  },

  "shadow": {
    "sm": "0 1px 2px rgba(0, 0, 0, 0.05)",
    "md": "0 3px 10px rgba(0, 0, 0, 0.1)",
    "lg": "0 10px 25px rgba(0, 0, 0, 0.1)",
    "xl": "0 20px 40px rgba(0, 0, 0, 0.15)",
    "default": "#0000001A 0 3px 10px"
  },

  "z": {
    "dropdown": 1000,
    "sticky": 1100,
    "banner": 1200,
    "overlay": 1300,
    "modal": 1400,
    "popover": 1500,
    "tooltip": 1600,
    "toast": 1700
  },

  "motion": {
    "duration": {
      "fast": "150ms",
      "normal": "300ms",
      "slow": "500ms"
    },
    "easing": {
      "standard": "cubic-bezier(0.2, 0, 0, 1)",
      "emphasized": "cubic-bezier(0.3, 0, 0.8, 0.15)"
    }
  },

  "transition": {
    "default": "0.3s"
  }
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/tokens/tokens.json
git commit -m "feat(tokens): expand tokens.json with color scales and semantic aliases"
```

---

### Task 2: Rewrite scripts/build-tokens.js to emit new CSS variables

**Files:**
- Modify: `scripts/build-tokens.js`

- [ ] **Step 1: Rewrite the build script**

Replace the entire contents of `scripts/build-tokens.js` with:

```js
#!/usr/bin/env node
const fs = require('fs');
const path = require('path');

const TOKENS_PATH = path.resolve(__dirname, '../resources/tokens/tokens.json');
const OUT_DIR = path.resolve(__dirname, '../resources/css/global');

const tokens = JSON.parse(fs.readFileSync(TOKENS_PATH, 'utf-8'));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function kebab(str) {
  return String(str).replace(/[A-Z]/g, m => '-' + m.toLowerCase()).replace(/^-/, '');
}

function emitScaleVars(lines, prefix, scale) {
  for (const [step, value] of Object.entries(scale)) {
    lines.push(`  --${prefix}-${step}: ${value};`);
  }
}

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

  // Screen sizes (consumed by functions.scss media queries)
  lines.push('  // screen sizes');
  lines.push('  --upper-bound: #{f.$upperBound-factor}px;');
  lines.push('  --lower-bound: #{f.$lowerBound-factor}px;');
  lines.push('  --upper-bound-mobile: #{f.$upperBoundMobile-factor}px;');
  lines.push('');

  // Motion
  lines.push('  // motion');
  lines.push(`  --duration-fast: ${tokens.motion.duration.fast};`);
  lines.push(`  --duration-normal: ${tokens.motion.duration.normal};`);
  lines.push(`  --duration-slow: ${tokens.motion.duration.slow};`);
  lines.push(`  --ease-standard: ${tokens.motion.easing.standard};`);
  lines.push(`  --ease-emphasized: ${tokens.motion.easing.emphasized};`);
  lines.push('');

  // Legacy general values (kept for backwards compat)
  lines.push('  // legacy aliases');
  lines.push(`  --transition-time: var(--duration-normal);`);
  lines.push('  --lighten-amount: 20%;');
  lines.push('  --darken-amount: 20%;');
  lines.push(`  --block-space-max-factor: ${tokens.spacing.blockSpaceMax};`);
  lines.push(`  --block-space-min-factor: ${tokens.spacing.blockSpaceMin};`);
  lines.push('');

  // Radius
  lines.push('  // radius');
  for (const step of ['none', 'sm', 'md', 'lg', 'xl', 'full']) {
    const val = tokens.radius[step];
    lines.push(`  --radius-${step}: ${val === 9999 ? '9999px' : val + 'px'};`);
  }
  // Legacy
  lines.push('  --border-radius: var(--radius-lg);');
  lines.push('  --border-radius-small: var(--radius-md);');
  lines.push('');
  lines.push('  @media screen and (max-width: #{f.$lowerBound-factor}px) {');
  lines.push('    --border-radius: var(--border-radius-small);');
  lines.push('  }');
  lines.push('');

  // Shadows
  lines.push('  // shadows');
  for (const step of ['sm', 'md', 'lg', 'xl']) {
    lines.push(`  --shadow-${step}: ${tokens.shadow[step]};`);
  }
  lines.push('  --box-shadow: var(--shadow-md);');
  lines.push('');

  // Z-index
  lines.push('  // z-index');
  for (const [name, value] of Object.entries(tokens.z)) {
    lines.push(`  --z-${name}: ${value};`);
  }
  lines.push('');

  // Spacing scale
  lines.push('  // spacing scale (4px base)');
  for (const [step, value] of Object.entries(tokens.space)) {
    lines.push(`  --space-${step}: ${value}px;`);
  }
  lines.push('');

  // Brand color scales
  lines.push('  // brand color scales');
  emitScaleVars(lines, 'color-brand-primary', tokens.color.brand.primary);
  lines.push('');
  emitScaleVars(lines, 'color-brand-secondary', tokens.color.brand.secondary);
  lines.push('');
  emitScaleVars(lines, 'color-brand-accent', tokens.color.brand.accent);
  lines.push('');

  // Neutral scale
  lines.push('  // neutral scale');
  emitScaleVars(lines, 'color-neutral', tokens.color.neutral);
  lines.push('');

  // Status scales
  lines.push('  // status color scales');
  for (const status of ['success', 'warning', 'error', 'info']) {
    emitScaleVars(lines, `color-status-${status}`, tokens.color.status[status]);
    lines.push('');
  }

  // Base colors
  lines.push('  // base colors');
  lines.push(`  --color-white: ${tokens.color.white};`);
  lines.push(`  --color-overlay: ${tokens.color.overlay};`);
  lines.push('');

  // Semantic aliases
  lines.push('  // semantic aliases (dark-mode-swap-ready)');
  for (const group of ['text', 'bg', 'border', 'action']) {
    for (const [name, value] of Object.entries(tokens.semantic[group])) {
      lines.push(`  --color-${group}-${name}: ${value};`);
    }
  }
  lines.push('');

  // Status semantic aliases (bg + border + icon for each status)
  lines.push('  // status semantic aliases');
  for (const status of ['success', 'warning', 'error', 'info']) {
    lines.push(`  --color-status-${status}-bg: var(--color-status-${status}-50);`);
    lines.push(`  --color-status-${status}-border: var(--color-status-${status}-200);`);
    lines.push(`  --color-status-${status}-icon: var(--color-status-${status}-600);`);
  }
  lines.push('');

  // Legacy color aliases (keeps existing components working)
  lines.push('  // legacy color aliases');
  lines.push('  --color-primary: var(--color-brand-primary-500);');
  lines.push('  --color-secondary: var(--color-brand-secondary-500);');
  lines.push('  --color-green: var(--color-brand-accent-500);');
  lines.push('  --color-brown: var(--color-brand-secondary-500);');
  lines.push('  --color-lightgreen: var(--color-brand-primary-500);');
  lines.push('  --color-black: var(--color-neutral-900);');
  lines.push('  --color-light-overlay: rgba(255, 255, 255, 0.5);');
  lines.push('');

  // Font families
  lines.push('  // font');
  lines.push(`  --heading-font-family: ${tokens.font.family.heading};`);
  lines.push(`  --text-font-family: ${tokens.font.family.text};`);
  for (const [name, value] of Object.entries(tokens.font.weight)) {
    lines.push(`  --font-weight-${name}: ${value};`);
  }
  for (const [name, value] of Object.entries(tokens.font.tracking)) {
    lines.push(`  --tracking-${name}: ${value};`);
  }
  lines.push('');

  // Legacy font-weight aliases
  lines.push('  --font-weight-normal: var(--font-weight-regular);');
  lines.push('');

  // Typography CSS variables (per-element font-family, font-weight, color)
  lines.push('  // typography (legacy per-element vars)');
  const typoMap = {
    h1: 'h1', h2: 'h2', h3: 'h3', h4: 'h4', h5: 'h5', h6: 'h6',
    body: 'p', lead: 'lead', small: 'small', link: 'a', list: 'list',
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
    if (tokenKey === 'body') {
      lines.push(`$${scssPrefix}-font-sizes: $base-font-sizes;`);
      lines.push(`$${scssPrefix}-line-heights: $base-line-heights;`);
    } else {
      lines.push(`$${scssPrefix}-font-sizes: (${t.sizes.join(', ')});`);
      lines.push(`$${scssPrefix}-line-heights: (${t.lineHeights.join(', ')});`);
    }
    lines.push('');
  }

  // New numeric text scale
  lines.push('// numeric text scale');
  for (const [name, t] of Object.entries(tokens.textScale)) {
    lines.push(`$text-${name}-font-sizes: (${t.sizes.join(', ')});`);
    lines.push(`$text-${name}-line-heights: (${t.lineHeights.join(', ')});`);
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

- [ ] **Step 2: Run the build script**

```bash
npm run tokens
```

Expected output:
```
Tokens built:
  → resources/css/global/_generated-variables.scss
  → resources/css/global/_generated-arrays.scss
```

- [ ] **Step 3: Build CSS and verify no errors**

```bash
npm run development
```

Expected: `webpack compiled successfully`

- [ ] **Step 4: Verify existing components still render correctly**

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:6007/atoms/button
```

Expected: `200` (after starting the storybook-server in another terminal: `php -S localhost:6007 storybook-server/index.php`)

- [ ] **Step 5: Verify generated CSS has the new tokens**

```bash
grep -c "color-brand-primary-500" resources/css/global/_generated-variables.scss
grep -c "color-text-primary" resources/css/global/_generated-variables.scss
grep -c "space-4" resources/css/global/_generated-variables.scss
grep -c "z-modal" resources/css/global/_generated-variables.scss
```

Expected: each command returns at least `1`.

- [ ] **Step 6: Verify legacy aliases present**

```bash
grep -c "\-\-color-primary: var" resources/css/global/_generated-variables.scss
grep -c "\-\-border-radius: var" resources/css/global/_generated-variables.scss
grep -c "\-\-box-shadow: var" resources/css/global/_generated-variables.scss
grep -c "\-\-transition-time: var" resources/css/global/_generated-variables.scss
```

Expected: each returns `1`.

- [ ] **Step 7: Commit**

```bash
git add scripts/build-tokens.js resources/css/global/_generated-variables.scss resources/css/global/_generated-arrays.scss
git commit -m "feat(tokens): rewrite build script for new token structure"
```

---

### Task 3: Color swatch documentation stories (brand + neutral)

**Files:**
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-brand.php`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-brand.variants.php`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-brand.stories.json`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-neutral.php`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-neutral.variants.php`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-neutral.stories.json`

- [ ] **Step 1: Create the colors-brand partial**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-brand.php`:

```php
<?php
$scalesJson = $props['scales'] ?? '[]';
$scales = json_decode($scalesJson, true) ?: [];
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
<?php foreach ($scales as $scale): ?>
    <h3 style="margin: 24px 0 8px;"><?= htmlspecialchars($scale['name']) ?></h3>
    <p style="color: #52524E; margin: 0 0 12px;"><?= htmlspecialchars($scale['description'] ?? '') ?></p>
    <div style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 4px;">
        <?php foreach ($scale['steps'] as $step => $hex):
            $r = hexdec(substr($hex, 1, 2));
            $g = hexdec(substr($hex, 3, 2));
            $b = hexdec(substr($hex, 5, 2));
            $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
            $textColor = $lum < 0.55 ? '#fff' : '#181816';
        ?>
            <div style="background: <?= $hex ?>; color: <?= $textColor ?>; padding: 20px 12px 12px; border-radius: 8px; min-height: 90px; display: flex; flex-direction: column; justify-content: space-between; font-family: ui-monospace, monospace; font-size: 12px;">
                <span style="font-weight: 700; font-size: 14px;"><?= $step ?></span>
                <span style="opacity: 0.85;"><?= $hex ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</div>
```

- [ ] **Step 2: Create the colors-brand variants**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-brand.variants.php`:

```php
<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../resources/tokens/tokens.json'), true);

return [
    'defaults' => [
        'scales' => json_encode([
            [
                'name' => 'Primary',
                'description' => 'Main brand color — actions, links, emphasis.',
                'steps' => $tokens['color']['brand']['primary'],
            ],
            [
                'name' => 'Secondary',
                'description' => 'Secondary brand color — supporting elements.',
                'steps' => $tokens['color']['brand']['secondary'],
            ],
            [
                'name' => 'Accent',
                'description' => 'Accent color — hover states, highlights.',
                'steps' => $tokens['color']['brand']['accent'],
            ],
        ]),
    ],
];
```

- [ ] **Step 3: Create the colors-brand story**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-brand.stories.json`:

```json
{
  "title": "Tokens/Colors/Brand",
  "stories": [
    { "name": "Brand Scales", "parameters": { "server": { "id": "atoms/_tokens/colors-brand" } } }
  ]
}
```

- [ ] **Step 4: Create the colors-neutral partial**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-neutral.php`:

```php
<?php
$scalesJson = $props['scales'] ?? '[]';
$scales = json_decode($scalesJson, true) ?: [];
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
<?php foreach ($scales as $scale): ?>
    <h3 style="margin: 24px 0 8px;"><?= htmlspecialchars($scale['name']) ?></h3>
    <p style="color: #52524E; margin: 0 0 12px;"><?= htmlspecialchars($scale['description'] ?? '') ?></p>
    <div style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 4px;">
        <?php foreach ($scale['steps'] as $step => $hex):
            $r = hexdec(substr($hex, 1, 2));
            $g = hexdec(substr($hex, 3, 2));
            $b = hexdec(substr($hex, 5, 2));
            $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
            $textColor = $lum < 0.55 ? '#fff' : '#181816';
        ?>
            <div style="background: <?= $hex ?>; color: <?= $textColor ?>; padding: 20px 12px 12px; border-radius: 8px; min-height: 90px; display: flex; flex-direction: column; justify-content: space-between; font-family: ui-monospace, monospace; font-size: 12px;">
                <span style="font-weight: 700; font-size: 14px;"><?= $step ?></span>
                <span style="opacity: 0.85;"><?= $hex ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</div>
```

- [ ] **Step 5: Create the colors-neutral variants**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-neutral.variants.php`:

```php
<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../resources/tokens/tokens.json'), true);

return [
    'defaults' => [
        'scales' => json_encode([
            [
                'name' => 'Neutral',
                'description' => 'Warm gray — page backgrounds, text, borders.',
                'steps' => $tokens['color']['neutral'],
            ],
        ]),
    ],
];
```

- [ ] **Step 6: Create the colors-neutral story**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-neutral.stories.json`:

```json
{
  "title": "Tokens/Colors/Neutral",
  "stories": [
    { "name": "Neutral Scale", "parameters": { "server": { "id": "atoms/_tokens/colors-neutral" } } }
  ]
}
```

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/_tokens/
git commit -m "feat(tokens): add brand and neutral color swatch stories"
```

---

### Task 4: Color swatch documentation stories (status + semantic)

**Files:**
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-status.php`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-status.variants.php`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-status.stories.json`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-semantic.php`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-semantic.variants.php`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/colors-semantic.stories.json`

- [ ] **Step 1: Create colors-status.php**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-status.php`:

```php
<?php
$scalesJson = $props['scales'] ?? '[]';
$scales = json_decode($scalesJson, true) ?: [];
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
<?php foreach ($scales as $scale): ?>
    <h3 style="margin: 24px 0 8px;"><?= htmlspecialchars($scale['name']) ?></h3>
    <p style="color: #52524E; margin: 0 0 12px;"><?= htmlspecialchars($scale['description'] ?? '') ?></p>
    <div style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 4px;">
        <?php foreach ($scale['steps'] as $step => $hex):
            $r = hexdec(substr($hex, 1, 2));
            $g = hexdec(substr($hex, 3, 2));
            $b = hexdec(substr($hex, 5, 2));
            $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
            $textColor = $lum < 0.55 ? '#fff' : '#181816';
        ?>
            <div style="background: <?= $hex ?>; color: <?= $textColor ?>; padding: 20px 12px 12px; border-radius: 8px; min-height: 90px; display: flex; flex-direction: column; justify-content: space-between; font-family: ui-monospace, monospace; font-size: 12px;">
                <span style="font-weight: 700; font-size: 14px;"><?= $step ?></span>
                <span style="opacity: 0.85;"><?= $hex ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</div>
```

- [ ] **Step 2: Create colors-status variants**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-status.variants.php`:

```php
<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../resources/tokens/tokens.json'), true);

return [
    'defaults' => [
        'scales' => json_encode([
            ['name' => 'Success', 'description' => 'Confirmations, successful actions.', 'steps' => $tokens['color']['status']['success']],
            ['name' => 'Warning', 'description' => 'Warnings, pending states.', 'steps' => $tokens['color']['status']['warning']],
            ['name' => 'Error', 'description' => 'Errors, destructive actions.', 'steps' => $tokens['color']['status']['error']],
            ['name' => 'Info', 'description' => 'Informational messages.', 'steps' => $tokens['color']['status']['info']],
        ]),
    ],
];
```

- [ ] **Step 3: Create colors-status story**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-status.stories.json`:

```json
{
  "title": "Tokens/Colors/Status",
  "stories": [
    { "name": "Status Scales", "parameters": { "server": { "id": "atoms/_tokens/colors-status" } } }
  ]
}
```

- [ ] **Step 4: Create colors-semantic.php**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-semantic.php`:

```php
<?php
$groupsJson = $props['groups'] ?? '[]';
$groups = json_decode($groupsJson, true) ?: [];
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
<?php foreach ($groups as $group): ?>
    <h3 style="margin: 24px 0 8px;"><?= htmlspecialchars($group['name']) ?></h3>
    <p style="color: #52524E; margin: 0 0 12px;"><?= htmlspecialchars($group['description']) ?></p>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px;">
        <?php foreach ($group['tokens'] as $token): ?>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid #E7E7E4; border-radius: 8px;">
                <div style="width: 40px; height: 40px; border-radius: 4px; background: var(--<?= htmlspecialchars($token['var']) ?>); border: 1px solid #D1D1CC; flex-shrink: 0;"></div>
                <div style="font-family: ui-monospace, monospace; font-size: 11px; line-height: 1.4;">
                    <div style="font-weight: 700; color: #181816;"><?= htmlspecialchars($token['var']) ?></div>
                    <div style="color: #71716C; margin-top: 2px;"><?= htmlspecialchars($token['desc']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</div>
```

- [ ] **Step 5: Create colors-semantic variants**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-semantic.variants.php`:

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'groups' => json_encode([
            [
                'name' => 'Text',
                'description' => 'Text color tokens — use these for text elements.',
                'tokens' => [
                    ['var' => 'color-text-primary', 'desc' => 'Primary text (headings, body)'],
                    ['var' => 'color-text-secondary', 'desc' => 'Muted body text'],
                    ['var' => 'color-text-muted', 'desc' => 'Captions, hints'],
                    ['var' => 'color-text-disabled', 'desc' => 'Disabled text'],
                    ['var' => 'color-text-inverse', 'desc' => 'Text on dark backgrounds'],
                    ['var' => 'color-text-link', 'desc' => 'Links'],
                    ['var' => 'color-text-link-hover', 'desc' => 'Link hover state'],
                    ['var' => 'color-text-error', 'desc' => 'Error text'],
                    ['var' => 'color-text-success', 'desc' => 'Success text'],
                    ['var' => 'color-text-warning', 'desc' => 'Warning text'],
                ],
            ],
            [
                'name' => 'Background',
                'description' => 'Background color tokens.',
                'tokens' => [
                    ['var' => 'color-bg-page', 'desc' => 'Page background'],
                    ['var' => 'color-bg-surface', 'desc' => 'Cards, panels'],
                    ['var' => 'color-bg-subtle', 'desc' => 'Section backgrounds'],
                    ['var' => 'color-bg-emphasis', 'desc' => 'Contrast blocks'],
                    ['var' => 'color-bg-inverse', 'desc' => 'Dark panels'],
                    ['var' => 'color-bg-overlay', 'desc' => 'Modal backdrop'],
                ],
            ],
            [
                'name' => 'Border',
                'description' => 'Border color tokens.',
                'tokens' => [
                    ['var' => 'color-border-subtle', 'desc' => 'Dividers'],
                    ['var' => 'color-border-default', 'desc' => 'Input borders'],
                    ['var' => 'color-border-strong', 'desc' => 'Emphasized borders'],
                    ['var' => 'color-border-focus', 'desc' => 'Focus ring'],
                ],
            ],
            [
                'name' => 'Action',
                'description' => 'Interactive action tokens (buttons, CTAs).',
                'tokens' => [
                    ['var' => 'color-action-primary', 'desc' => 'Primary button background'],
                    ['var' => 'color-action-primary-hover', 'desc' => 'Primary button hover'],
                    ['var' => 'color-action-primary-text', 'desc' => 'Primary button text'],
                    ['var' => 'color-action-secondary', 'desc' => 'Secondary button background'],
                    ['var' => 'color-action-secondary-hover', 'desc' => 'Secondary button hover'],
                    ['var' => 'color-action-secondary-text', 'desc' => 'Secondary button text'],
                    ['var' => 'color-action-danger', 'desc' => 'Danger button (AA verified)'],
                    ['var' => 'color-action-danger-hover', 'desc' => 'Danger hover'],
                    ['var' => 'color-action-danger-text', 'desc' => 'Danger text'],
                ],
            ],
        ]),
    ],
];
```

- [ ] **Step 6: Create colors-semantic story**

Create `public/packages/inno_frontend/components/atoms/_tokens/colors-semantic.stories.json`:

```json
{
  "title": "Tokens/Colors/Semantic",
  "stories": [
    { "name": "Semantic Aliases", "parameters": { "server": { "id": "atoms/_tokens/colors-semantic" } } }
  ]
}
```

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/_tokens/
git commit -m "feat(tokens): add status and semantic color documentation stories"
```

---

### Task 5: Typography, spacing, radius, shadows, motion documentation stories

**Files:**
- Create: `public/packages/inno_frontend/components/atoms/_tokens/typography.php` + `.variants.php` + `.stories.json`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/spacing.php` + `.variants.php` + `.stories.json`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/radius.php` + `.variants.php` + `.stories.json`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/shadows.php` + `.variants.php` + `.stories.json`
- Create: `public/packages/inno_frontend/components/atoms/_tokens/motion.php` + `.variants.php` + `.stories.json`

- [ ] **Step 1: Create typography.php**

Create `public/packages/inno_frontend/components/atoms/_tokens/typography.php`:

```php
<?php
$samplesJson = $props['samples'] ?? '[]';
$samples = json_decode($samplesJson, true) ?: [];
$sampleText = $props['sampleText'] ?? 'Die Innovationsbox stellt digitale Räume zur Verfügung';
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
<?php foreach ($samples as $sample): ?>
    <div style="margin: 16px 0; padding: 16px; border-bottom: 1px solid #E7E7E4;">
        <div style="font-family: ui-monospace, monospace; font-size: 11px; color: #71716C; margin-bottom: 8px;">
            <?= htmlspecialchars($sample['token']) ?> — <?= htmlspecialchars($sample['sizes']) ?>
        </div>
        <div style="font-size: <?= htmlspecialchars($sample['fontSize']) ?>; line-height: <?= htmlspecialchars($sample['lineHeight']) ?>; font-weight: <?= htmlspecialchars($sample['weight'] ?? '400') ?>;">
            <?= htmlspecialchars($sampleText) ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
```

- [ ] **Step 2: Create typography variants**

Create `public/packages/inno_frontend/components/atoms/_tokens/typography.variants.php`:

```php
<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../resources/tokens/tokens.json'), true);

$samples = [];
foreach ($tokens['textScale'] as $name => $t) {
    $samples[] = [
        'token' => "text-{$name}",
        'sizes' => "{$t['sizes'][0]}px → {$t['sizes'][3]}px",
        'fontSize' => "{$t['sizes'][1]}px",
        'lineHeight' => "{$t['lineHeights'][1]}px",
        'weight' => '400',
    ];
}

return [
    'defaults' => [
        'samples' => json_encode($samples),
        'sampleText' => 'Die Innovationsbox stellt digitale Räume zur Verfügung',
    ],
];
```

- [ ] **Step 3: Create typography story**

Create `public/packages/inno_frontend/components/atoms/_tokens/typography.stories.json`:

```json
{
  "title": "Tokens/Typography",
  "argTypes": {
    "sampleText": { "control": "text" }
  },
  "stories": [
    { "name": "Text Scale", "parameters": { "server": { "id": "atoms/_tokens/typography" } } }
  ]
}
```

- [ ] **Step 4: Create spacing.php**

Create `public/packages/inno_frontend/components/atoms/_tokens/spacing.php`:

```php
<?php
$scaleJson = $props['scale'] ?? '[]';
$scale = json_decode($scaleJson, true) ?: [];
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
    <h3 style="margin: 0 0 16px;">Spacing Scale (4px base)</h3>
    <?php foreach ($scale as $item): ?>
        <div style="display: flex; align-items: center; gap: 16px; margin: 4px 0;">
            <div style="width: 120px; font-family: ui-monospace, monospace; font-size: 12px;">
                <strong>space-<?= htmlspecialchars($item['step']) ?></strong>
                <span style="color: #71716C;"> = <?= htmlspecialchars($item['px']) ?>px</span>
            </div>
            <div style="background: #B7CD57; height: 24px; width: <?= htmlspecialchars($item['px']) ?>px; border-radius: 4px;"></div>
        </div>
    <?php endforeach; ?>
</div>
```

- [ ] **Step 5: Create spacing variants**

Create `public/packages/inno_frontend/components/atoms/_tokens/spacing.variants.php`:

```php
<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../resources/tokens/tokens.json'), true);

$scale = [];
foreach ($tokens['space'] as $step => $px) {
    $scale[] = ['step' => $step, 'px' => $px];
}

return [
    'defaults' => [
        'scale' => json_encode($scale),
    ],
];
```

- [ ] **Step 6: Create spacing story**

Create `public/packages/inno_frontend/components/atoms/_tokens/spacing.stories.json`:

```json
{
  "title": "Tokens/Spacing",
  "stories": [
    { "name": "Spacing Scale", "parameters": { "server": { "id": "atoms/_tokens/spacing" } } }
  ]
}
```

- [ ] **Step 7: Create radius.php**

Create `public/packages/inno_frontend/components/atoms/_tokens/radius.php`:

```php
<?php
$samplesJson = $props['samples'] ?? '[]';
$samples = json_decode($samplesJson, true) ?: [];
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px; display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px;">
    <?php foreach ($samples as $sample): ?>
        <div style="text-align: center;">
            <div style="background: #B7CD57; width: 120px; height: 120px; margin: 0 auto; border-radius: <?= htmlspecialchars($sample['px']) ?>;"></div>
            <div style="font-family: ui-monospace, monospace; font-size: 12px; margin-top: 8px;">
                <strong>radius-<?= htmlspecialchars($sample['name']) ?></strong><br>
                <span style="color: #71716C;"><?= htmlspecialchars($sample['px']) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

- [ ] **Step 8: Create radius variants**

Create `public/packages/inno_frontend/components/atoms/_tokens/radius.variants.php`:

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'samples' => json_encode([
            ['name' => 'none', 'px' => '0'],
            ['name' => 'sm', 'px' => '4px'],
            ['name' => 'md', 'px' => '10px'],
            ['name' => 'lg', 'px' => '20px'],
            ['name' => 'xl', 'px' => '32px'],
            ['name' => 'full', 'px' => '9999px'],
        ]),
    ],
];
```

- [ ] **Step 9: Create radius story**

Create `public/packages/inno_frontend/components/atoms/_tokens/radius.stories.json`:

```json
{
  "title": "Tokens/Radius",
  "stories": [
    { "name": "Border Radius", "parameters": { "server": { "id": "atoms/_tokens/radius" } } }
  ]
}
```

- [ ] **Step 10: Create shadows.php**

Create `public/packages/inno_frontend/components/atoms/_tokens/shadows.php`:

```php
<?php
$samplesJson = $props['samples'] ?? '[]';
$samples = json_decode($samplesJson, true) ?: [];
?>
<div style="font-family: 'Inter', sans-serif; padding: 48px; background: #FAFAF9; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 32px;">
    <?php foreach ($samples as $sample): ?>
        <div style="text-align: center;">
            <div style="background: white; width: 140px; height: 140px; margin: 0 auto; border-radius: 10px; box-shadow: <?= htmlspecialchars($sample['shadow']) ?>;"></div>
            <div style="font-family: ui-monospace, monospace; font-size: 12px; margin-top: 12px;">
                <strong>shadow-<?= htmlspecialchars($sample['name']) ?></strong>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

- [ ] **Step 11: Create shadows variants**

Create `public/packages/inno_frontend/components/atoms/_tokens/shadows.variants.php`:

```php
<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../resources/tokens/tokens.json'), true);

$samples = [];
foreach (['sm', 'md', 'lg', 'xl'] as $name) {
    $samples[] = ['name' => $name, 'shadow' => $tokens['shadow'][$name]];
}

return [
    'defaults' => [
        'samples' => json_encode($samples),
    ],
];
```

- [ ] **Step 12: Create shadows story**

Create `public/packages/inno_frontend/components/atoms/_tokens/shadows.stories.json`:

```json
{
  "title": "Tokens/Shadows",
  "stories": [
    { "name": "Shadow Scale", "parameters": { "server": { "id": "atoms/_tokens/shadows" } } }
  ]
}
```

- [ ] **Step 13: Create motion.php**

Create `public/packages/inno_frontend/components/atoms/_tokens/motion.php`:

```php
<?php
$samplesJson = $props['samples'] ?? '[]';
$samples = json_decode($samplesJson, true) ?: [];
?>
<style>
@keyframes motionDemo { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(200px); } }
</style>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
    <?php foreach ($samples as $sample): ?>
        <div style="margin: 16px 0; padding: 16px; border: 1px solid #E7E7E4; border-radius: 8px;">
            <div style="font-family: ui-monospace, monospace; font-size: 12px; margin-bottom: 8px;">
                <strong><?= htmlspecialchars($sample['name']) ?></strong>
                <span style="color: #71716C;"> — <?= htmlspecialchars($sample['value']) ?></span>
            </div>
            <div style="background: #F4F4F3; padding: 16px; border-radius: 4px; overflow: hidden;">
                <div style="background: #B7CD57; width: 40px; height: 40px; border-radius: 4px; animation: motionDemo <?= htmlspecialchars($sample['duration']) ?> <?= htmlspecialchars($sample['easing']) ?> infinite;"></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

- [ ] **Step 14: Create motion variants**

Create `public/packages/inno_frontend/components/atoms/_tokens/motion.variants.php`:

```php
<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../resources/tokens/tokens.json'), true);

return [
    'defaults' => [
        'samples' => json_encode([
            ['name' => 'duration-fast + ease-standard', 'value' => $tokens['motion']['duration']['fast'] . ' / standard', 'duration' => $tokens['motion']['duration']['fast'], 'easing' => $tokens['motion']['easing']['standard']],
            ['name' => 'duration-normal + ease-standard', 'value' => $tokens['motion']['duration']['normal'] . ' / standard', 'duration' => $tokens['motion']['duration']['normal'], 'easing' => $tokens['motion']['easing']['standard']],
            ['name' => 'duration-slow + ease-standard', 'value' => $tokens['motion']['duration']['slow'] . ' / standard', 'duration' => $tokens['motion']['duration']['slow'], 'easing' => $tokens['motion']['easing']['standard']],
            ['name' => 'duration-normal + ease-emphasized', 'value' => $tokens['motion']['duration']['normal'] . ' / emphasized', 'duration' => $tokens['motion']['duration']['normal'], 'easing' => $tokens['motion']['easing']['emphasized']],
        ]),
    ],
];
```

- [ ] **Step 15: Create motion story**

Create `public/packages/inno_frontend/components/atoms/_tokens/motion.stories.json`:

```json
{
  "title": "Tokens/Motion",
  "stories": [
    { "name": "Duration & Easing", "parameters": { "server": { "id": "atoms/_tokens/motion" } } }
  ]
}
```

- [ ] **Step 16: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/_tokens/
git commit -m "feat(tokens): add typography, spacing, radius, shadows, motion stories"
```

---

### Task 6: Verify everything works

- [ ] **Step 1: Regenerate tokens and rebuild**

```bash
npm run tokens
npm run development
```

Expected: both commands succeed.

- [ ] **Step 2: Run tests**

```bash
nix develop --command bash -c "./vendor/bin/pest --testsuite=Unit"
```

Expected: all unit tests pass.

- [ ] **Step 3: Verify all existing components still render identically**

Start servers:
```bash
nix develop --command bash -c "php -S localhost:6007 storybook-server/index.php" &
```

Test key components:
```bash
curl -s -o /dev/null -w "atoms/button: %{http_code}\n" http://localhost:6007/atoms/button
curl -s -o /dev/null -w "organisms/navigation-drawer: %{http_code}\n" http://localhost:6007/organisms/navigation-drawer
curl -s -o /dev/null -w "organisms/footer: %{http_code}\n" http://localhost:6007/organisms/footer
curl -s -o /dev/null -w "pages/homepage: %{http_code}\n" http://localhost:6007/pages/homepage
```

Expected: all return `200`.

- [ ] **Step 4: Verify new token stories render**

```bash
curl -s -o /dev/null -w "tokens/colors-brand: %{http_code}\n" http://localhost:6007/atoms/_tokens/colors-brand
curl -s -o /dev/null -w "tokens/colors-neutral: %{http_code}\n" http://localhost:6007/atoms/_tokens/colors-neutral
curl -s -o /dev/null -w "tokens/colors-status: %{http_code}\n" http://localhost:6007/atoms/_tokens/colors-status
curl -s -o /dev/null -w "tokens/colors-semantic: %{http_code}\n" http://localhost:6007/atoms/_tokens/colors-semantic
curl -s -o /dev/null -w "tokens/typography: %{http_code}\n" http://localhost:6007/atoms/_tokens/typography
curl -s -o /dev/null -w "tokens/spacing: %{http_code}\n" http://localhost:6007/atoms/_tokens/spacing
curl -s -o /dev/null -w "tokens/radius: %{http_code}\n" http://localhost:6007/atoms/_tokens/radius
curl -s -o /dev/null -w "tokens/shadows: %{http_code}\n" http://localhost:6007/atoms/_tokens/shadows
curl -s -o /dev/null -w "tokens/motion: %{http_code}\n" http://localhost:6007/atoms/_tokens/motion
```

Expected: all return `200`.

- [ ] **Step 5: Commit any regenerated build artifacts**

```bash
git add -A
git status --short
# If only compiled files changed:
git commit -m "build: regenerate theme and block assets with new tokens" || echo "nothing to commit"
```
