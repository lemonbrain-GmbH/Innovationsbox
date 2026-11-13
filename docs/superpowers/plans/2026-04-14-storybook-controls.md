# Storybook Controls — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `argTypes` and `args` to all 24 story files so every prop is an interactive Storybook control.

**Architecture:** Add component-level `argTypes` for control types. Convert `parameters.server.params` to `args` (Storybook merges args into server fetch params automatically). Keep `parameters.server.id` for component identification.

**Tech Stack:** JSON (Storybook stories), `@storybook/server-webpack5`

---

### Task 1: Add controls to atom stories (8 files)

**Files:**
- Modify: `public/packages/inno_frontend/components/atoms/button/button.stories.json`
- Modify: `public/packages/inno_frontend/components/atoms/container/container.stories.json`
- Modify: `public/packages/inno_frontend/components/atoms/heading/heading.stories.json`
- Modify: `public/packages/inno_frontend/components/atoms/image/image.stories.json`
- Modify: `public/packages/inno_frontend/components/atoms/link/link.stories.json`
- Modify: `public/packages/inno_frontend/components/atoms/overlay/overlay.stories.json`
- Modify: `public/packages/inno_frontend/components/atoms/paragraph/paragraph.stories.json`
- Modify: `public/packages/inno_frontend/components/atoms/skip-link/skip-link.stories.json`

- [ ] **Step 1: Replace all 8 atom story files**

Replace `button.stories.json`:
```json
{
  "title": "Atoms/Button",
  "argTypes": {
    "variant": { "control": { "type": "select" }, "options": ["primary", "secondary"] },
    "size": { "control": { "type": "select" }, "options": ["default", "small", "large"] },
    "label": { "control": "text" },
    "href": { "control": "text" }
  },
  "stories": [
    { "name": "Primary", "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Secondary", "args": { "variant": "secondary" }, "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Small", "args": { "size": "small" }, "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Large", "args": { "size": "large" }, "parameters": { "server": { "id": "atoms/button" } } }
  ]
}
```

Replace `container.stories.json`:
```json
{
  "title": "Atoms/Container",
  "argTypes": {
    "variant": { "control": { "type": "select" }, "options": ["default", "medium", "small", "narrow"] },
    "spaced": { "control": { "type": "select" }, "options": ["true", "false"] },
    "content": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/container" } } },
    { "name": "Medium", "args": { "variant": "medium" }, "parameters": { "server": { "id": "atoms/container" } } },
    { "name": "Narrow", "args": { "variant": "narrow" }, "parameters": { "server": { "id": "atoms/container" } } },
    { "name": "Spaced", "args": { "spaced": "true" }, "parameters": { "server": { "id": "atoms/container" } } }
  ]
}
```

Replace `heading.stories.json`:
```json
{
  "title": "Atoms/Heading",
  "argTypes": {
    "level": { "control": { "type": "select" }, "options": ["1", "2", "3", "4", "5", "6"] },
    "text": { "control": "text" }
  },
  "stories": [
    { "name": "H1", "args": { "level": "1" }, "parameters": { "server": { "id": "atoms/heading" } } },
    { "name": "H2", "parameters": { "server": { "id": "atoms/heading" } } },
    { "name": "H3", "args": { "level": "3" }, "parameters": { "server": { "id": "atoms/heading" } } },
    { "name": "H4", "args": { "level": "4" }, "parameters": { "server": { "id": "atoms/heading" } } }
  ]
}
```

Replace `image.stories.json`:
```json
{
  "title": "Atoms/Image",
  "argTypes": {
    "src": { "control": "text" },
    "alt": { "control": "text" },
    "ratio": { "control": { "type": "select" }, "options": ["auto", "16/9", "3/2", "4/3", "1/1"] },
    "fit": { "control": { "type": "select" }, "options": ["cover", "contain"] },
    "rounded": { "control": { "type": "select" }, "options": ["true", "false"] },
    "width": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/image" } } },
    { "name": "Rounded 3:2", "args": { "ratio": "3/2", "rounded": "true" }, "parameters": { "server": { "id": "atoms/image" } } },
    { "name": "Square Contain", "args": { "ratio": "1/1", "fit": "contain", "width": "200px" }, "parameters": { "server": { "id": "atoms/image" } } }
  ]
}
```

