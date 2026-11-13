# Component Library Relocation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move `resources/components/` into `public/packages/inno_frontend/components/` so components ship with deployment, using a webpack alias for SCSS helper imports.

**Architecture:** `git mv` the component directory into the package. Add `~@helpers` webpack resolve alias for SCSS imports. Update all config files (webpack, Storybook, tsconfig, lint-staged, php-cs-fixer, DIR_COMPONENTS) to point at the new location. Update documentation in this repo and the arc42 docs.

**Tech Stack:** Laravel Mix (webpack), SCSS, TypeScript, PHP, Concrete CMS, Storybook

---

### Task 1: Move the component directory

**Files:**
- Move: `resources/components/` → `public/packages/inno_frontend/components/`

- [ ] **Step 1: Move the directory with git**

```bash
git mv resources/components public/packages/inno_frontend/components
```

- [ ] **Step 2: Verify the move**

```bash
ls public/packages/inno_frontend/components/atoms/button/button.php
ls public/packages/inno_frontend/components/organisms/header/header.php
test -d resources/components && echo "FAIL: old dir still exists" || echo "OK"
```

Expected: both files exist, old dir gone.

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "refactor: move component library into inno_frontend package"
```

---

### Task 2: Add webpack alias and update SCSS imports

**Files:**
- Modify: `webpack.mix.js`
- Modify: `public/packages/inno_frontend/components/organisms/header/header.scss`
- Modify: `public/packages/inno_frontend/components/organisms/footer/footer.scss`
- Modify: `public/packages/inno_frontend/components/organisms/hero-image/hero-image.scss`
- Modify: `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss`
- Modify: `public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.scss`
- Modify: `public/packages/inno_frontend/components/molecules/accent-text/accent-text.scss`
- Modify: `public/packages/inno_frontend/components/molecules/question-text/question-text.scss`
- Modify: `public/packages/inno_frontend/components/molecules/feature-button/feature-button.scss`
- Modify: `public/packages/inno_frontend/components/molecules/page-card/page-card.scss`
- Modify: `public/packages/inno_frontend/components/atoms/overlay/overlay.scss`

- [ ] **Step 1: Add resolve alias to webpack.mix.js**

In `webpack.mix.js`, replace the `resolve` block inside `mix.webpackConfig({...})`:

```js
  resolve: {
    extensions: ["*", ".js", ".jsx", ".vue", ".ts", ".tsx"]
  }
```

with:

```js
  resolve: {
    extensions: ["*", ".js", ".jsx", ".vue", ".ts", ".tsx"],
    alias: {
      '~@helpers': path.resolve(__dirname, 'resources/css/helper'),
    }
  }
