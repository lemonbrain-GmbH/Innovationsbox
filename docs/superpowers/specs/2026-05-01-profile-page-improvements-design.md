# Profile Page Improvements — Design Spec

## Goal

Improve the visual quality of the member profile page (`/members/profile/view/:id`) by redesigning the header/avatar area and the attributes section. The overall page structure, back button, badge, action buttons, and CMS area remain unchanged.

## Changes

### 1. Avatar: New `2xl` Size Variant

Add a `2xl` size to the avatar atom component (`avatar.scss` + `avatar.variants.php`):

- **Dimensions:** 120px x 120px
- **Font size (initials):** 48px
- **Usage:** Profile pages only — the existing sizes (xs/sm/md/lg/xl) stay as-is

The profile template switches from `size => 'xl'` (64px) to `size => '2xl'` (120px).

### 2. Header Layout Improvements

File: `resources/css/single_pages/members-profile.scss`

- Increase gap between avatar and meta from `--space-6` to `--space-8`
- Name (`__name`): use `text-2xl` fluid typography (already fixed from earlier work)
- Join date (`__joined`): add `--space-2` top margin for breathing room below badge/role
- Mobile breakpoint (<=480px): stack vertically, center-align. The 120px avatar becomes the hero.

File: `public/packages/inno_frontend/single_pages/members/profile.php`

- Change avatar props: `'size' => '2xl'`

### 3. Attributes Section Redesign

**HTML fix:** The current template wraps `<dt>` and `<dd>` inside a `<div>` without a `<dl>` parent. Fix by switching to semantic `<div>` pairs — the styling is custom anyway, so `<dl>`/`<dt>`/`<dd>` adds no accessibility value here beyond what ARIA or plain markup provides.

**Layout:** Replace the grid layout with a flowing two-column layout using CSS columns or grid with `auto-fill`, single column on mobile.

**Typography:**
- Label: keep small, uppercase, secondary color, semibold — but reduce letter-spacing slightly (0.04em instead of 0.05em)
- Value: `text-base` fluid typography, primary color, regular weight
- Empty values ("Keine Angabe"): add `.member-profile__attribute-value--empty` modifier with muted color and italic style

**Spacing:**
- Remove the `border-top` separator — use `--space-8` top margin for visual separation instead
- Each attribute item gets `--space-4` bottom margin

### Files Changed

| File | Change |
|---|---|
| `public/packages/inno_frontend/components/atoms/avatar/avatar.scss` | Add `&--2xl` size (120px) |
| `public/packages/inno_frontend/components/atoms/avatar/avatar.variants.php` | Add `'2xl'` to sizes array |
| `public/packages/inno_frontend/single_pages/members/profile.php` | Avatar `size => '2xl'`, fix `<dt>`/`<dd>` markup, add empty-value modifier |
| `resources/css/single_pages/members-profile.scss` | Header spacing, attribute layout redesign, empty-value style |

### What Stays the Same

- Back button ("Zurück zum Verzeichnis") at top
- Badge component for group/role
- Action buttons (edit profile / send message)
- Section title "Profilinformationen"
- CMS `Area('Main')` at bottom
- Overall max-width 800px container
- Mobile stacking behavior (just with larger avatar now)
