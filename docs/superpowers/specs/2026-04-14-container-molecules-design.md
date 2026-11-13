# Container Molecules — Sub-project 9

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Add a flexible `card` molecule for grouping related content with elevation, padding, and optional link behavior. Reuses existing tokens and the `heading` atom.

## Architecture

Stateless PHP partial. The card renders as `<article>` by default or `<a>` when `clickable="true"`. Title/subtitle are rendered via the heading atom (for correct semantic level and typography). Body and footer are raw HTML slots passed via props.

## Card

**Files:**
- `molecules/card/card.php`
- `molecules/card/card.scss`
- `molecules/card/card.variants.php`
- `molecules/card/card.stories.json`

**Props:**
- `variant` — `elevated` (default) | `outlined`
- `padding` — `sm` | `md` (default) | `lg`
- `clickable` — `"true"` / `"false"` (default `"false"`)
- `href` — URL; required when `clickable="true"` (default `"#"`)
- `title` — scalar text (optional)
- `titleLevel` — `"2"` | `"3"` (default) | `"4"` (integer as string, maps to `<h2>`/`<h3>`/`<h4>`)
- `subtitle` — scalar text (optional)
- `body` — raw HTML slot for main content
- `footer` — raw HTML slot for actions (optional)

**card.php:**
```php
<?php
$variant = $props['variant'] ?? 'elevated';
$padding = $props['padding'] ?? 'md';
$clickable = ($props['clickable'] ?? 'false') === 'true';
$href = $props['href'] ?? '#';
$title = $props['title'] ?? '';
$titleLevel = (int)($props['titleLevel'] ?? '3');
$titleLevel = max(2, min(4, $titleLevel));
$titleTag = 'h' . $titleLevel;
$subtitle = $props['subtitle'] ?? '';
$body = $props['body'] ?? '';
$footer = $props['footer'] ?? '';

$classes = ['card', 'card--' . $variant, 'card--padding-' . $padding];
if ($clickable) {
    $classes[] = 'card--clickable';
}
$classAttr = implode(' ', $classes);

$tag = $clickable ? 'a' : 'article';
$extraAttrs = $clickable ? ' href="' . htmlspecialchars($href) . '"' : '';
?>
<<?= $tag ?> class="<?= $classAttr ?>"<?= $extraAttrs ?>>
    <?php if ($title !== '' || $subtitle !== ''): ?>
        <header class="card__header">
            <?php if ($title !== ''): ?>
                <<?= $titleTag ?> class="card__title"><?= htmlspecialchars($title) ?></<?= $titleTag ?>>
            <?php endif; ?>
            <?php if ($subtitle !== ''): ?>
                <p class="card__subtitle"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </header>
    <?php endif; ?>
    <?php if ($body !== ''): ?>
        <div class="card__body"><?= $body ?></div>
    <?php endif; ?>
    <?php if ($footer !== ''): ?>
        <footer class="card__footer"><?= $footer ?></footer>
    <?php endif; ?>
</<?= $tag ?>>
```

**card.scss:**
```scss
@use "~@helpers/a11y" as a11y;

.card {
  display: block;
  background: var(--color-bg-surface);
  border-radius: var(--radius-lg);
  color: var(--color-text-primary);
  text-decoration: none;

  &--elevated {
    box-shadow: var(--shadow-sm);
    border: 1px solid transparent;
  }

  &--outlined {
    border: 1px solid var(--color-border-default);
  }

  &--padding-sm { padding: var(--space-4); }
  &--padding-md { padding: var(--space-6); }
  &--padding-lg { padding: var(--space-8); }

  &--clickable {
    cursor: pointer;
    display: block;

    @include a11y.respect-motion {
      transition:
        box-shadow var(--duration-fast) var(--ease-standard),
        border-color var(--duration-fast) var(--ease-standard),
        transform var(--duration-fast) var(--ease-standard);
    }

    &:hover {
      box-shadow: var(--shadow-md);
      border-color: var(--color-border-strong);

      @include a11y.respect-motion {
        transform: translateY(-2px);
      }
    }

    @include a11y.focus-visible-ring;
  }

  &__header {
    margin-bottom: var(--space-4);
  }

  &__title {
    margin: 0 0 var(--space-1) 0;
  }

  &__subtitle {
    margin: 0;
    color: var(--color-text-muted);
    font-size: 14px;
  }

  &__body {
    color: var(--color-text-primary);

    > :first-child { margin-top: 0; }
    > :last-child  { margin-bottom: 0; }
  }

  &__footer {
    margin-top: var(--space-4);
    padding-top: var(--space-4);
    border-top: 1px solid var(--color-border-default);
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
  }
}
```