```

- [ ] **Step 2: Update blockComponents source paths**

In `webpack.mix.js`, replace the `blockComponents` array:

```js
const blockComponents = [
  { src: 'resources/components/organisms/navigation-drawer/navigation-drawer.scss', out: 'blocks/autonav/templates/innovationsbox/view.css' },
  { src: 'resources/components/organisms/navigation-drawer/navigation-drawer.ts',   out: 'blocks/autonav/templates/innovationsbox/view.js' },
  { src: 'resources/components/organisms/hero-image/hero-image.scss',               out: 'blocks/hero_image/templates/innovationsbox/view.css' },
  { src: 'resources/components/organisms/content-block/content-block.scss',          out: 'blocks/content/templates/innovationsbox/view.css' },
  { src: 'resources/components/molecules/feature-button/feature-button.scss',        out: 'blocks/feature_link/templates/innovationsbox/view.css' },
  { src: 'resources/components/organisms/page-list-grid/page-list-grid.scss',        out: 'blocks/page_list/templates/innovationsbox/view.css' },
]
```

with:

```js
const blockComponents = [
  { src: 'public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss', out: 'blocks/autonav/templates/innovationsbox/view.css' },
  { src: 'public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.ts',   out: 'blocks/autonav/templates/innovationsbox/view.js' },
  { src: 'public/packages/inno_frontend/components/organisms/hero-image/hero-image.scss',               out: 'blocks/hero_image/templates/innovationsbox/view.css' },
  { src: 'public/packages/inno_frontend/components/organisms/content-block/content-block.scss',          out: 'blocks/content/templates/innovationsbox/view.css' },
  { src: 'public/packages/inno_frontend/components/molecules/feature-button/feature-button.scss',        out: 'blocks/feature_link/templates/innovationsbox/view.css' },
  { src: 'public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.scss',        out: 'blocks/page_list/templates/innovationsbox/view.css' },
]
```

- [ ] **Step 3: Update all 10 component SCSS files**

In each of these 9 files, replace line 1:

```scss
@use "../../../css/helper/functions.scss" as f;
```

with:

```scss
@use "~@helpers/functions" as f;
```

Files:
- `public/packages/inno_frontend/components/organisms/header/header.scss`
- `public/packages/inno_frontend/components/organisms/footer/footer.scss`
- `public/packages/inno_frontend/components/organisms/hero-image/hero-image.scss`
- `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss`
- `public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.scss`
- `public/packages/inno_frontend/components/molecules/accent-text/accent-text.scss`
- `public/packages/inno_frontend/components/molecules/question-text/question-text.scss`
- `public/packages/inno_frontend/components/molecules/feature-button/feature-button.scss`
- `public/packages/inno_frontend/components/molecules/page-card/page-card.scss`

And in this 1 file (note: no `.scss` extension in original), replace line 1:

```scss
@use "../../../css/helper/functions" as f;
```

with:

```scss
@use "~@helpers/functions" as f;
```

File:
- `public/packages/inno_frontend/components/atoms/overlay/overlay.scss`

- [ ] **Step 4: Commit**

```bash
git add webpack.mix.js public/packages/inno_frontend/components/
git commit -m "refactor: add webpack alias for SCSS helpers, update component imports"
```

---

### Task 3: Update main.scss imports

**Files:**
- Modify: `resources/css/main.scss`

- [ ] **Step 1: Update header/footer import paths**

In `resources/css/main.scss`, replace:

```scss
// layout (from component library)
@use "../components/organisms/header/header";
@use "../components/organisms/footer/footer";
```

with:

```scss
// layout (from component library)
@use "../../public/packages/inno_frontend/components/organisms/header/header";
@use "../../public/packages/inno_frontend/components/organisms/footer/footer";
```

- [ ] **Step 2: Commit**

```bash
git add resources/css/main.scss
git commit -m "refactor: update main.scss imports to new component location"
```

---

### Task 4: Update DIR_COMPONENTS constants

**Files:**
- Modify: `public/packages/inno_frontend/themes/innovationsbox/page_theme.php`
- Modify: `storybook-server/bootstrap.php`

- [ ] **Step 1: Update page_theme.php**

In `public/packages/inno_frontend/themes/innovationsbox/page_theme.php`, replace:

```php
            define('DIR_COMPONENTS', DIR_BASE . '/resources/components');
```

with:

```php
            define('DIR_COMPONENTS', DIR_BASE . '/packages/inno_frontend/components');
```

- [ ] **Step 2: Update storybook-server/bootstrap.php**

In `storybook-server/bootstrap.php`, replace:

```php
define('DIR_COMPONENTS', dirname(__DIR__) . '/resources/components');
```

with:

```php
define('DIR_COMPONENTS', dirname(__DIR__) . '/public/packages/inno_frontend/components');
```

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/themes/innovationsbox/page_theme.php storybook-server/bootstrap.php
git commit -m "refactor: update DIR_COMPONENTS to point inside inno_frontend package"
```

---

### Task 5: Update Storybook config

**Files:**
- Modify: `.storybook/main.js`

- [ ] **Step 1: Update stories glob and add webpack alias**

Replace the entire contents of `.storybook/main.js` with:

```js
import path from 'path';

/** @type {import('@storybook/server-webpack5').StorybookConfig} */
const config = {
  stories: [
    '../public/packages/inno_frontend/components/**/*.stories.json',
  ],
  addons: [
    '@storybook/addon-essentials',
    '@storybook/addon-links',
  ],
  framework: {
    name: '@storybook/server-webpack5',
    options: {},
  },
  staticDirs: [
    { from: '../public/packages/inno_frontend', to: '/packages/inno_frontend' },
  ],
  webpackFinal: async (config) => {
    config.resolve = config.resolve || {};
    config.resolve.alias = {
      ...config.resolve.alias,
      '~@helpers': path.resolve(__dirname, '../resources/css/helper'),
    };
    return config;
  },
};

export default config;
```