Replace `link.stories.json`:
```json
{
  "title": "Atoms/Link",
  "argTypes": {
    "text": { "control": "text" },
    "href": { "control": "text" },
    "target": { "control": { "type": "select" }, "options": ["_self", "_blank"] }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/link" } } },
    { "name": "External", "args": { "text": "KlimaGlarus.ch", "href": "https://klimaglarus.ch", "target": "_blank" }, "parameters": { "server": { "id": "atoms/link" } } }
  ]
}
```

Replace `overlay.stories.json`:
```json
{
  "title": "Atoms/Overlay",
  "argTypes": {
    "variant": { "control": { "type": "select" }, "options": ["light", "dark"] },
    "content": { "control": "text" }
  },
  "stories": [
    { "name": "Light", "parameters": { "server": { "id": "atoms/overlay" } } },
    { "name": "Dark", "args": { "variant": "dark" }, "parameters": { "server": { "id": "atoms/overlay" } } }
  ]
}
```

Replace `paragraph.stories.json`:
```json
{
  "title": "Atoms/Paragraph",
  "argTypes": {
    "variant": { "control": { "type": "select" }, "options": ["body", "lead", "small"] },
    "text": { "control": "text" }
  },
  "stories": [
    { "name": "Body", "parameters": { "server": { "id": "atoms/paragraph" } } },
    { "name": "Lead", "args": { "variant": "lead" }, "parameters": { "server": { "id": "atoms/paragraph" } } },
    { "name": "Small", "args": { "variant": "small" }, "parameters": { "server": { "id": "atoms/paragraph" } } }
  ]
}
```

Replace `skip-link.stories.json`:
```json
{
  "title": "Atoms/Skip Link",
  "argTypes": {
    "text": { "control": "text" },
    "target": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/skip-link" } } }
  ]
}
```

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/
git commit -m "feat: add Storybook controls to atom stories"
```

---

### Task 2: Add controls to molecule stories (6 files)

**Files:**
- Modify: `public/packages/inno_frontend/components/molecules/accent-text/accent-text.stories.json`
- Modify: `public/packages/inno_frontend/components/molecules/feature-button/feature-button.stories.json`
- Modify: `public/packages/inno_frontend/components/molecules/nav-item/nav-item.stories.json`
- Modify: `public/packages/inno_frontend/components/molecules/page-card/page-card.stories.json`
- Modify: `public/packages/inno_frontend/components/molecules/partner-logo/partner-logo.stories.json`
- Modify: `public/packages/inno_frontend/components/molecules/question-text/question-text.stories.json`

- [ ] **Step 1: Replace all 6 molecule story files**

Replace `accent-text.stories.json`:
```json
{
  "title": "Molecules/Accent Text",
  "argTypes": {
    "text": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/accent-text" } } }
  ]
}
```

Replace `feature-button.stories.json`:
```json
{
  "title": "Molecules/Feature Button",
  "argTypes": {
    "label": { "control": "text" },
    "href": { "control": "text" },
    "icon": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/feature-button" } } },
    { "name": "With Icon", "args": { "icon": "↓" }, "parameters": { "server": { "id": "molecules/feature-button" } } }
  ]
}
```

Replace `nav-item.stories.json`:
```json
{
  "title": "Molecules/Nav Item",
  "argTypes": {
    "text": { "control": "text" },
    "href": { "control": "text" },
    "active": { "control": { "type": "select" }, "options": ["true", "false"] },
    "level": { "control": { "type": "select" }, "options": ["1", "2"] }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/nav-item" } } },
    { "name": "Active", "args": { "active": "true" }, "parameters": { "server": { "id": "molecules/nav-item" } } }
  ]
}
```

Replace `page-card.stories.json`:
```json
{
  "title": "Molecules/Page Card",
  "argTypes": {
    "title": { "control": "text" },
    "href": { "control": "text" },
    "target": { "control": { "type": "select" }, "options": ["_self", "_blank"] },
    "imageSrc": { "control": "text" },
    "imageAlt": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/page-card" } } },
    { "name": "External Link", "args": { "target": "_blank", "href": "https://klimaglarus.ch" }, "parameters": { "server": { "id": "molecules/page-card" } } }
  ]
}
```

Replace `partner-logo.stories.json`:
```json
{
  "title": "Molecules/Partner Logo",
  "argTypes": {
    "name": { "control": "text" },
    "href": { "control": "text" },
    "imageSrc": { "control": "text" }
  },
  "stories": [
    { "name": "arento", "parameters": { "server": { "id": "molecules/partner-logo" } } },
    { "name": "KlimaGlarus", "args": { "name": "KlimaGlarus.ch", "href": "https://www.klimaglarus.ch/", "imageSrc": "https://placehold.co/120x60/FFF/B7CD57?text=KlimaGlarus" }, "parameters": { "server": { "id": "molecules/partner-logo" } } }
  ]
}
```

Replace `question-text.stories.json`:
```json
{
  "title": "Molecules/Question Text",
  "argTypes": {
    "text": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/question-text" } } }
  ]
}
```

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/
git commit -m "feat: add Storybook controls to molecule stories"
```

