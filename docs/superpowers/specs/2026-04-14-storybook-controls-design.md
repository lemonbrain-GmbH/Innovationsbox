# Storybook Controls — Sub-project 3

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Add `argTypes` and `args` to all 24 component story files so every prop is exposed as an interactive Storybook control.

## How It Works

`@storybook/server-webpack5` JSON stories support `args` and `argTypes` natively. When a user changes a control, the new value is sent as a URL query parameter to the PHP server, which re-renders the component with the updated prop.

- `argTypes` at component level define the control type for each prop
- `args` at story level provide default values (replacing `parameters.server.params`)
- `parameters.server.id` still required to identify the component

## Control Type Mapping

| Prop type | Control | Examples |
|---|---|---|
| Enum (fixed options) | `{ "control": { "type": "select" }, "options": [...] }` | variant, size, level, fit, ratio, target |
| Boolean-as-string | `{ "control": { "type": "select" }, "options": ["true", "false"] }` | active, spaced, rounded |
| Free text | `{ "control": "text" }` | label, text, href, alt |
| HTML content | `{ "control": "text" }` | content, body |
| JSON arrays | No control (too complex) | items, pages, partners, navItems |

## Changes Per Component

### Atoms (8)

**button:** argTypes for `variant` (select: primary/secondary), `size` (select: default/small/large), `label` (text), `href` (text)

**container:** argTypes for `variant` (select: default/medium/small/narrow), `spaced` (select: true/false), `content` (text)

**heading:** argTypes for `level` (select: 1-6), `text` (text)

**image:** argTypes for `src` (text), `alt` (text), `ratio` (select), `fit` (select), `rounded` (select: true/false), `width` (text)

**link:** argTypes for `text` (text), `href` (text), `target` (select: _self/_blank)

**overlay:** argTypes for `variant` (select: light/dark), `content` (text)

**paragraph:** argTypes for `variant` (select: body/lead/small), `text` (text)

**skip-link:** argTypes for `text` (text), `target` (text)

### Molecules (6)

**accent-text:** argTypes for `text` (text)

**feature-button:** argTypes for `label` (text), `href` (text), `icon` (text)

**nav-item:** argTypes for `text` (text), `href` (text), `active` (select: true/false), `level` (select: 1/2)

**page-card:** argTypes for `title` (text), `href` (text), `target` (select), `imageSrc` (text), `imageAlt` (text)

**partner-logo:** argTypes for `name` (text), `href` (text), `imageSrc` (text)

**question-text:** argTypes for `text` (text)

### Organisms (6)

**content-block:** argTypes for `content` (text)

**footer:** argTypes for `text` (text). No control for `partners` (JSON array).

**header:** argTypes for `logoSrc` (text), `logoAlt` (text), `logoHref` (text). No control for `navItems` (JSON array).

**hero-image:** argTypes for `title` (text), `body` (text), `imageSrc` (text), `imageAlt` (text), `buttonLabel` (text), `buttonHref` (text)

**navigation-drawer:** argTypes for `logoSrc` (text), `logoAlt` (text), `logoHref` (text). No control for `items` (JSON array).

**page-list-grid:** No controls (only prop is `pages` JSON array).

### Templates (2) + Pages (2)

**default-page:** argTypes for `logoSrc` (text), `logoAlt` (text), `pageHeaderContent` (text), `mainContent` (text), `footerText` (text). No control for `navItems`, `footerPartners`.

**view-page:** argTypes for `heroTitle` (text), `heroBody` (text), `heroButtonLabel` (text), `mainContent` (text), `footerText` (text). No control for JSON arrays.

**homepage:** argTypes for `heroTitle` (text), `heroBody` (text), `footerText` (text). No control for JSON arrays.

**inner-page:** argTypes for `pageTitle` (text), `mainContent` (text), `footerText` (text). No control for JSON arrays.

## What Doesn't Change

- Component PHP partials, SCSS, TS
- `_fixtures/sample-data.php`
- `variants.php` files
- Storybook config files

## Verification

Open Storybook, click any component. The Controls panel shows interactive inputs. Changing a text or select control re-renders the component via the PHP server with the new value.
