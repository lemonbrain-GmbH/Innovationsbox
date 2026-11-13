# Add PHP Type Declarations to Frontend Package — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `declare(strict_types=1)`, explicit type casts, and `@var` PHPDoc annotations to all untyped PHP files in `public/packages/inno_frontend/`.

**Architecture:** Each component partial extracts variables from a `$props` array. We add `declare(strict_types=1)` at the top, then annotate+cast each extracted variable. Block templates, theme files, and single pages get the same treatment where applicable. Finally, we fix the one remaining gap in the domain packages (`ProjectSpace::getMemberIds()`) and add the frontend to PHPStan.

**Tech Stack:** PHP 8.1+, PHPStan Level 6, Concrete CMS 9.4+

**Pattern to apply consistently:**

```php
<?php

declare(strict_types=1);

/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'primary');

/** @var bool $disabled */
$disabled = ($props['disabled'] ?? 'false') === 'true';

/** @var int $rows */
$rows = (int) ($props['rows'] ?? '4');

/** @var array<string, mixed> $items */
$items = (array) json_decode((string) ($props['items'] ?? '[]'), true);
```

Rules:
- `(string)` cast for all string props
- `(int)` cast for numeric props
- `(bool)` cast is implicit when `=== 'true'` comparison is used — add `@var bool` annotation only
- `(array)` cast for json_decode results, with `@var array<...>` annotation
- Do NOT cast intermediate/computed variables (e.g. `$classes`, `$classAttr`, `$inner`) — these are already typed by their assignment. Add `@var` only if the type is non-obvious.
- `declare(strict_types=1)` must be the very first statement after `<?php` (before any other code)
- For block templates that start with `defined('C5_EXECUTE') or die(...)`, place `declare(strict_types=1)` between `<?php` and the `defined()` check

---

### Task 1: Atom Components (Part 1 — tokens + avatar through divider)

**Files to modify:**
- `public/packages/inno_frontend/components/atoms/_tokens/colors-brand/colors-brand.php`
- `public/packages/inno_frontend/components/atoms/_tokens/colors-neutral/colors-neutral.php`
- `public/packages/inno_frontend/components/atoms/_tokens/colors-semantic/colors-semantic.php`
- `public/packages/inno_frontend/components/atoms/_tokens/colors-status/colors-status.php`
- `public/packages/inno_frontend/components/atoms/_tokens/motion/motion.php`
- `public/packages/inno_frontend/components/atoms/_tokens/radius/radius.php`
- `public/packages/inno_frontend/components/atoms/_tokens/shadows/shadows.php`
- `public/packages/inno_frontend/components/atoms/_tokens/spacing/spacing.php`
- `public/packages/inno_frontend/components/atoms/_tokens/typography/typography.php`
- `public/packages/inno_frontend/components/atoms/avatar/avatar.php`
- `public/packages/inno_frontend/components/atoms/badge/badge.php`
- `public/packages/inno_frontend/components/atoms/button/button.php` (already has strict_types — add casts + @var)
- `public/packages/inno_frontend/components/atoms/caption/caption.php`
- `public/packages/inno_frontend/components/atoms/checkbox/checkbox.php`
- `public/packages/inno_frontend/components/atoms/code/code.php`
- `public/packages/inno_frontend/components/atoms/container/container.php`
- `public/packages/inno_frontend/components/atoms/divider/divider.php`

- [ ] **Step 1: Read all 17 files to understand current variable extraction**

Read each file and note which variables are extracted from `$props` and which are computed.

- [ ] **Step 2: Add strict_types + casts + @var to each token file**

Token files follow the pattern: extract JSON string from props, decode to array.

Example for `colors-brand.php` — transform from:
```php
<?php
$scalesJson = $props['scales'] ?? '[]';
$scales = json_decode($scalesJson, true) ?: [];
```
To:
```php
<?php

declare(strict_types=1);

/** @var string $scalesJson */
$scalesJson = (string) ($props['scales'] ?? '[]');

/** @var array<string, string> $scales */
$scales = (array) (json_decode($scalesJson, true) ?: []);
```