---

### Task 3: Add controls to organism stories (6 files)

**Files:**
- Modify: `public/packages/inno_frontend/components/organisms/content-block/content-block.stories.json`
- Modify: `public/packages/inno_frontend/components/organisms/footer/footer.stories.json`
- Modify: `public/packages/inno_frontend/components/organisms/header/header.stories.json`
- Modify: `public/packages/inno_frontend/components/organisms/hero-image/hero-image.stories.json`
- Modify: `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.stories.json`
- Modify: `public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.stories.json`

- [ ] **Step 1: Replace all 6 organism story files**

Replace `content-block.stories.json`:
```json
{
  "title": "Organisms/Content Block",
  "argTypes": {
    "content": { "control": "text" }
  },
  "stories": [
    { "name": "Plain Text", "parameters": { "server": { "id": "organisms/content-block" } } },
    { "name": "With Accent Quote", "args": { "content": "<p>Unser Motto:</p><span class=\"accent\">Die Innovationsbox stellt digitale Räume zur Verfügung, in welchen sich innovative Entwicklerinnen mit anderen Menschen treffen können, um klimafreundliche Ideen gemeinsam zu entwickeln und zum Erfolg zu bringen.</span>" }, "parameters": { "server": { "id": "organisms/content-block" } } },
    { "name": "With FAQ Question", "args": { "content": "<span class=\"question\">Welche Projektideen werden in dieser Box bearbeitet?</span><p>Es dürfen alle Projektideen eingereicht werden, welche dem Klimaschutz helfen.</p>" }, "parameters": { "server": { "id": "organisms/content-block" } } }
  ]
}
```

Replace `footer.stories.json`:
```json
{
  "title": "Organisms/Footer",
  "argTypes": {
    "text": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "organisms/footer" } } }
  ]
}
```

Replace `header.stories.json`:
```json
{
  "title": "Organisms/Header",
  "argTypes": {
    "logoSrc": { "control": "text" },
    "logoAlt": { "control": "text" },
    "logoHref": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "organisms/header" } } }
  ]
}
```

Replace `hero-image.stories.json`:
```json
{
  "title": "Organisms/Hero Image",
  "argTypes": {
    "title": { "control": "text" },
    "body": { "control": "text" },
    "imageSrc": { "control": "text" },
    "imageAlt": { "control": "text" },
    "buttonLabel": { "control": "text" },
    "buttonHref": { "control": "text" }
  },
  "stories": [
    { "name": "With Button", "parameters": { "server": { "id": "organisms/hero-image" } } },
    { "name": "Without Button", "args": { "buttonLabel": "" }, "parameters": { "server": { "id": "organisms/hero-image" } } }
  ]
}
```

