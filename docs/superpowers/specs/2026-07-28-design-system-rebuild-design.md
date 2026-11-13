# Design System Rebuild — Figma-Driven Redesign

**Date:** 2026-07-28
**Source:** [Figma — innovationsbox](https://www.figma.com/design/GiNRb1GmUY8Ac7LZcE0e9X/innovationsbox?node-id=0-1&m=dev) (Frame 2 / node 7:10)
**Approach:** Token-first, ripple outward (update tokens, then cascade through components)

## Summary

Rebuild and improve the design system based on the Figma redesign. The Figma is the source of truth. Key changes: dark header, Open Sans font, compact project cards without descriptions, new footer styling, floating action button on all pages, and renaming "Projekträume" to "Innovationen". Pages not shown in Figma are adapted to feel consistent with the new design language.

## 1. Token Updates (`resources/tokens/tokens.json`)

### Font Family

- Replace `Inter` with `Open Sans` everywhere in `tokens.json`
- Update the `@font-face` / Google Fonts import in `resources/css/_fonts.scss` (or equivalent)
- Weight mappings remain: 300 (Light), 400 (Regular), 500 (Medium), 600 (SemiBold), 700 (Bold)

### Color Adjustments

Update/add these semantic tokens:

| Token | Old Value | New Value |
|---|---|---|
| `header-bg` | (new) | `rgba(0, 0, 0, 0.8)` |
| `footer-bg` | `brand-secondary-500` (#89705B) | `rgba(121, 105, 77, 0.7)` |
| `card-border` | (new) | `#e4e4e4` |

Keep unchanged: brand-primary (green), brand-secondary (brown), accent (sage), neutral scale, status colors, all spacing/radius/shadow/z-index/motion/breakpoint tokens.

### New Tokens

| Token | Value | Purpose |
|---|---|---|
| `header-shadow-height` | `34px` | Gradient depth effect below header |
| `fab-size` | `87px` | Floating action button dimensions |
| `fab-bg` | `brand-primary-500` | FAB background color |

## 2. Header Redesign (`organisms/header/`)

### Visual Changes

- **Background:** `rgba(0, 0, 0, 0.8)` (near-black, slight transparency)
- **Height:** ~115px (matches Figma)
- **Logo:** Circular icon (~54px, extract SVG asset from Figma node `2:80`/`7:177`) + "innovationsbox" text, both white. The icon appears to be a rounded square with a leaf/plant motif inside a circle. The exact asset must be downloaded from Figma during implementation.
- **Hamburger menu:** White icon, right-aligned (same position as current)
- **All text/links in header:** White for contrast

### Depth Gradient

Replace the current dual box-shadow (`inset 0 -4px primary, inset 0 -8px secondary`) with:

```scss
.page-header::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  height: var(--header-shadow-height, 34px);
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.15), transparent);
  pointer-events: none;
  z-index: var(--z-index-sticky);
}
```

### Component Impact

- Skip-link: adjust focus colors for dark background
- Auth elements in header (login link, user-menu avatar): white/light treatment
- Notification badges: `error-600` red still contrasts well against dark bg
- Navigation drawer trigger: white icon; drawer panel itself stays light

## 3. Project Card — Compact Layout (`molecules/project-card/`)

### Structure (top to bottom)

1. **Image** — Full-width, contained within 20px rounded corners (overflow hidden)
2. **Badges** — Category + status pills (e.g. "Wohnen", "Aktiv") below image
3. **Title** — Bold, no description text
4. **Meta row** — "2 Mitglieder" (people icon) + "Erstellt: 15.3.26" separated by divider
5. **Footer row** — Initiator avatar (46px circle) + name/role on left, action buttons on right

### Styling

- **Container:** 20px border-radius, 2px solid `#e4e4e4` border, no box-shadow, no accent bar gradient
- **Hover:** Subtle lift (`translateY(-2px)` + shadow elevation)
- **Description:** Hidden by default. Add `showDescription` prop to `.variants.php` (default `false`) for future re-enablement

### Action Buttons

- "Mitmachen" — primary button style
- "Mehr erfahren" — secondary/ghost button style (shown on some cards)

## 4. Footer (`organisms/footer/`)

### Visual Changes

- **Background:** `rgba(121, 105, 77, 0.7)` (warm brown with transparency, softer than current solid #89705B)
- **Font:** Open Sans (cascades from token change)
- **Attribution text:** 15px, centered, white — "Diese Innovationsbox ist ein Engagement der arento ag für die Klimaregion Glarnerland und den Verein KlimaGlarus.ch" with underlined links to arento.ch and klimaglarus.ch
- **Partner logos:** Keep current rendering, ensure white/light versions. Centered layout with fluid gap

### No Structural Changes

The footer layout stays the same — only the background color and font change.

## 5. FAB — Floating Action Button (New Component)

### New Atom: `atoms/fab/`

- **Position:** `position: fixed; bottom: 24px; right: 24px;`
- **Size:** 87px circle (matching Figma)
- **Appearance:** `brand-primary-500` green background, white "+" icon, `shadow-md`
- **Hover:** Scale up slightly + `brand-primary-600` darker green
- **Z-index:** `1000` (dropdown level — above content, below modals/overlays)
- **Accessibility:** `aria-label="Neue Projektidee einreichen"`, respects `prefers-reduced-motion` for hover animation
- **Link target:** Idea submission page

### Placement

- Added to the base page template (`default-page` template or `page_theme.php`) so it appears on **every page**
- Visible to **all users** (logged in or not)
- If user is not logged in, clicking redirects to login

### Replaces

The current "Projektidee einreichen" button in the Projekträume/Innovationen page header section is removed.

## 6. Rename "Projekträume" → "Innovationen"

### Code Changes (handled in implementation)

| What | From | To |
|---|---|---|
| Page title | "Projekträume" | "Innovationen" |
| Subtitle | "Laufende und abgeschlossene Klimaprojekte..." | "Alle laufenden und abgeschlossenen Innovationen unserer Community" |
| URL path | `/projektraeume` | `/innovationen` |
| File: single page template | `projektraeume.php` | `innovationen.php` |
| File: SCSS | `projektraeume.scss` | `innovationen.scss` |
| File: TypeScript | `projektraeume.ts` | `innovationen.ts` |
| Search placeholder | "Projekte durchsuchen..." | "Innovationen durchsuchen..." |
| Code references/comments | "Projekträume" | "Innovationen" |

### CMS/Database Changes (manual — see checklist below)

These cannot be done in code and require CMS admin action.

## 7. Adapting Non-Figma Pages

### Automatic (cascades from token/component updates)

- Open Sans font everywhere
- Updated color tokens (buttons, links, badges, form fields)
- Dark header + gradient shadow on all pages
- New footer styling on all pages
- FAB on all pages

### Needs Targeted Attention

| Page | What to Check |
|---|---|
| Login/Register | Auth-card against neutral bg with new font/colors |
| Projektraum detail | Sidebar, status-update-timeline, member-cards harmonize with dark header |
| Moderation pages | Table/list views with neutral tokens |
| Profile/Account | Form-heavy pages, form-field molecules |
| Idea submission | Form + CKEditor styling |
| Hero image (homepage) | Interaction with dark header |

No structural redesigns for these pages — only color/font/spacing adjustments where the token cascade doesn't fully cover it.

---

## CMS/Database Actions Checklist (Manual)

After implementation is deployed, perform these actions in the CMS Dashboard:

- [ ] **Rename single page:** Dashboard → Sitemap → find "Projekträume" page → change name to "Innovationen" and path to `/innovationen`
- [ ] **Update navigation menu labels:** Any menu items referencing "Projekträume" → "Innovationen"
- [ ] **Update content block links:** Any content blocks on other pages that link to `/projektraeume` → update to `/innovationen`
- [ ] **Verify partner logos:** Ensure white/light versions of partner logos are uploaded for the new semi-transparent footer background
