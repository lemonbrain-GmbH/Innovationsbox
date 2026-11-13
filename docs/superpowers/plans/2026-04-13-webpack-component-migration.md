# Webpack Component Library Migration — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `resources/components/` the single source of truth for block SCSS/TS by rewiring webpack.mix.js and deleting duplicated files.

**Architecture:** Replace webpack.mix.js auto-discovery of `resources/css/blocks/` and `resources/js/blocks/` with an explicit mapping table pointing at component library sources. Move layout styles into component SCSS. Delete all duplicated and dead files.

**Tech Stack:** Laravel Mix (webpack), SCSS, TypeScript, Concrete CMS block conventions

---

### Task 1: Update component SCSS files (header, footer, content-block)

Three component SCSS files are currently placeholders pointing at old locations. They need real content before we can delete the old files.

**Files:**
- Modify: `resources/components/organisms/header/header.scss`
- Modify: `resources/components/organisms/footer/footer.scss`
- Modify: `resources/components/organisms/content-block/content-block.scss`

- [ ] **Step 1: Replace header.scss placeholder with actual styles**

Replace the entire contents of `resources/components/organisms/header/header.scss` with:

```scss
@use "../../../css/helper/functions.scss" as f;

header.page-header {
  box-sizing: border-box;
  box-shadow: inset 0 -4px 0 0 var(--color-primary), inset 0 -8px 0 0 var(--color-secondary);
}
```

- [ ] **Step 2: Replace footer.scss placeholder with actual styles**

Replace the entire contents of `resources/components/organisms/footer/footer.scss` with:

```scss
@use "../../../css/helper/functions.scss" as f;

footer {
  background-color: var(--color-brown);
  padding: f.fluidVal(12, 24) 0 f.fluidVal(16, 48);
  margin-top: f.fluidVal(48, 96);

  .static-content {
    text-align: center;

    p {
      color: var(--color-white);
    }

    a.footer-link:hover {
      color: var(--color-white);
    }

    .logos {
      margin-top: f.fluidVal(24, 48);
      display: flex;
      justify-content: center;
      gap: f.fluidVal(16, 32);

      a.logo-link {
        display: flex;
        width: f.fluidVal(80, 120);
      }
    }
  }
}
```

- [ ] **Step 3: Replace content-block.scss placeholder with molecule imports**

Replace the entire contents of `resources/components/organisms/content-block/content-block.scss` with:

```scss
@use "../../molecules/accent-text/accent-text";
@use "../../molecules/question-text/question-text";
```

- [ ] **Step 4: Commit**

```bash
git add resources/components/organisms/header/header.scss resources/components/organisms/footer/footer.scss resources/components/organisms/content-block/content-block.scss
git commit -m "refactor: move layout and block styles into component SCSS files"
```

---

### Task 2: Update main.scss to import from component library

**Files:**
- Modify: `resources/css/main.scss`

- [ ] **Step 1: Replace layout imports with component imports**

In `resources/css/main.scss`, replace these two lines:

```scss
// layout
@use "layout/header";
@use "layout/footer";
```

with:

```scss
// layout (from component library)
@use "../components/organisms/header/header";
@use "../components/organisms/footer/footer";
```

The full file should now be:

```scss
@use "../../node_modules/normalize.css/normalize.css";
@use "global/_generated-variables";
@use "global/fonts.css";

// global
@use "global/mixins";
@use "global/global";
@use "global/cke";

// layout (from component library)
@use "../components/organisms/header/header";
@use "../components/organisms/footer/footer";

.ccm-page {
  @import "global/presets";
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/css/main.scss
git commit -m "refactor: import header/footer styles from component library"
```

---

### Task 3: Update main.ts to remove dead import

**Files:**
- Modify: `resources/js/main.ts`

- [ ] **Step 1: Remove the dead import**

Replace the entire contents of `resources/js/main.ts` with:

```ts
// webpack entry point — mix.extract() requires this file
```

The old file imported `./types/global` which is being deleted. Nothing else is used.

- [ ] **Step 2: Commit**

```bash
git add resources/js/main.ts
git commit -m "refactor: remove dead global type import from main.ts"
```

---

### Task 4: Rewire webpack.mix.js

Replace block auto-discovery with explicit component mapping. This is the core change.

**Files:**
- Modify: `webpack.mix.js`

- [ ] **Step 1: Replace block auto-discovery with component mapping**

In `webpack.mix.js`, replace this block (lines 57–76):

