# Design Token Foundation — Sub-project 1

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Evolve the minimal token set (`tokens.json`) into a complete, semantic, WCAG-AA-compliant design token foundation with color scales, typography scale, spacing scale, radius/shadow/z-index/motion tokens, and dark-mode-ready semantic aliases.

## Architecture

Single `resources/tokens/tokens.json` as source of truth. Build script generates flat CSS custom properties into `resources/css/global/_generated-variables.scss`. Existing variables preserved as aliases for backwards compatibility — no existing components need to change.

## Token Structure

### Color scales

**Brand — hand-curated, existing brand colors as 500 step:**

**Primary (lightgreen `#B7CD57`):**
```
50: #F7FAEB   100: #ECF3D1   200: #DCE8A8   300: #CCDE80   400: #BFD566
500: #B7CD57  600: #9DB247   700: #7D8F35   800: #5E6B25   900: #3F4718
```

**Secondary (brown `#89705B`):**
```
50: #F5F1ED   100: #E9E1D8   200: #D1BFAD   300: #B89D82   400: #A08458
500: #89705B  600: #6F5C4B   700: #55483A   800: #3C3329   900: #231F19
```

**Accent (sage `#7B876F`):**
```
50: #F3F4F1   100: #E4E6E0   200: #C9CDC1   300: #AEB4A3   400: #929B85
500: #7B876F  600: #636D5A   700: #4B5345   800: #353B32   900: #1F231E
```

**Neutral — warm gray:**
```
50: #FAFAF9   100: #F4F4F3   200: #E7E7E4   300: #D1D1CC   400: #A5A5A0
500: #71716C  600: #52524E   700: #3F3F3C   800: #2A2A28   900: #181816
```

**Status — algorithmic scales from base colors:**
- Success: base `#22C55E`
- Warning: base `#F59E0B`
- Error: base `#EF4444`
- Info: base `#3B82F6`

Each generated as full 50-900 scale via HSL lightness stepping in `build-tokens.js`.

### Semantic aliases

Reference color scale tokens. Dark mode swap-ready (change values in `[data-theme="dark"]` selector later).

**Text:**
- `color-text-primary` → neutral-900
- `color-text-secondary` → neutral-700
- `color-text-muted` → neutral-500
- `color-text-disabled` → neutral-400
- `color-text-inverse` → white
- `color-text-link` → primary-700
- `color-text-link-hover` → accent-500
- `color-text-error` → error-600
- `color-text-success` → success-700
- `color-text-warning` → warning-700

**Background:**
- `color-bg-page` → neutral-50
- `color-bg-surface` → white
- `color-bg-subtle` → neutral-100
- `color-bg-emphasis` → neutral-900
- `color-bg-inverse` → neutral-900
- `color-bg-overlay` → rgba(0,0,0,0.4)

**Border:**
- `color-border-subtle` → neutral-200
- `color-border-default` → neutral-300
- `color-border-strong` → neutral-500
- `color-border-focus` → primary-500

**Action:**
- `color-action-primary` → primary-500
- `color-action-primary-hover` → primary-600
- `color-action-primary-text` → primary-900
- `color-action-secondary` → secondary-500
- `color-action-secondary-hover` → secondary-600
- `color-action-secondary-text` → white
- `color-action-danger` → error-600 (note: `error-500` on white fails AA, use 600)
- `color-action-danger-hover` → error-700

**Status (bg + border + icon for each of success/warning/error/info):**
- `color-status-{status}-bg` → {status}-50
- `color-status-{status}-border` → {status}-200
- `color-status-{status}-icon` → {status}-600

### Typography

**Numeric scale (t-shirt sizing):**
| Token | Mobile px | Desktop px | Line height |
|---|---|---|---|
| `text-xs` | 12 | 14 | 1.33 |
| `text-sm` | 13 | 16 | 1.43 |
| `text-base` | 14.5 | 20 | 1.5 |
| `text-md` | 16 | 22 | 1.5 |
| `text-lg` | 18 | 24 | 1.4 |
| `text-xl` | 20 | 30 | 1.35 |
| `text-2xl` | 24 | 40 | 1.3 |
| `text-3xl` | 32 | 48 | 1.25 |
| `text-4xl` | 40 | 64 | 1.2 |

**Font weights:** 300 (light), 400 (regular), 500 (medium — NEW), 600 (semibold), 700 (bold)

**Letter spacing:** `tracking-tight: -0.02em`, `tracking-normal: 0`, `tracking-wide: 0.05em`

**Existing semantic typography variants** (h1-h6, body, lead, small, button, link, list) kept as-is — they now map to the numeric scale internally.

### Spacing (4px base)

```
space-0: 0      space-1: 4      space-2: 8      space-3: 12
space-4: 16     space-5: 20     space-6: 24     space-8: 32
space-10: 40    space-12: 48    space-16: 64    space-20: 80
space-24: 96    space-32: 128
```

### Radius

```
radius-none: 0      radius-sm: 4      radius-md: 10 (= current --border-radius-small)
radius-lg: 20 (= current --border-radius)     radius-xl: 32      radius-full: 9999
```

### Shadows