**card.variants.php:**
```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'variant' => 'elevated',
        'padding' => 'md',
        'clickable' => 'false',
        'href' => '#',
        'title' => 'Solarenergie im Quartier',
        'titleLevel' => '3',
        'subtitle' => 'Eingereicht am 12.03.2026',
        'body' => '<p>Bewohnerinnen planen eine Genossenschaft für Photovoltaik-Anlagen auf den Dächern ihrer Häuser – inklusive Batteriespeicher und Lastmanagement.</p>',
        'footer' => '<a href="#" class="link">Mehr erfahren</a>',
    ],
];
```

**card.stories.json:**
```json
{
  "title": "Molecules/Card",
  "argTypes": {
    "variant": { "control": { "type": "select" }, "options": ["elevated", "outlined"] },
    "padding": { "control": { "type": "select" }, "options": ["sm", "md", "lg"] },
    "clickable": { "control": { "type": "select" }, "options": ["true", "false"] },
    "href": { "control": "text" },
    "title": { "control": "text" },
    "titleLevel": { "control": { "type": "select" }, "options": ["2", "3", "4"] },
    "subtitle": { "control": "text" }
  },
  "stories": [
    { "name": "Elevated", "parameters": { "server": { "id": "molecules/card" } } },
    { "name": "Outlined", "args": { "variant": "outlined" }, "parameters": { "server": { "id": "molecules/card" } } },
    { "name": "Padding Small", "args": { "padding": "sm" }, "parameters": { "server": { "id": "molecules/card" } } },
    { "name": "Padding Large", "args": { "padding": "lg" }, "parameters": { "server": { "id": "molecules/card" } } },
    { "name": "Clickable", "args": { "clickable": "true", "href": "#idea-42" }, "parameters": { "server": { "id": "molecules/card" } } },
    { "name": "Title Only", "args": { "subtitle": "", "footer": "" }, "parameters": { "server": { "id": "molecules/card" } } },
    { "name": "Body Only", "args": { "title": "", "subtitle": "", "footer": "" }, "parameters": { "server": { "id": "molecules/card" } } }
  ]
}
```

## Wiring

**`resources/css/main.scss`** — add 1 `@use` line in molecules section (alphabetical, between `breadcrumb` and `dropdown`):
```scss
@use "../../public/packages/inno_frontend/components/molecules/card/card";
```

**`_storybook/components.scss`** — same line in Molecules section.

**`_storybook/components.ts`** — no change (no JS).

## What Doesn't Change

- `molecules/page-card` — remains the narrow link-card used by CMS autonav block template. Not replaced.
- Atoms (heading atom used as-is)
- Tokens, a11y infrastructure
- Other molecules, organisms, templates
- Storybook config

## Verification

1. `npm run development` compiles without errors
2. `molecules/card` endpoint returns 200
3. Heading tag matches `titleLevel` prop (e.g. `<h2>` when `titleLevel="2"`)
4. Clickable variant renders `<a>` root, shows focus ring on Tab, hover lifts the card
5. Non-clickable renders `<article>` root with no pointer cursor
6. Omitting `title`, `subtitle`, `footer` cleanly hides those sections (no empty elements)
7. Axe panel: 0 violations across all stories