```js
// block relevant js gets built into block directory
const blockJsFiles = getAllFiles('resources/js/blocks');
blockJsFiles.forEach(f => {
  mix.ts(f.fullpath, 'blocks/' + f.relpath)
})
// singlePage relevant js gets built into themes/innovationsbox/js/single_pages directory
const singlePageJsFiles = getAllFiles('resources/js/single_pages');
singlePageJsFiles.forEach(f => {
  mix.ts(f.fullpath, 'themes/innovationsbox/js/single_pages/')
})

// block relevant css gets built into block directory
const blockCssFiles = getAllFiles('resources/css/blocks');
blockCssFiles.forEach(f => {
  if (!f.fullpath.endsWith('.scss')) return;
  mix.sass(
    f.fullpath,
    'blocks/' + path.dirname(f.relpath)
  )
})
// singlePage relevant css gets built into themes/innovationsbox/css/single_pages directory
const singlePageCssFiles = getAllFiles('resources/css/single_pages');
singlePageCssFiles.forEach(f => {
  if (!f.fullpath.endsWith('.scss')) return;
  mix.sass(f.fullpath, 'themes/innovationsbox/css/single_pages/')
})
```

with:

```js
// Component library → CMS block output mapping
// Output filenames must be view.css / view.js (Concrete CMS convention in BlockViewTemplate.php)
const blockComponents = [
  { src: 'resources/components/organisms/navigation-drawer/navigation-drawer.scss', out: 'blocks/autonav/templates/innovationsbox/view.css' },
  { src: 'resources/components/organisms/navigation-drawer/navigation-drawer.ts',   out: 'blocks/autonav/templates/innovationsbox/view.js' },
  { src: 'resources/components/organisms/hero-image/hero-image.scss',               out: 'blocks/hero_image/templates/innovationsbox/view.css' },
  { src: 'resources/components/organisms/content-block/content-block.scss',          out: 'blocks/content/templates/innovationsbox/view.css' },
  { src: 'resources/components/molecules/feature-button/feature-button.scss',        out: 'blocks/feature_link/templates/innovationsbox/view.css' },
  { src: 'resources/components/organisms/page-list-grid/page-list-grid.scss',        out: 'blocks/page_list/templates/innovationsbox/view.css' },
]

blockComponents.forEach(({ src, out }) => {
  if (src.endsWith('.ts')) {
    mix.ts(src, out)
  } else {
    mix.sass(src, out)
  }
})

// singlePage relevant js gets built into themes/innovationsbox/js/single_pages directory
const singlePageJsFiles = getAllFiles('resources/js/single_pages');
singlePageJsFiles.forEach(f => {
  mix.ts(f.fullpath, 'themes/innovationsbox/js/single_pages/')
})

// singlePage relevant css gets built into themes/innovationsbox/css/single_pages directory
const singlePageCssFiles = getAllFiles('resources/css/single_pages');
singlePageCssFiles.forEach(f => {
  if (!f.fullpath.endsWith('.scss')) return;
  mix.sass(f.fullpath, 'themes/innovationsbox/css/single_pages/')
})
```

- [ ] **Step 2: Commit**

```bash
git add webpack.mix.js
git commit -m "refactor: replace block auto-discovery with component library mapping"
```

---

### Task 5: Delete duplicated and dead files

Remove all old block SCSS/TS, layout SCSS, and dead JS files.

**Files to delete:**
- `resources/css/blocks/autonav/templates/innovationsbox/view.scss`
- `resources/css/blocks/hero_image/templates/innovationsbox/view.scss`
- `resources/css/blocks/content/templates/innovationsbox/view.scss`
- `resources/css/blocks/feature_link/templates/innovationsbox/view.scss`
- `resources/css/blocks/page_list/templates/innovationsbox/view.scss`
- `resources/js/blocks/autonav/templates/innovationsbox/view.ts`
- `resources/css/layout/header.scss`
- `resources/css/layout/footer.scss`
- `resources/js/helpers/dataservice.ts`
- `resources/js/helpers/escapeHtml.ts`
- `resources/js/types/global.ts`
- `resources/js/modules/index.ts`

- [ ] **Step 1: Delete all duplicated and dead files, remove empty directories**

```bash
git rm resources/css/blocks/autonav/templates/innovationsbox/view.scss
git rm resources/css/blocks/hero_image/templates/innovationsbox/view.scss
git rm resources/css/blocks/content/templates/innovationsbox/view.scss
git rm resources/css/blocks/feature_link/templates/innovationsbox/view.scss
git rm resources/css/blocks/page_list/templates/innovationsbox/view.scss
git rm resources/js/blocks/autonav/templates/innovationsbox/view.ts
git rm resources/css/layout/header.scss
git rm resources/css/layout/footer.scss
git rm resources/js/helpers/dataservice.ts
git rm resources/js/helpers/escapeHtml.ts
git rm resources/js/types/global.ts
git rm resources/js/modules/index.ts
```

Then remove the now-empty directories (git does not track directories, but clean up the working tree):

```bash
rm -rf resources/css/blocks resources/css/layout resources/js/blocks resources/js/helpers resources/js/modules resources/js/types
```

- [ ] **Step 2: Commit**

```bash
git commit -m "refactor: remove duplicated block SCSS/TS and dead JS files"
```

---

### Task 6: Verify the build

Run the webpack build and confirm all output files are produced at the correct paths.

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

Expected: all 6 files exist and have non-zero size.

- [ ] **Step 3: Verify main theme assets exist**