Apply same pattern to all 9 token files. Use `array<string, string>` for color tokens, `array<string, string>` for motion/radius/shadows/spacing, and `array<string, mixed>` for typography.

- [ ] **Step 3: Add strict_types + casts + @var to atom component files (avatar through divider)**

For each file: add `declare(strict_types=1)`, add `(string)` casts to string props, `@var bool` to boolean comparisons, `(int)` to integer props, and `@var array<...>` for arrays.

For `button.php` (already has strict_types): just add the `(string)` casts and `@var` annotations to the variable extraction section.

- [ ] **Step 4: Verify no syntax errors**

```bash
for f in public/packages/inno_frontend/components/atoms/_tokens/*/; do php -l "${f}"*.php; done
for f in public/packages/inno_frontend/components/atoms/{avatar,badge,button,caption,checkbox,code,container,divider}/; do php -l "${f}"*.php; done
```

Expected: `No syntax errors detected` for all files.

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/_tokens/ \
        public/packages/inno_frontend/components/atoms/avatar/ \
        public/packages/inno_frontend/components/atoms/badge/ \
        public/packages/inno_frontend/components/atoms/button/ \
        public/packages/inno_frontend/components/atoms/caption/ \
        public/packages/inno_frontend/components/atoms/checkbox/ \
        public/packages/inno_frontend/components/atoms/code/ \
        public/packages/inno_frontend/components/atoms/container/ \
        public/packages/inno_frontend/components/atoms/divider/
git commit -m "refactor(frontend): add strict_types, casts and @var to atom components (part 1)"
```

---

### Task 2: Atom Components (Part 2 — heading through tooltip)

**Files to modify:**
- `public/packages/inno_frontend/components/atoms/heading/heading.php`
- `public/packages/inno_frontend/components/atoms/icon/icon.php`
- `public/packages/inno_frontend/components/atoms/image/image.php`
- `public/packages/inno_frontend/components/atoms/input/input.php`
- `public/packages/inno_frontend/components/atoms/label/label.php`
- `public/packages/inno_frontend/components/atoms/link/link.php`
- `public/packages/inno_frontend/components/atoms/overlay/overlay.php`
- `public/packages/inno_frontend/components/atoms/paragraph/paragraph.php`
- `public/packages/inno_frontend/components/atoms/radio/radio.php`
- `public/packages/inno_frontend/components/atoms/select/select.php`
- `public/packages/inno_frontend/components/atoms/skip-link/skip-link.php`
- `public/packages/inno_frontend/components/atoms/spinner/spinner.php`
- `public/packages/inno_frontend/components/atoms/textarea/textarea.php`
- `public/packages/inno_frontend/components/atoms/toggle/toggle.php`
- `public/packages/inno_frontend/components/atoms/tooltip/tooltip.php`

- [ ] **Step 1: Read all 15 files**

- [ ] **Step 2: Add strict_types + casts + @var to each file**

Same pattern as Task 1. Key type mappings for these files:
- `heading.php`: `$level` → `(int)`, `$text` → `(string)`, `$id` → `(string)`, `$variant` → `(string)`
- `image.php`: all strings, `$rounded` → bool
- `input.php`: all strings except `$disabled`, `$required`, `$invalid` → bool
- `select.php`: `$optionsJson` → `(string)`, `$options` → `(array)` from json_decode, booleans for disabled/required/invalid
- `textarea.php`: `$rows` → `(int)`, booleans for disabled/required/invalid
- All form-like atoms: `$describedBy` → `(string)`

- [ ] **Step 3: Verify no syntax errors**

```bash
for f in public/packages/inno_frontend/components/atoms/{heading,icon,image,input,label,link,overlay,paragraph,radio,select,skip-link,spinner,textarea,toggle,tooltip}/; do php -l "${f}"*.php; done
```

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/{heading,icon,image,input,label,link,overlay,paragraph,radio,select,skip-link,spinner,textarea,toggle,tooltip}/
git commit -m "refactor(frontend): add strict_types, casts and @var to atom components (part 2)"
```

---

### Task 3: Molecule Components (Part 1 — accent-text through form-field)

