# Accessibility Infrastructure — Sub-project 2

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Establish accessibility infrastructure for the design system: axe-core in Storybook, reusable focus-visible/reduced-motion/touch-target mixins, global reduced-motion safety rule.

## Architecture

Install `@storybook/addon-a11y` so axe-core runs automatically on every story. Create `resources/css/helper/_a11y.scss` with three SCSS mixins and one global `@media (prefers-reduced-motion)` rule. Load the a11y helper in `main.scss` so the global rule applies site-wide. Components opt in to focus/touch-target mixins via `@use "~@helpers/a11y"`.

## Changes

### 1. Install axe-core addon

```bash
npm install --save-dev @storybook/addon-a11y@8.6.18
```

Version must match existing Storybook (8.6.18).

### 2. Register addon in `.storybook/main.js`

Add `@storybook/addon-a11y` to the `addons` array:

```js
addons: [
  '@storybook/addon-essentials',
  '@storybook/addon-links',
  '@storybook/addon-a11y',
],
```

### 3. Configure a11y rules in `.storybook/preview.js`

Add `a11y` parameters to the preview. Disable document-level rules that produce false positives when rendering isolated component fragments:

```js
const preview = {
  parameters: {
    server: { url: 'http://localhost:6007' },
    a11y: {
      config: {
        rules: [
          { id: 'landmark-one-main', enabled: false },
          { id: 'page-has-heading-one', enabled: false },
          { id: 'region', enabled: false },
        ],
      },
      manual: false,
    },
    controls: { /* existing */ },
  },
};
```

Individual stories that render full pages can re-enable these rules via story-level `parameters.a11y.config`.

### 4. Create `resources/css/helper/_a11y.scss`

Three mixins plus global reduced-motion safety rule:

```scss
// Accessibility utilities — mixins for focus, motion, touch targets

// Focus-visible ring using semantic token. Only shows on keyboard focus.
@mixin focus-visible-ring {
  &:focus {
    outline: none;
  }

  &:focus-visible {
    outline: 2px solid var(--color-border-focus);
    outline-offset: 2px;
    border-radius: var(--radius-sm);
  }
}

// Wrap animations/transitions so they respect prefers-reduced-motion.
@mixin respect-motion {
  @content;

  @media (prefers-reduced-motion: reduce) {
    transition: none !important;
    animation: none !important;
  }
}

// Ensure minimum 44x44 touch target for small interactive elements.
@mixin touch-target {
  min-width: 44px;
  min-height: 44px;
  position: relative;
}

// Global safety rule: kill all non-essential animation for users
// who prefer reduced motion. Applied site-wide via main.scss.
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

### 5. Load a11y helper in `main.scss`

Add `@use "helper/a11y";` to `resources/css/main.scss` after the global imports, so the global reduced-motion rule applies site-wide.

## Usage (for future components)

Components import the helper and use the mixins:

```scss
@use "~@helpers/a11y" as a11y;

.my-button {
  @include a11y.focus-visible-ring;
  @include a11y.touch-target;

  @include a11y.respect-motion {
    transition: background-color var(--duration-normal) var(--ease-standard);
  }
}
```

The `~@helpers` alias (set up in webpack and Storybook configs) resolves to `resources/css/helper/`.

## What Doesn't Change

- Existing components (no retrofit in this sub-project; they can adopt mixins later)
- Token files
- Webpack configuration
- Component PHP/TS files

## Verification

1. `npm run development` — builds successfully
2. `npm run storybook` — Storybook loads, "Accessibility" tab appears next to Controls
3. Pick any story, open Accessibility tab — shows axe-core scan results
4. Emulate `prefers-reduced-motion: reduce` in DevTools — animations on the page suppress (test with a story that has animations, e.g. navigation-drawer toggle)
5. Tab through Storybook preview — focus ring only appears on keyboard focus, not on mouse click (after components adopt `focus-visible-ring`)
6. Component SCSS can `@use "~@helpers/a11y"` without errors (test in a future component)