- [ ] **Step 2: Commit**

```bash
git add .storybook/main.js
git commit -m "refactor: update Storybook config for new component location"
```

---

### Task 6: Update tsconfig, lint-staged, and php-cs-fixer

**Files:**
- Modify: `tsconfig.json`
- Modify: `.lintstagedrc.json`
- Modify: `.php-cs-fixer.dist.php`

- [ ] **Step 1: Update tsconfig.json include**

In `tsconfig.json`, replace:

```json
  "include": ["resources/js/**/*", "resources/components/**/*"],
```

with:

```json
  "include": ["resources/js/**/*", "public/packages/inno_frontend/components/**/*"],
```

- [ ] **Step 2: Update .lintstagedrc.json**

Replace the entire contents of `.lintstagedrc.json` with:

```json
{
  "resources/**/*.ts": ["eslint --fix", "prettier --write"],
  "public/packages/inno_frontend/components/**/*.ts": ["eslint --fix", "prettier --write"],
  "resources/**/*.{js,json}": ["prettier --write"],
  "public/packages/inno_frontend/components/**/*.{js,json}": ["prettier --write"],
  "resources/**/*.scss": ["stylelint --fix"],
  "public/packages/inno_frontend/components/**/*.scss": ["stylelint --fix"],
  "**/*.php": ["php-cs-fixer fix"]
}
```

- [ ] **Step 3: Update .php-cs-fixer.dist.php**

In `.php-cs-fixer.dist.php`, replace:

```php
        __DIR__ . '/resources/components',
```

with:

```php
        __DIR__ . '/public/packages/inno_frontend/components',
```

- [ ] **Step 4: Commit**

```bash
git add tsconfig.json .lintstagedrc.json .php-cs-fixer.dist.php
git commit -m "refactor: update tsconfig, lint-staged, php-cs-fixer for new component path"
```

---

### Task 7: Verify build, Storybook, and tests

- [ ] **Step 1: Run the development build**

```bash
npm run development
```

Expected: build succeeds without errors.

- [ ] **Step 2: Verify all block output files exist**

```bash
ls -la public/packages/inno_frontend/blocks/autonav/templates/innovationsbox/view.css
ls -la public/packages/inno_frontend/blocks/autonav/templates/innovationsbox/view.js
ls -la public/packages/inno_frontend/blocks/hero_image/templates/innovationsbox/view.css
ls -la public/packages/inno_frontend/blocks/content/templates/innovationsbox/view.css
ls -la public/packages/inno_frontend/blocks/feature_link/templates/innovationsbox/view.css
ls -la public/packages/inno_frontend/blocks/page_list/templates/innovationsbox/view.css
```

Expected: all 6 files exist with non-zero size.

- [ ] **Step 3: Verify components exist inside the package**

```bash
ls public/packages/inno_frontend/components/atoms/button/button.php
ls public/packages/inno_frontend/components/organisms/header/header.php
ls public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.ts
```

Expected: all files exist.

- [ ] **Step 4: Run TypeScript type check**

```bash
npm run typecheck
```

Expected: passes with no errors.

- [ ] **Step 5: Verify Storybook starts**

Start the PHP server and Storybook:

```bash
php -S localhost:6007 storybook-server/index.php &
STORYBOOK_DISABLE_TELEMETRY=1 npx storybook dev -p 6006 --no-open --ci
```

Expected: Storybook compiles successfully. Test a component:

```bash
curl -s "http://localhost:6007/atoms/button?variant=primary&label=Test&href=%23"
```

Expected: renders `<a class="button button--primary" ...>Test</a>`.

- [ ] **Step 6: Commit build artifacts if changed**

```bash
git add public/packages/inno_frontend/blocks/ public/packages/inno_frontend/themes/innovationsbox/js/
git commit -m "build: regenerate assets from relocated components"
```

---

### Task 8: Update documentation (this repo)

**Files:**
- Modify: `CLAUDE.md`
- Modify: `CONTRIBUTING.md`

- [ ] **Step 1: Update CLAUDE.md file layout section**

