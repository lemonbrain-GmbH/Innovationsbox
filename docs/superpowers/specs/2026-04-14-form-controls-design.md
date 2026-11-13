# Form Control Atoms — Sub-project 4

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Add 6 stateless form control atoms (Input, Textarea, Checkbox, Radio, Toggle, Select) using design tokens and a11y helpers from prior sub-projects.

## Architecture

All 6 atoms are stateless PHP partials with consistent prop API (`name`, `id`, `value`, `disabled`, `required`, `invalid`, `describedBy`) plus type-specific extras. All use native HTML form elements (no custom dropdowns or hidden state machines). Custom visual styling sits on top of native inputs, preserving keyboard accessibility, screen reader support, and mobile native pickers.

## Common props (all 6 controls)

| Prop | Type | Required | Default | Notes |
|---|---|---|---|---|
| `name` | text | yes | — | Form field name |
| `id` | text | yes | — | For label association via `for` attribute |
| `value` | text | no | "" | Current value |
| `disabled` | "true"/"false" | no | "false" | Disabled state |
| `required` | "true"/"false" | no | "false" | Required field |
| `invalid` | "true"/"false" | no | "false" | Sets `aria-invalid` |
| `describedBy` | text | no | "" | ID of associated hint/error for `aria-describedby` |

## Per-component specs

### Input (`atoms/input/`)

**Type-specific props:**
- `type` — select: `text`, `email`, `password`, `number`, `search`, `tel`, `url`, `date` (default: `text`)
- `placeholder` — text
- `size` — select: `sm`, `md`, `lg` (default: `md`)
- `autocomplete` — text (e.g. `email`, `current-password`, `off`)

**HTML output:**
```html
<input
  type="text"
  id="email"
  name="email"
  value=""
  class="input input--md"
  placeholder="..."
  autocomplete="email"
  required
  aria-invalid="false"
  aria-describedby="email-hint"
>
```

**SCSS:**
- Border: `1px solid var(--color-border-default)`
- Padding: size-dependent (sm: `space-2 space-3`, md: `space-3 space-4`, lg: `space-4 space-5`)
- Background: `var(--color-bg-surface)`
- Color: `var(--color-text-primary)`
- Border-radius: `var(--radius-md)`
- `:hover` → `border-color: var(--color-border-strong)`
- `:focus-visible` → `focus-visible-ring` mixin
- `[aria-invalid="true"]` → `border-color: var(--color-action-danger)`
- `:disabled` → `background: var(--color-bg-subtle); color: var(--color-text-disabled); cursor: not-allowed`

### Textarea (`atoms/textarea/`)

**Type-specific props:**
- `placeholder` — text
- `rows` — number (default 4)
- `resize` — select: `none`, `vertical`, `horizontal`, `both` (default `vertical`)

**HTML output:**
```html
<textarea
  id="message"
  name="message"
  rows="4"
  class="textarea"
  placeholder="..."
  required
  aria-invalid="false"
></textarea>
```

**SCSS:**
- Same border/padding/focus/error/disabled as Input (md size)
- `font: inherit; font-family: var(--text-font-family);`
- `min-height: calc(rows * line-height)` via inline style derived from `rows` prop
- `resize: vertical` (configurable)

### Checkbox (`atoms/checkbox/`)

**Type-specific props:**
- `label` — text (visible label next to checkbox)
- `checked` — "true"/"false" (default "false")

**HTML output:**
```html
<label class="checkbox">
  <input
    type="checkbox"
    id="terms"
    name="terms"
    value="yes"
    checked
    class="checkbox__input"
    required
    aria-invalid="false"
  >
  <span class="checkbox__box" aria-hidden="true">
    <svg viewBox="0 0 16 16" class="checkbox__check">
      <path d="M3 8l3 3 7-7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </span>
  <span class="checkbox__label">Ich akzeptiere die Datenschutzerklärung</span>
</label>
```

**SCSS:**
- `.checkbox` — `display: inline-flex; align-items: center; gap: var(--space-2); cursor: pointer;`
- `.checkbox__input` — visually hidden but accessible (`position: absolute; opacity: 0; ...`)
- `.checkbox__box` — `width/height: 20px; border: 2px solid var(--color-border-default); border-radius: var(--radius-sm); background: var(--color-bg-surface);`
- `.checkbox__check` — `opacity: 0; color: var(--color-action-primary-text);`
- `.checkbox__input:checked + .checkbox__box` — `background: var(--color-action-primary); border-color: var(--color-action-primary);`
- `.checkbox__input:checked + .checkbox__box .checkbox__check` — `opacity: 1`
- `.checkbox__input:focus-visible + .checkbox__box` — focus-visible ring
- `.checkbox__input:disabled ~ *` — disabled state
- `.checkbox:has(.checkbox__input[aria-invalid="true"]) .checkbox__box` — `border-color: var(--color-action-danger)`

### Radio (`atoms/radio/`)

**Type-specific props:**
- `label` — text
- `checked` — "true"/"false"

**HTML output:**
```html
<label class="radio">
  <input type="radio" id="size-md" name="size" value="md" checked class="radio__input">
  <span class="radio__circle" aria-hidden="true">
    <span class="radio__dot"></span>
  </span>
  <span class="radio__label">Mittel</span>
</label>
```