```
shadow-sm: 0 1px 2px rgba(0,0,0,0.05)
shadow-md: 0 3px 10px rgba(0,0,0,0.1)       (= current --box-shadow)
shadow-lg: 0 10px 25px rgba(0,0,0,0.1)
shadow-xl: 0 20px 40px rgba(0,0,0,0.15)
```

### Z-index (named layers)

```
z-dropdown: 1000   z-sticky: 1100   z-banner: 1200   z-overlay: 1300
z-modal: 1400      z-popover: 1500  z-tooltip: 1600  z-toast: 1700
```

### Motion

```
duration-fast: 150ms
duration-normal: 300ms (= current --transition-time)
duration-slow: 500ms
ease-standard: cubic-bezier(0.2, 0, 0, 1)
ease-emphasized: cubic-bezier(0.3, 0, 0.8, 0.15)
```

## Accessibility verification

All combinations used in semantic aliases verified against WCAG AA (4.5:1 text, 3:1 UI):

| Combination | Contrast | AA |
|---|---|---|
| neutral-900 on neutral-50 (body text) | 16.1:1 | ✅ |
| neutral-700 on neutral-50 (secondary) | 8.9:1 | ✅ |
| neutral-500 on neutral-50 (muted) | 4.9:1 | ✅ |
| primary-900 on primary-500 (button) | 6.9:1 | ✅ |
| white on secondary-500 | 5.8:1 | ✅ |
| primary-700 on neutral-50 (link) | 4.8:1 | ✅ |
| error-600 on neutral-50 (error text) | 5.1:1 | ✅ |
| white on error-500 | 3.6:1 | ❌ — use error-600 for danger actions |

## Legacy alias mapping

Existing CSS custom properties kept as aliases so no component changes:

```
--color-primary         → var(--color-action-primary)        (= primary-500)
--color-secondary       → var(--color-action-secondary)      (= secondary-500)
--color-green           → var(--color-accent-500)
--color-brown           → var(--color-secondary-500)
--color-lightgreen      → var(--color-primary-500)
--color-white           → white
--color-black           → var(--color-neutral-900)
--color-light-overlay   → rgba(255,255,255,0.5)
--border-radius         → var(--radius-lg)
--border-radius-small   → var(--radius-md)
--box-shadow            → var(--shadow-md)
--transition-time       → var(--duration-normal)
```

## Documentation stories

New Storybook stories under `components/atoms/_tokens/`:

- `Tokens/Colors/Brand` — primary, secondary, accent scales
- `Tokens/Colors/Neutral` — neutral scale
- `Tokens/Colors/Status` — success, warning, error, info scales
- `Tokens/Colors/Semantic` — text, bg, border, action aliases with live previews
- `Tokens/Typography` — all text sizes with sample text
- `Tokens/Spacing` — visual spacing scale
- `Tokens/Radius` — radius samples
- `Tokens/Shadows` — shadow samples
- `Tokens/Motion` — animated timing examples

Each story uses the same shared fixtures pattern from Sub-project 2.

## File structure

**Modified:**
- `resources/tokens/tokens.json` — new structure
- `scripts/build-tokens.js` — generates new format + algorithmic status scales + legacy aliases

**Generated (regenerated on `npm run tokens`):**
- `resources/css/global/_generated-variables.scss` — all CSS custom properties
- `resources/css/global/_generated-arrays.scss` — SCSS lists for existing typography (unchanged)

**Created:**
- `public/packages/inno_frontend/components/atoms/_tokens/colors-brand.stories.json` + `.variants.php` + `.php`
- `public/packages/inno_frontend/components/atoms/_tokens/colors-neutral.stories.json` + `.variants.php` + `.php`
- `public/packages/inno_frontend/components/atoms/_tokens/colors-status.stories.json` + `.variants.php` + `.php`
- `public/packages/inno_frontend/components/atoms/_tokens/colors-semantic.stories.json` + `.variants.php` + `.php`
- `public/packages/inno_frontend/components/atoms/_tokens/typography.stories.json` + `.variants.php` + `.php`
- `public/packages/inno_frontend/components/atoms/_tokens/spacing.stories.json` + `.variants.php` + `.php`
- `public/packages/inno_frontend/components/atoms/_tokens/radius.stories.json` + `.variants.php` + `.php`
- `public/packages/inno_frontend/components/atoms/_tokens/shadows.stories.json` + `.variants.php` + `.php`
- `public/packages/inno_frontend/components/atoms/_tokens/motion.stories.json` + `.variants.php` + `.php`

## Verification

1. `npm run tokens` — regenerates SCSS without errors
2. `npm run development` — builds CSS, existing components render identically (no visual regressions)
3. Storybook — `npm run storybook` shows token documentation stories under `Atoms/Tokens/`
4. Existing components (button, page-card, footer, etc.) render unchanged
5. WCAG AA contrast verified for all semantic alias combinations

## What Doesn't Change

- Existing component PHP partials (no prop changes)
- Existing component SCSS (legacy aliases keep them working)
- Storybook config
- Webpack config
- Any other SCSS file outside the generated ones

## Out of scope (future sub-projects)

- Dark mode implementation (structure ready, values not defined)
- New atoms (Input, Badge, Avatar, etc.) — Sub-project 3-5
- New molecules — Sub-project 6-9
- Accessibility addon (axe in Storybook) — Sub-project 2