In `CLAUDE.md`, replace the file layout block:

```
resources/
  components/    ← Storybook component library (Atomic Design: atoms/molecules/organisms/templates/pages)
                   Single source of truth for block SCSS/TS — compiled to CMS block output paths
  css/           ← SCSS source (main entry: main.scss, global styles, helper functions)
  js/            ← TypeScript source (main entry: main.ts)
    single_pages/← Single-page TS → compiled to themes/innovationsbox/js/single_pages/
  tokens/        ← Design tokens (tokens.json → generated SCSS variables)
```

with:

```
resources/
  css/           ← SCSS source (main entry: main.scss, global styles, helper functions)
  js/            ← TypeScript source (main entry: main.ts)
    single_pages/← Single-page TS → compiled to themes/innovationsbox/js/single_pages/
  tokens/        ← Design tokens (tokens.json → generated SCSS variables)
```

And under `inno_frontend/` in the package listing, add the components line:

```
    inno_frontend/        ← Theme + UI assets + component library (handle: inno_frontend)
```

- [ ] **Step 2: Update CLAUDE.md component library section**

Replace the component library path:

```
resources/components/
```

with:

```
public/packages/inno_frontend/components/
```

- [ ] **Step 3: Update CLAUDE.md webpack section**

Replace:

```
- Block-specific SCSS/TS are compiled from `resources/components/` via an explicit mapping table in `webpack.mix.js` — each component maps to a CMS block output path (e.g. `organisms/navigation-drawer/` → `blocks/autonav/templates/innovationsbox/view.css`). Output filenames must be `view.css`/`view.js` (Concrete CMS convention).
```

with:

```
- Block-specific SCSS/TS are compiled from `public/packages/inno_frontend/components/` via an explicit mapping table in `webpack.mix.js` — each component maps to a CMS block output path (e.g. `organisms/navigation-drawer/` → `blocks/autonav/templates/innovationsbox/view.css`). Output filenames must be `view.css`/`view.js` (Concrete CMS convention). Component SCSS uses a `~@helpers` webpack alias to import shared functions from `resources/css/helper/`.
```

- [ ] **Step 4: Update CONTRIBUTING.md**

Replace:

```
Components live in `resources/components/` following Atomic Design:
```

with:

```
Components live in `public/packages/inno_frontend/components/` following Atomic Design:
```

- [ ] **Step 5: Commit**

```bash
git add CLAUDE.md CONTRIBUTING.md
git commit -m "docs: update CLAUDE.md and CONTRIBUTING.md for component relocation"
```

---

### Task 9: Update innovationsbox-dokumentation repo

**Files:**
- Modify: `/home/progressio/lemonbrain/git/innovationsbox-dokumentation/src/product/arc42/05_building_block_view.typ`

- [ ] **Step 1: Update the component tree listing**

In `05_building_block_view.typ`, replace:

```
resources/components/
  atoms/         ← 8 Basiskomponenten: button, heading, paragraph, link, image, container, skip-link, overlay
  molecules/     ← 6 zusammengesetzte Gruppen: feature-button, page-card, nav-item, accent-text, question-text, partner-logo
  organisms/     ← 6 komplexe Sektionen: hero-image, page-list-grid, navigation-drawer, header, footer, content-block
  templates/     ← 2 Seitenlayouts: default-page, view-page
  pages/         ← 2 Beispielseiten: homepage, inner-page
```

with:

```
public/packages/inno_frontend/components/
  atoms/         ← 8 Basiskomponenten: button, heading, paragraph, link, image, container, skip-link, overlay
  molecules/     ← 6 zusammengesetzte Gruppen: feature-button, page-card, nav-item, accent-text, question-text, partner-logo
  organisms/     ← 6 komplexe Sektionen: hero-image, page-list-grid, navigation-drawer, header, footer, content-block
  templates/     ← 2 Seitenlayouts: default-page, view-page
  pages/         ← 2 Beispielseiten: homepage, inner-page
```

- [ ] **Step 2: Commit in the documentation repo**

```bash
cd /home/progressio/lemonbrain/git/innovationsbox-dokumentation
git add src/product/arc42/05_building_block_view.typ
git commit -m "docs: update component library path after relocation into inno_frontend package"
```