**Files to modify:**
- `public/packages/inno_frontend/components/molecules/accent-text/accent-text.php`
- `public/packages/inno_frontend/components/molecules/alert/alert.php`
- `public/packages/inno_frontend/components/molecules/auth-card/auth-card.php`
- `public/packages/inno_frontend/components/molecules/avatar-upload/avatar-upload.php`
- `public/packages/inno_frontend/components/molecules/breadcrumb/breadcrumb.php`
- `public/packages/inno_frontend/components/molecules/card/card.php`
- `public/packages/inno_frontend/components/molecules/dropdown/dropdown.php`
- `public/packages/inno_frontend/components/molecules/empty-state/empty-state.php`
- `public/packages/inno_frontend/components/molecules/feature-button/feature-button.php`
- `public/packages/inno_frontend/components/molecules/form-field/form-field.php`

- [ ] **Step 1: Read all 10 files**

- [ ] **Step 2: Add strict_types + casts + @var to each file**

Key type mappings:
- `alert.php`: `$icons` is a PHP array literal (not from props) — add `@var array<string, string>`, booleans for `$dismissible`, `$showIcon`
- `breadcrumb.php`: `$itemsJson` → `(string)`, `$items` → `@var array<int, array{text: string, href: string}>` from json_decode
- `card.php`: `$titleLevel` → `(int)`, `$clickable` → bool
- `dropdown.php`: `$itemsJson` → `(string)`, `$items` → `@var array<int, array{label: string, href: string}>` from json_decode, `$disabled` → bool
- `form-field.php`: multiple booleans, `$commonProps` is a computed array

- [ ] **Step 3: Verify no syntax errors**

```bash
for f in public/packages/inno_frontend/components/molecules/{accent-text,alert,auth-card,avatar-upload,breadcrumb,card,dropdown,empty-state,feature-button,form-field}/; do php -l "${f}"*.php; done
```

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/{accent-text,alert,auth-card,avatar-upload,breadcrumb,card,dropdown,empty-state,feature-button,form-field}/
git commit -m "refactor(frontend): add strict_types, casts and @var to molecule components (part 1)"
```

---

### Task 4: Molecule Components (Part 2 — member-card through user-menu)

**Files to modify:**
- `public/packages/inno_frontend/components/molecules/member-card/member-card.php`
- `public/packages/inno_frontend/components/molecules/modal/modal.php`
- `public/packages/inno_frontend/components/molecules/nav-item/nav-item.php`
- `public/packages/inno_frontend/components/molecules/page-card/page-card.php`
- `public/packages/inno_frontend/components/molecules/pagination/pagination.php`
- `public/packages/inno_frontend/components/molecules/partner-logo/partner-logo.php`
- `public/packages/inno_frontend/components/molecules/password-field/password-field.php`
- `public/packages/inno_frontend/components/molecules/question-text/question-text.php`
- `public/packages/inno_frontend/components/molecules/search-bar/search-bar.php`
- `public/packages/inno_frontend/components/molecules/skeleton/skeleton.php`
- `public/packages/inno_frontend/components/molecules/tabs/tabs.php`
- `public/packages/inno_frontend/components/molecules/user-menu/user-menu.php`

- [ ] **Step 1: Read all 12 files**

- [ ] **Step 2: Add strict_types + casts + @var to each file**

Key type mappings:
- `pagination.php`: `$current` → `(int)`, `$total` → `(int)`, `$siblings` → `(int)`, `$buildHref` is a closure — add `@var \Closure(int): string`
- `skeleton.php`: `$count` → `(int)`, `$defaults` is a PHP array literal
- `tabs.php`: `$tabsJson` → `(string)`, `$tabs` → `@var array<int, array{label: string, content: string, disabled?: bool}>` from json_decode, `$activeIndex` → `(int)`
- `modal.php`: `$open` → bool
- `member-card.php`: `$isLinked` → bool
- `search-bar.php`: `$clearable`, `$disabled` → bool

- [ ] **Step 3: Verify no syntax errors**

```bash
for f in public/packages/inno_frontend/components/molecules/{member-card,modal,nav-item,page-card,pagination,partner-logo,password-field,question-text,search-bar,skeleton,tabs,user-menu}/; do php -l "${f}"*.php; done
```

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/{member-card,modal,nav-item,page-card,pagination,partner-logo,password-field,question-text,search-bar,skeleton,tabs,user-menu}/
git commit -m "refactor(frontend): add strict_types, casts and @var to molecule components (part 2)"
```