**SCSS:**
- Same pattern as Checkbox but with `border-radius: var(--radius-full)` (perfect circle)
- `.radio__dot` — `width/height: 8px; border-radius: var(--radius-full); background: var(--color-action-primary-text); transform: scale(0); transition: transform var(--duration-fast)`
- `.radio__input:checked + .radio__circle .radio__dot` — `transform: scale(1)`
- All other states (focus, disabled, invalid) match Checkbox
- Wrap transform in `respect-motion` mixin

### Toggle (`atoms/toggle/`)

**Type-specific props:**
- `label` — text
- `checked` — "true"/"false"

**HTML output:**
```html
<label class="toggle">
  <input
    type="checkbox"
    role="switch"
    id="notifications"
    name="notifications"
    checked
    class="toggle__input"
  >
  <span class="toggle__track" aria-hidden="true">
    <span class="toggle__thumb"></span>
  </span>
  <span class="toggle__label">E-Mail-Benachrichtigungen</span>
</label>
```

**SCSS:**
- `.toggle__track` — `width: 40px; height: 22px; border-radius: var(--radius-full); background: var(--color-neutral-300); position: relative;`
- `.toggle__thumb` — `width: 18px; height: 18px; border-radius: var(--radius-full); background: var(--color-bg-surface); position: absolute; top: 2px; left: 2px;`
- Wrap `transform` and `background` transitions in `respect-motion` mixin
- `.toggle__input:checked + .toggle__track` — `background: var(--color-action-primary)`
- `.toggle__input:checked + .toggle__track .toggle__thumb` — `transform: translateX(18px)`
- `.toggle__input:focus-visible + .toggle__track` — focus-visible ring
- Disabled state via `:disabled`

### Select (`atoms/select/`)

**Type-specific props:**
- `options` — JSON-encoded array `[{"value": "ch", "label": "Schweiz"}, ...]`
- `placeholder` — text (renders as disabled selected first option)
- `size` — select: `sm`, `md`, `lg`

**HTML output:**
```html
<div class="select-wrapper select-wrapper--md">
  <select id="country" name="country" class="select" aria-invalid="false">
    <option value="" disabled selected>Land auswählen…</option>
    <option value="ch">Schweiz</option>
    <option value="de">Deutschland</option>
  </select>
  <span class="select__chevron" aria-hidden="true">▾</span>
</div>
```

**SCSS:**
- `.select-wrapper` — `position: relative; display: inline-block;`
- `.select` — same border/padding/focus/error styles as Input, plus `appearance: none; padding-right: var(--space-8);` (room for chevron)
- `.select__chevron` — `position: absolute; right: var(--space-3); top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--color-text-muted);`
- Size variants on `.select-wrapper--{sm,md,lg}` cascade to `.select` padding/font-size

## Storybook stories (per atom)

Each atom has these stories at minimum:

- **Input:** Default, Email, Password, Number, Disabled, Invalid, Required, Sizes (sm/lg)
- **Textarea:** Default, With Value, Disabled, Invalid
- **Checkbox:** Unchecked, Checked, Disabled, Invalid, Required
- **Radio:** Unchecked, Checked, Disabled
- **Toggle:** Off, On, Disabled
- **Select:** Default, With Value, Disabled, Invalid, Sizes (sm/lg)

All argTypes follow the common props pattern + type-specific options.

## Wiring

**`resources/css/main.scss`** — add 6 lines in atoms section (alphabetical):
```scss
@use "../../public/packages/inno_frontend/components/atoms/checkbox/checkbox";
@use "../../public/packages/inno_frontend/components/atoms/input/input";
@use "../../public/packages/inno_frontend/components/atoms/radio/radio";
@use "../../public/packages/inno_frontend/components/atoms/select/select";
@use "../../public/packages/inno_frontend/components/atoms/textarea/textarea";
@use "../../public/packages/inno_frontend/components/atoms/toggle/toggle";
```

**`_storybook/components.scss`** — same 6 lines in atoms section.

## Accessibility patterns applied

- `aria-invalid` on every input — set from `invalid` prop
- `aria-describedby` on every input — set from `describedBy` prop (consumer passes ID of hint/error)
- `aria-hidden="true"` on visual-only chrome (custom checkboxes, chevrons)
- `role="switch"` on toggle
- Visually hidden but accessible inputs for custom checkbox/radio/toggle (clickable label wraps everything)
- `focus-visible-ring` mixin on all interactive elements (keyboard-only)
- Disabled state uses `:disabled` (not `pointer-events: none`) so screen readers announce it
- All SVG icons have `aria-hidden="true"` (decorative)

## What Doesn't Change

- Existing components
- Tokens
- A11y infrastructure (already in place from Sub-project 2)
- Storybook config

## Verification

1. `npm run development` — builds without errors
2. Each atom returns 200 on its endpoint:
   - `atoms/input`, `atoms/textarea`, `atoms/checkbox`, `atoms/radio`, `atoms/toggle`, `atoms/select`
3. Storybook a11y panel shows no violations on any default story
4. Tab through stories — focus ring appears only on keyboard focus
5. Disabled stories show input as disabled with proper styling
6. Invalid stories show error border + correct `aria-invalid` attribute