Replace `navigation-drawer.stories.json`:
```json
{
  "title": "Organisms/Navigation Drawer",
  "argTypes": {
    "logoSrc": { "control": "text" },
    "logoAlt": { "control": "text" },
    "logoHref": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "organisms/navigation-drawer" } } }
  ]
}
```

Replace `page-list-grid.stories.json` (no controllable props — only JSON array):
```json
{
  "title": "Organisms/Page List Grid",
  "stories": [
    { "name": "Five Categories", "parameters": { "server": { "id": "organisms/page-list-grid" } } }
  ]
}
```

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/components/organisms/
git commit -m "feat: add Storybook controls to organism stories"
```

---

### Task 4: Add controls to template and page stories (4 files)

**Files:**
- Modify: `public/packages/inno_frontend/components/templates/default-page/default-page.stories.json`
- Modify: `public/packages/inno_frontend/components/templates/view-page/view-page.stories.json`
- Modify: `public/packages/inno_frontend/components/pages/homepage/homepage.stories.json`
- Modify: `public/packages/inno_frontend/components/pages/inner-page/inner-page.stories.json`

- [ ] **Step 1: Replace all 4 template/page story files**

Replace `default-page.stories.json`:
```json
{
  "title": "Templates/Default Page",
  "argTypes": {
    "logoSrc": { "control": "text" },
    "logoAlt": { "control": "text" },
    "pageHeaderContent": { "control": "text" },
    "mainContent": { "control": "text" },
    "footerText": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "templates/default-page" } } }
  ]
}
```

Replace `view-page.stories.json`:
```json
{
  "title": "Templates/View Page",
  "argTypes": {
    "heroTitle": { "control": "text" },
    "heroBody": { "control": "text" },
    "heroButtonLabel": { "control": "text" },
    "heroButtonHref": { "control": "text" },
    "mainContent": { "control": "text" },
    "footerText": { "control": "text" }
  },
  "stories": [
    { "name": "With Hero and Button", "parameters": { "server": { "id": "templates/view-page" } } },
    { "name": "Without Button", "args": { "heroButtonLabel": "" }, "parameters": { "server": { "id": "templates/view-page" } } }
  ]
}
```

Replace `homepage.stories.json`:
```json
{
  "title": "Pages/Homepage",
  "argTypes": {
    "heroTitle": { "control": "text" },
    "heroBody": { "control": "text" },
    "footerText": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "pages/homepage" } } }
  ]
}
```

Replace `inner-page.stories.json`:
```json
{
  "title": "Pages/Inner Page",
  "argTypes": {
    "pageTitle": { "control": "text" },
    "mainContent": { "control": "text" },
    "footerText": { "control": "text" }
  },
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "pages/inner-page" } } }
  ]
}
```

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/components/templates/ public/packages/inno_frontend/components/pages/
git commit -m "feat: add Storybook controls to template and page stories"
```

---

### Task 5: Verify controls work in Storybook

- [ ] **Step 1: Start Storybook**

```bash
php -S localhost:6007 storybook-server/index.php &
STORYBOOK_DISABLE_TELEMETRY=1 npx storybook dev -p 6006 --no-open --ci
```

- [ ] **Step 2: Verify controls panel appears**

Navigate to `http://localhost:6006/?path=/story/atoms-button--primary` and check:
- Controls panel shows 4 inputs: variant (select), size (select), label (text), href (text)
- Changing "variant" to "secondary" re-renders with secondary styling
- Changing "label" text re-renders with new label

- [ ] **Step 3: Verify a molecule**

Navigate to `http://localhost:6006/?path=/story/molecules-nav-item--active` and check:
- Controls show: text (text), href (text), active (select), level (select)
- "active" shows "true" selected

- [ ] **Step 4: Verify an organism**

Navigate to `http://localhost:6006/?path=/story/organisms-hero-image--with-button` and check:
- Controls show: title, body, imageSrc, imageAlt, buttonLabel, buttonHref (all text)
- Clearing "buttonLabel" hides the button
