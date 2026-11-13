# Component Library Relocation into inno_frontend Package

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Move the component library from `resources/components/` into `public/packages/inno_frontend/components/` so it ships with the deployed package, while keeping a single source of truth for both CMS and Storybook.

## Problem

The Bitbucket pipeline deploys only `public/packages/inno_frontend/`. The component PHP partials (used by block `view.php` files at runtime) currently live in `resources/components/` — outside the package. They don't get deployed.

## Approach

Move the entire component directory into the package. Use a webpack resolve alias for SCSS imports that reference build-time infrastructure (`resources/css/helper/functions.scss`), keeping DRY.

## Changes

### 1. Move components

```
resources/components/  →  public/packages/inno_frontend/components/
```

All files move as-is: PHP partials, SCSS, TS, `.stories.json`, `.variants.php`. The directory structure (atoms/molecules/organisms/templates/pages) is preserved.

### 2. SCSS imports — webpack alias

Add a `~@helpers` alias in webpack that resolves to `resources/css/helper/`.

All 10 component SCSS files that currently use:
```scss
@use "../../../css/helper/functions.scss" as f;
```
change to:
```scss
@use "~@helpers/functions" as f;
```

The same alias is added to Storybook's webpack config (`.storybook/main.js` `webpackFinal`).

Files affected:
- `components/organisms/header/header.scss`
- `components/organisms/footer/footer.scss`
- `components/organisms/hero-image/hero-image.scss`
- `components/organisms/navigation-drawer/navigation-drawer.scss`
- `components/organisms/page-list-grid/page-list-grid.scss`
- `components/molecules/accent-text/accent-text.scss`
- `components/molecules/question-text/question-text.scss`
- `components/molecules/feature-button/feature-button.scss`
- `components/molecules/page-card/page-card.scss`
- `components/atoms/overlay/overlay.scss`

Intra-component `@use` imports (e.g. content-block importing accent-text) stay relative — they moved together.

### 3. DIR_COMPONENTS updates

**`public/packages/inno_frontend/themes/innovationsbox/page_theme.php`** (CMS runtime):
```php
define('DIR_COMPONENTS', DIR_BASE . '/packages/inno_frontend/components');
```

**`storybook-server/bootstrap.php`** (Storybook dev):
```php
define('DIR_COMPONENTS', dirname(__DIR__) . '/public/packages/inno_frontend/components');
```

Block `view.php` files and component PHP partials use `DIR_COMPONENTS` — no changes needed.

### 4. Webpack updates (`webpack.mix.js`)

Add resolve alias:
```js
resolve: {
  alias: {
    '~@helpers': path.resolve(__dirname, 'resources/css/helper'),
  }
}
```

Update `blockComponents` source paths:
```js
{ src: 'public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss', out: '...' },
// etc.
```

### 5. Storybook updates (`.storybook/main.js`)

Update stories glob:
```js
stories: ['../public/packages/inno_frontend/components/**/*.stories.json']
```

Add same `~@helpers` alias in `webpackFinal`.

### 6. Other config updates

**`tsconfig.json`** — update include:
```json
"include": ["resources/js/**/*", "public/packages/inno_frontend/components/**/*"]
```

**`resources/css/main.scss`** — update header/footer imports:
```scss
@use "../../public/packages/inno_frontend/components/organisms/header/header";
@use "../../public/packages/inno_frontend/components/organisms/footer/footer";
```

### 7. Documentation updates

**This repo:**
- `CLAUDE.md` — update file layout section and webpack section
- `CONTRIBUTING.md` — update component paths if referenced
- `README.md` — update component paths if referenced

**`innovationsbox-dokumentation` repo:**
- `src/product/arc42/05_building_block_view.typ` — update component tree listing

Historical plan/spec files are not updated (they document what was done at that point).

## What Doesn't Change

- Block `view.php` files (use `DIR_COMPONENTS` constant)
- Component PHP partials (inter-component includes use `DIR_COMPONENTS`)
- `resources/css/global/`, `resources/css/helper/` (build infrastructure, stays in place)
- `resources/js/main.ts`, `resources/tokens/`
- `.storybook/preview-head.html`, `.storybook/preview.js`

## Verification

1. `npm run development` — produces same block CSS/JS output files
2. `npm run storybook` — components render in Storybook
3. `composer test` — all unit tests pass
4. `npm run typecheck` — no TS errors
5. `npm run lint:all` — no lint errors
6. Component PHP partials exist inside the package at `public/packages/inno_frontend/components/`
