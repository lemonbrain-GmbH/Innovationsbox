# Visual Regression Fixes — Sub-project 1

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Fix visual regressions so the local CMS frontend matches the reference site (https://innovationsbox.lemonbrain.ch/) exactly. CSS/template changes only — no backend logic changes.

## Reference vs Local Comparison

Reference screenshots saved to `audit/reference-*.png`, local to `audit/local-*.png`.

## Findings and Fixes

### V1 — CRITICAL: Logo missing in header

**Root cause:** The `navigation-drawer` component expects `logoSrc` from a site attribute (`logo`), but the reference site uses a CMS-uploaded file rendered inside the autonav template as `<div class="main-logo"><a><img></a></div>`.

**Fix:** The autonav `view.php` should get the logo from the page's header area or site config. However, since the logo is managed as a CMS file upload (visible at `/application/files/.../innobox_logo.svg`), the simplest approach is to read the site's logo file object. The current code already attempts this but the attribute name might be wrong. Alternatively, hardcode the logo path from the package icon or make the logo come from the theme.

**Decision:** The `header.php` theme element should render the logo (it's part of the header, not the autonav block). The reference site renders the logo inside the autonav's `<div class="nav-container">`, but it's actually placed there by the theme template, not the autonav block itself. We need to update `header.php` to wrap the autonav global area in a `<div class="lb-container">` and render the logo before it, matching the reference structure.

### V2 — MINOR: Nav button label mismatch

**Root cause:** Component uses `aria-label="Navigation öffnen"`. Reference uses `<span class="sr-only">Open menu</span>` inside the button (no aria-label).

**Fix:** Update `navigation-drawer.php` button to use `<span class="sr-only">` pattern matching reference. Keep the German text but match the structure.

### V3 — CRITICAL: HTML entities not decoded in nav items

**Root cause:** `nav-item.php` uses `htmlspecialchars($text)`, but Concrete CMS `$ni->name` is already HTML-escaped. Double encoding produces visible `&Uuml;` and `&auml;`.

**Fix:** In `nav-item.php`, output `$text` without `htmlspecialchars()`. The CMS already escapes it. Alternatively use `html_entity_decode()` then `htmlspecialchars()`, but since the CMS guarantees escaping, just output directly.

### V4 — CRITICAL: Sub-pages flat instead of nested

**Root cause:** The `navigation-drawer` component renders a flat `<ul>` of `<li>` items. The reference uses nested `<ul>` inside parent `<li>` elements for sub-pages. The autonav `view.php` passes `level` but the component ignores it for nesting.

**Fix:** Update `navigation-drawer.php` to handle hierarchical nav items. Use the `level` property to open/close nested `<ul>` elements, matching the core CMS autonav template's nesting logic. The `nav-item` molecule should not be used for this — the navigation-drawer should render its own `<li><a>` elements with nesting logic inline (the molecule is too simple for hierarchical nav).

### V5 — MODERATE: Extra "Home" link in nav

**Root cause:** The CMS autonav block includes the home page in its output. The reference site's autonav is configured to exclude the home page (via block settings in the CMS, not in code).

**Fix:** This is a CMS block configuration issue, not a code bug. The autonav block settings need to be configured to start from "Pages below this page" instead of "This page and sub-pages". However, we can add a code-level fix: filter out the home page in `view.php` if `$ni->isHome` is true.

### V6 — CRITICAL: Nav toggle button covered by hero image

**Root cause:** The reference wraps the nav in `<div class="lb-container">` which provides positioning context. On local, the nav-container has no wrapper, and the hero image (`position: relative`) paints over the `position: absolute` toggle button.

**Fix:** Two changes:
1. Wrap the autonav output in `<div class="lb-container">` (from `header.php` or from the component)
2. Add `z-index` to `.nav-container` to ensure it stays above the hero

### V7 — MINOR: Nav sub-link color differentiation missing

**Root cause:** The reference SCSS has `ul li a { color: var(--color-green) }` for child links. Our component SCSS was migrated but the nested `<ul>` never renders (V4), so the child-color rules don't apply.

**Fix:** Fixing V4 (nested lists) will automatically fix V7 since the SCSS selectors already target nested `ul li a`.

## Implementation Strategy

The core issue is that the `navigation-drawer` component and `autonav/view.php` need to produce HTML matching the reference structure. The changes are:

### 1. Update `header.php` theme element

Add `<div class="lb-container">` wrapper around the header nav global area, and render the logo before the autonav block — matching the reference structure:

```html
<header class="page-header">
    <a href="#main-content-area" class="skip-link visually-hidden"><?= t('Zum Hauptinhalt springen') ?></a>
    <div class="lb-container">
        <!-- Logo + autonav rendered here by global area -->
        <?php $a = new GlobalArea('Header Nav'); $a->display($c); ?>
    </div>
</header>
```

### 2. Update `navigation-drawer.php` component

Rewrite to match reference HTML structure:
- Logo as `<div class="main-logo"><a><img></a></div>`
- Button with `<span class="sr-only">` instead of `aria-label`
- Nav drawer as `<nav>` element instead of `<div>`
- Render items with hierarchical nesting using `level` property
- Nav overlay inside nav-container

### 3. Update `autonav/view.php`

- Filter out home page (`$ni->isHome`)
- Pass `hasSubmenu` and `subDepth` to support nesting
- Don't double-encode `$ni->name`

### 4. Update `nav-item.php` molecule

- Remove `htmlspecialchars()` on `$text` (already escaped by CMS)
- Or deprecate this molecule for autonav use and handle items inline in navigation-drawer

### 5. Update `navigation-drawer.scss`

- Add `z-index` to `.nav-container` to fix overlay issue
- Verify child-link color rules match reference

## What Doesn't Change

- Hero image component (no changes)
- Footer component (matches reference)
- Content blocks (match reference)
- Page list grid (matches reference)
- Feature buttons (match reference)
- No backend/PHP logic changes beyond template fixes

## Verification

1. Compare local homepage screenshot against `audit/reference-homepage-full.png`
2. Open nav drawer — logo visible, items properly nested, no HTML entities
3. Nav toggle clickable (not covered by hero)
4. Inner pages match reference layout
5. Storybook still renders navigation-drawer component