---

### Task 5: Organism, Template, and Page Components

**Files to modify:**
- `public/packages/inno_frontend/components/organisms/content-block/content-block.php`
- `public/packages/inno_frontend/components/organisms/footer/footer.php`
- `public/packages/inno_frontend/components/organisms/header/header.php`
- `public/packages/inno_frontend/components/organisms/hero-image/hero-image.php`
- `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.php`
- `public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.php`
- `public/packages/inno_frontend/components/templates/default-page/default-page.php`
- `public/packages/inno_frontend/components/templates/view-page/view-page.php`

- [ ] **Step 1: Read all 8 files**

- [ ] **Step 2: Add strict_types + casts + @var to each file**

Key type mappings:
- `footer.php`: `$partnersJson` → `(string)`, `$partners` → `@var array<int, array{name: string, href: string, imageSrc: string}>` from json_decode
- `navigation-drawer.php`: `$itemsJson` → `(string)`, `$items` → `@var array<int, array<string, mixed>>` from json_decode; loop variables like `$level` → int, `$active` → bool
- `page-list-grid.php`: `$pagesJson` → `(string)`, `$pages` → `@var array<int, array{title: string, href: string, target: string, imageSrc: string, imageAlt: string}>` from json_decode
- All template files: all props are strings

- [ ] **Step 3: Verify no syntax errors**

```bash
for f in public/packages/inno_frontend/components/organisms/*/; do php -l "${f}"*.php; done
for f in public/packages/inno_frontend/components/templates/*/; do php -l "${f}"*.php; done
```

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/components/organisms/ \
        public/packages/inno_frontend/components/templates/
git commit -m "refactor(frontend): add strict_types, casts and @var to organism and template components"
```

---

### Task 6: Block Templates

**Files to modify:**
- `public/packages/inno_frontend/blocks/content/templates/innovationsbox/view.php`
- `public/packages/inno_frontend/blocks/feature_link/templates/innovationsbox/view.php`
- `public/packages/inno_frontend/blocks/page_list/templates/innovationsbox/view.php`

Files already with strict_types (verify casts are present):
- `public/packages/inno_frontend/blocks/autonav/templates/innovationsbox/view.php`
- `public/packages/inno_frontend/blocks/hero_image/templates/innovationsbox/view.php`

- [ ] **Step 1: Read all 5 block template files**

- [ ] **Step 2: Add strict_types + casts + @var to files missing them**

For block templates, the pattern is slightly different — they use CMS variables (`$controller`, `$pages`, etc.) and Facade calls. Add:
- `declare(strict_types=1)` after `<?php`, before `defined('C5_EXECUTE')` check
- `@var` annotations for CMS objects (e.g. `/** @var \Concrete\Core\Page\Page $page */`)
- `(string)` casts for string extractions from page attributes

For files that already have strict_types, audit for missing casts and add any needed `@var` annotations.

- [ ] **Step 3: Verify no syntax errors**

```bash
for f in public/packages/inno_frontend/blocks/*/templates/innovationsbox/view.php; do php -l "$f"; done
```

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/blocks/
git commit -m "refactor(frontend): add strict_types, casts and @var to block templates"
```

---

### Task 7: Theme Files

**Files to modify:**
- `public/packages/inno_frontend/themes/innovationsbox/default.php`
- `public/packages/inno_frontend/themes/innovationsbox/view.php`
- `public/packages/inno_frontend/themes/innovationsbox/page_forbidden.php`
- `public/packages/inno_frontend/themes/innovationsbox/page_not_found.php`
- `public/packages/inno_frontend/themes/innovationsbox/elements/header.php` (called `header_top.php` in elements)
- `public/packages/inno_frontend/themes/innovationsbox/elements/footer.php`
- `public/packages/inno_frontend/themes/innovationsbox/elements/footer_bottom.php`
- `public/packages/inno_frontend/themes/innovationsbox/elements/header_top.php`

Already has strict_types:
- `public/packages/inno_frontend/themes/innovationsbox/page_theme.php`

- [ ] **Step 1: Read all 9 theme files**

- [ ] **Step 2: Add strict_types + casts + @var to files missing them**

Theme files use CMS variables injected by the framework. Add `@var` annotations for framework objects and `(string)` casts for extracted values. The `header_top.php` file has many CMS objects — use proper Concrete CMS class FQCNs in annotations.

- [ ] **Step 3: Verify no syntax errors**

```bash
for f in public/packages/inno_frontend/themes/innovationsbox/*.php public/packages/inno_frontend/themes/innovationsbox/elements/*.php; do php -l "$f"; done
```

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/themes/
git commit -m "refactor(frontend): add strict_types, casts and @var to theme files"
```

---

### Task 8: Single Page Templates

**Files to modify (missing strict_types):**
- `public/packages/inno_frontend/single_pages/register.php`

**Files to audit (already have strict_types, check for missing casts):**
- `public/packages/inno_frontend/single_pages/login.php`
- `public/packages/inno_frontend/single_pages/account/edit_profile.php`
- `public/packages/inno_frontend/single_pages/account/avatar.php`
- `public/packages/inno_frontend/single_pages/members/profile.php`
- `public/packages/inno_frontend/single_pages/members/directory.php`

- [ ] **Step 1: Read all 6 single page files**

- [ ] **Step 2: Add strict_types to `register.php`, audit and add missing casts/@var to all files**

Single page templates use CMS form helpers, tokens, and user objects. Add `@var` with proper CMS class FQCNs.

- [ ] **Step 3: Verify no syntax errors**

```bash
for f in public/packages/inno_frontend/single_pages/*.php public/packages/inno_frontend/single_pages/**/*.php; do php -l "$f"; done
```

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/single_pages/
git commit -m "refactor(frontend): add strict_types, casts and @var to single page templates"
```

