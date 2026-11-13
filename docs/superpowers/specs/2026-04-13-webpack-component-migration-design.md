# Webpack Component Library Migration

**Date:** 2026-04-13
**Status:** Approved
**Goal:** Make the Storybook component library (`resources/components/`) the single source of truth for block SCSS and TS by rewiring webpack.mix.js and removing duplicated files.

## Context

The project has two parallel sets of block styles and scripts:

- **Old:** `resources/css/blocks/` and `resources/js/blocks/` — compiled by webpack.mix.js auto-discovery into CMS block output paths
- **New:** `resources/components/` — the Storybook component library with near-identical SCSS/TS, but not wired into webpack

The component library should be the sole source. The old files are duplicates that will drift.

## Approach: Explicit Mapping in webpack.mix.js

Replace the auto-discovery loops for `resources/css/blocks/` and `resources/js/blocks/` with an explicit mapping table that compiles component SCSS/TS directly into CMS block output paths.

### webpack.mix.js Changes

Remove the `blockJsFiles` and `blockCssFiles` auto-discovery loops. Replace with:

```js
const blockComponents = [
  { src: 'resources/components/organisms/navigation-drawer/navigation-drawer.scss', out: 'blocks/autonav/templates/innovationsbox/view.css' },
  { src: 'resources/components/organisms/navigation-drawer/navigation-drawer.ts',   out: 'blocks/autonav/templates/innovationsbox/view.js' },
  { src: 'resources/components/organisms/hero-image/hero-image.scss',               out: 'blocks/hero_image/templates/innovationsbox/view.css' },
  { src: 'resources/components/organisms/content-block/content-block.scss',          out: 'blocks/content/templates/innovationsbox/view.css' },
  { src: 'resources/components/molecules/feature-button/feature-button.scss',        out: 'blocks/feature_link/templates/innovationsbox/view.css' },
  { src: 'resources/components/organisms/page-list-grid/page-list-grid.scss',        out: 'blocks/page_list/templates/innovationsbox/view.css' },
];
```

**Important:** Output filenames must be exactly `view.css` / `view.js` — Concrete CMS auto-discovers block template assets by these hardcoded names (`BlockViewTemplate.php`). Since the component source files have different names (e.g. `navigation-drawer.scss`), the full output path including filename must be specified.

Single-page auto-discovery stays unchanged. `main.scss` and `main.ts` entry points stay.

### main.scss Changes

Replace layout imports with component imports:

```scss
// layout (from component library)
@use "../components/organisms/header/header";
@use "../components/organisms/footer/footer";
```

Remove:
```scss
@use "layout/header";
@use "layout/footer";
```

### main.ts Changes

Remove `import './types/global'` (file being deleted). File becomes effectively empty but remains as the webpack entry point needed by `mix.extract()`.

### Component SCSS Adjustments

**header.scss** — replace comment placeholder with actual styles from `layout/header.scss`:
```scss
@use "../../../css/helper/functions.scss" as f;

header.page-header {
  box-sizing: border-box;
  box-shadow: inset 0 -4px 0 0 var(--color-primary), inset 0 -8px 0 0 var(--color-secondary);
}
```

**footer.scss** — replace comment placeholder with full styles from `layout/footer.scss`.

**content-block.scss** — replace comment placeholder with imports from molecule SCSS:
```scss
@use "../../../css/helper/functions.scss" as f;
@use "../../molecules/accent-text/accent-text";
@use "../../molecules/question-text/question-text";
```

**navigation-drawer.scss**, **hero-image.scss**, **page-list-grid.scss** — no changes needed, already have correct styles.

## Files to Delete

### Duplicated block SCSS (5 files)
- `resources/css/blocks/autonav/templates/innovationsbox/view.scss`
- `resources/css/blocks/hero_image/templates/innovationsbox/view.scss`
- `resources/css/blocks/content/templates/innovationsbox/view.scss`
- `resources/css/blocks/feature_link/templates/innovationsbox/view.scss`
- `resources/css/blocks/page_list/templates/innovationsbox/view.scss`

### Duplicated block TS (1 file)
- `resources/js/blocks/autonav/templates/innovationsbox/view.ts`

### Layout SCSS merged into components (2 files)
- `resources/css/layout/header.scss`
- `resources/css/layout/footer.scss`

### Dead JS files (4 files)
- `resources/js/helpers/dataservice.ts` — 100% commented out
- `resources/js/helpers/escapeHtml.ts` — nothing imports it
- `resources/js/types/global.ts` — only used by old autonav (component inlines enums)
- `resources/js/modules/index.ts` — empty file

### Empty directories to remove (10)
- `resources/css/blocks/` (entire tree)
- `resources/css/layout/`
- `resources/js/blocks/` (entire tree)
- `resources/js/helpers/`
- `resources/js/modules/`
- `resources/js/types/`

## What Stays

- `resources/css/main.scss` — webpack entry point (updated imports)
- `resources/css/global/*` — infrastructure (variables, fonts, resets, presets, CKEditor)
- `resources/css/helper/*` — SCSS functions and mixins (used by component SCSS)
- `resources/js/main.ts` — webpack entry point (needed by mix.extract())
- `resources/components/` — the component library (now the sole source)
- `resources/tokens/` — design tokens

## Verification

After implementation, `npm run development` must produce identical output files in `public/packages/inno_frontend/blocks/` at the same paths as before. Storybook must still work (`npm run storybook`).