```bash
ls -la public/packages/inno_frontend/themes/innovationsbox/js/main.js
ls -la public/packages/inno_frontend/themes/innovationsbox/js/manifest.js
ls -la public/packages/inno_frontend/themes/innovationsbox/css/skins/default.css
```

Expected: all 3 files exist and have non-zero size.

- [ ] **Step 4: Verify Storybook still works**

```bash
npm run storybook:build
```

Expected: build succeeds (Storybook reads component SCSS directly, unaffected by webpack changes).

- [ ] **Step 5: Verify no old directories remain**

```bash
test -d resources/css/blocks && echo "FAIL: blocks dir still exists" || echo "OK"
test -d resources/css/layout && echo "FAIL: layout dir still exists" || echo "OK"
test -d resources/js/blocks && echo "FAIL: blocks dir still exists" || echo "OK"
test -d resources/js/helpers && echo "FAIL: helpers dir still exists" || echo "OK"
test -d resources/js/modules && echo "FAIL: modules dir still exists" || echo "OK"
test -d resources/js/types && echo "FAIL: types dir still exists" || echo "OK"
```

Expected: all 6 print "OK".

- [ ] **Step 6: Commit build artifacts if needed**

If the compiled output files changed (check `git status`), commit them:

```bash
git add public/packages/inno_frontend/blocks/ public/packages/inno_frontend/themes/
git commit -m "build: regenerate block and theme assets from component library"
```

---

### Task 7: Clean up getAllFiles utility

After removing block auto-discovery, the `getAllFiles` function in `webpack.mix.js` is only used by single_pages (which currently only have `.keep` files). It can stay for future single-page use, but the relpath logic that strips `resources/js/blocks/` and `resources/css/blocks/` prefixes is now dead code.

**Files:**
- Modify: `webpack.mix.js`

- [ ] **Step 1: Clean up the relpath replacements in getAllFiles**

In `webpack.mix.js`, replace the `getAllFiles` function (lines 5–25):

```js
const getAllFiles = function (dirPath, arrayOfFiles) {
  files = fs.readdirSync(dirPath)
  arrayOfFiles = arrayOfFiles || []

  files.forEach(function (file) {
    if (fs.statSync(dirPath + "/" + file).isDirectory()) {
      arrayOfFiles = getAllFiles(dirPath + "/" + file, arrayOfFiles)
    } else {
      arrayOfFiles.push({
        fullpath: path.join(dirPath, "/", file),
        relpath: path.join(dirPath, "/", file)
          .replace(/^resources\/js\/blocks\//, "")
          .replace(/\.ts$/, ".js")
          .replace(/^resources\/css\/blocks\//, "")
          .replace(/\.scss$/, ".css")
      })
    }
  })

  return arrayOfFiles
}
```

with:

```js
const getAllFiles = function (dirPath, arrayOfFiles) {
  files = fs.readdirSync(dirPath)
  arrayOfFiles = arrayOfFiles || []

  files.forEach(function (file) {
    if (fs.statSync(dirPath + "/" + file).isDirectory()) {
      arrayOfFiles = getAllFiles(dirPath + "/" + file, arrayOfFiles)
    } else {
      arrayOfFiles.push({
        fullpath: path.join(dirPath, "/", file),
      })
    }
  })

  return arrayOfFiles
}
```

Then update the single-page loops to use `fullpath` only. Replace:

```js
// singlePage relevant js gets built into themes/innovationsbox/js/single_pages directory
const singlePageJsFiles = getAllFiles('resources/js/single_pages');
singlePageJsFiles.forEach(f => {
  mix.ts(f.fullpath, 'themes/innovationsbox/js/single_pages/')
})

// singlePage relevant css gets built into themes/innovationsbox/css/single_pages directory
const singlePageCssFiles = getAllFiles('resources/css/single_pages');
singlePageCssFiles.forEach(f => {
  if (!f.fullpath.endsWith('.scss')) return;
  mix.sass(f.fullpath, 'themes/innovationsbox/css/single_pages/')
})
```

with:

```js
// singlePage relevant js gets built into themes/innovationsbox/js/single_pages directory
const singlePageJsFiles = getAllFiles('resources/js/single_pages');
singlePageJsFiles.forEach(f => {
  if (!f.fullpath.endsWith('.ts')) return;
  mix.ts(f.fullpath, 'themes/innovationsbox/js/single_pages/')
})

// singlePage relevant css gets built into themes/innovationsbox/css/single_pages directory
const singlePageCssFiles = getAllFiles('resources/css/single_pages');
singlePageCssFiles.forEach(f => {
  if (!f.fullpath.endsWith('.scss')) return;
  mix.sass(f.fullpath, 'themes/innovationsbox/css/single_pages/')
})
```

- [ ] **Step 2: Run build to verify nothing broke**

```bash
npm run development
```

Expected: build succeeds, same output as before.

- [ ] **Step 3: Commit**

```bash
git add webpack.mix.js
git commit -m "refactor: clean up getAllFiles utility after block migration"
```