---

### Task 9: Domain Package Fix + PHPStan Config Update

**Files to modify:**
- `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php` — fix `getMemberIds()` return type
- `phpstan.neon` — add frontend paths
- `phpstan-baseline.neon` — regenerate after changes

- [ ] **Step 1: Fix `ProjectSpace::getMemberIds()` return type**

In `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php`, change:
```php
public function getMemberIds(): array
```
To:
```php
/** @return list<string> */
public function getMemberIds(): array
```

- [ ] **Step 2: Add frontend component paths to PHPStan config**

In `phpstan.neon`, add to `paths:`:
```yaml
        - public/packages/inno_frontend/src
        - public/packages/inno_frontend/controllers
```

Note: Component partials (atoms, molecules, etc.) are template files with loose variables — PHPStan Level 6 may produce many false positives for these. Start with only `src/` and `controllers/` which contain proper classes.

- [ ] **Step 3: Run PHPStan to verify**

```bash
composer lint:php
```

Expected: passes (or only baseline items remain).

- [ ] **Step 4: Regenerate baseline if needed**

If new errors appear from the added paths:
```bash
./vendor/bin/phpstan analyse --generate-baseline
```

Review the baseline entries — fix what's fixable, baseline the rest.

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php phpstan.neon phpstan-baseline.neon
git commit -m "refactor: fix ProjectSpace return type and add frontend to PHPStan"
```

---

### Task 10: Final Verification

- [ ] **Step 1: Run PHP syntax check on all modified files**

```bash
find public/packages/inno_frontend -name '*.php' -exec php -l {} \; 2>&1 | grep -v "No syntax errors"
```

Expected: no output (all files pass).

- [ ] **Step 2: Run PHPStan**

```bash
composer lint:php
```

Expected: passes.

- [ ] **Step 3: Run existing tests**

```bash
composer test
```

Expected: all tests pass.

- [ ] **Step 4: Run frontend build**

```bash
npm run development
```

Expected: build succeeds (PHP template changes don't affect webpack, but confirms nothing is broken).

- [ ] **Step 5: Spot-check Storybook renders**

```bash
npm run storybook
```

Open browser, verify components still render correctly. The type casts should not change runtime behavior for valid prop values.
