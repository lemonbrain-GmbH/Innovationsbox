# Storybook Plan 2: Atoms

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the remaining 7 atom components (heading, paragraph, link, image, container, skip-link, overlay) following the established button atom pattern.

**Architecture:** Each atom is a PHP partial (`<name>.php`) with a `$props` array input, a variants definition (`<name>.variants.php`), an SCSS file, and a Storybook story (`<name>.stories.json`). The PHP story server renders them via `?id=atoms/<name>`. Global theme CSS is loaded in Storybook's preview iframe.

**Tech Stack:** PHP 8.3 partials, SCSS (global theme from generated tokens), `@storybook/server` 8.6 (JSON stories)

**Spec:** `docs/superpowers/specs/2026-04-13-storybook-design-system-design.md`

**Plan series:** This is Plan 2 of 4. Plan 1 (Infrastructure) is complete. The button atom already exists.

**Established pattern (from button atom):**
- Each component: 4 files in `resources/components/atoms/<name>/`
- `<name>.variants.php` returns array with `defaults` key
- `<name>.php` reads `$props`, outputs HTML
- `<name>.scss` defines component-specific styles (global presets handle base typography)
- `<name>.stories.json` has `title: "Atoms/<Name>"` and stories with `parameters.server.id` + `params`
- Verify with: `curl "http://localhost:6007/?id=atoms/<name>&<props>"`

---

### Task 1: Heading atom

**Files:**
- Create: `resources/components/atoms/heading/heading.variants.php`
- Create: `resources/components/atoms/heading/heading.php`
- Create: `resources/components/atoms/heading/heading.scss`
- Create: `resources/components/atoms/heading/heading.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/atoms/heading
```

- [ ] **Step 2: Create `heading.variants.php`**

```php
<?php
return [
    'levels' => [1, 2, 3, 4, 5, 6],
    'defaults' => [
        'level' => '2',
        'text' => 'Überschrift',
    ],
];
```

- [ ] **Step 3: Create `heading.php`**

```php
<?php
/**
 * Atom: Heading
 *
 * @param array $props {
 *   @type string $level  '1'-'6' — which heading tag to render
 *   @type string $text   Heading text content
 * }
 */

$level = (int) ($props['level'] ?? 2);
$level = max(1, min(6, $level));
$text = $props['text'] ?? 'Überschrift';
$tag = 'h' . $level;
?>
<<?= $tag ?>><?= htmlspecialchars($text) ?></<?= $tag ?>>
```

- [ ] **Step 4: Create `heading.scss`**

```scss
// Atom: Heading
// Base heading styles (h1-h6 font-size, line-height, color, weight) are defined
// in global/presets.scss via responsive-fluid-multi and CSS custom properties.
// This file is reserved for heading-specific component overrides if needed.
```

- [ ] **Step 5: Create `heading.stories.json`**

```json
{
  "title": "Atoms/Heading",
  "stories": [
    {
      "name": "H1",
      "parameters": {
        "server": {
          "id": "atoms/heading",
          "params": { "level": "1", "text": "Willkommen bei der Innovationsbox" }
        }
      }
    },
    {
      "name": "H2",
      "parameters": {
        "server": {
          "id": "atoms/heading",
          "params": { "level": "2", "text": "Projektideen entdecken" }
        }
      }
    },
    {
      "name": "H3",
      "parameters": {
        "server": {
          "id": "atoms/heading",
          "params": { "level": "3", "text": "Aktuelle Projekte" }
        }
      }
    },
    {
      "name": "H4",
      "parameters": {
        "server": {
          "id": "atoms/heading",
          "params": { "level": "4", "text": "Projektraum beitreten" }
        }
      }
    }
  ]
}
```

- [ ] **Step 6: Verify rendering**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=atoms/heading&level=1&text=Test"
kill $PID
```

Expected: `<h1>Test</h1>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/atoms/heading/
git commit -m "feat(atoms): add heading component (h1-h6)"
```

---

### Task 2: Paragraph atom

**Files:**
- Create: `resources/components/atoms/paragraph/paragraph.variants.php`
- Create: `resources/components/atoms/paragraph/paragraph.php`
- Create: `resources/components/atoms/paragraph/paragraph.scss`
- Create: `resources/components/atoms/paragraph/paragraph.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/atoms/paragraph
```

- [ ] **Step 2: Create `paragraph.variants.php`**

```php
<?php
return [
    'variants' => ['body', 'lead', 'small'],
    'defaults' => [
        'variant' => 'body',
        'text' => 'Dies ist ein Beispieltext für die Innovationsbox-Plattform.',
    ],
];
```

- [ ] **Step 3: Create `paragraph.php`**

```php
<?php
/**
 * Atom: Paragraph
 *
 * @param array $props {
 *   @type string $variant  'body' | 'lead' | 'small'
 *   @type string $text     Text content
 * }
 */

$variant = $props['variant'] ?? 'body';
$text = $props['text'] ?? '';

if ($variant === 'lead') {
    $tag = 'p';
    $class = 'lead';
} elseif ($variant === 'small') {
    $tag = 'p';
    $class = 'small';
} else {
    $tag = 'p';
    $class = '';
}
?>
<<?= $tag ?><?= $class ? ' class="' . $class . '"' : '' ?>><?= htmlspecialchars($text) ?></<?= $tag ?>>
```

- [ ] **Step 4: Create `paragraph.scss`**

```scss
// Atom: Paragraph
// Base paragraph, .lead, and .small styles are defined in global/presets.scss.
// This file is reserved for paragraph-specific component overrides if needed.
```

- [ ] **Step 5: Create `paragraph.stories.json`**

```json
{
  "title": "Atoms/Paragraph",
  "stories": [
    {
      "name": "Body",
      "parameters": {
        "server": {
          "id": "atoms/paragraph",
          "params": {
            "variant": "body",
            "text": "Die Innovationsbox ist eine moderierte Plattform, auf der klimarelevante Projektideen eingereicht, geprüft und gemeinsam weiterentwickelt werden können."
          }
        }
      }
    },
    {
      "name": "Lead",
      "parameters": {
        "server": {
          "id": "atoms/paragraph",
          "params": {
            "variant": "lead",
            "text": "Gemeinsam für das Klima — Ideen einreichen, entwickeln und umsetzen."
          }
        }
      }
    },
    {
      "name": "Small",
      "parameters": {
        "server": {
          "id": "atoms/paragraph",
          "params": {
            "variant": "small",
            "text": "Zuletzt aktualisiert am 13. April 2026"
          }
        }
      }
    }
  ]
}
```

- [ ] **Step 6: Verify rendering**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=atoms/paragraph&variant=lead&text=Test"
kill $PID
```

Expected: `<p class="lead">Test</p>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/atoms/paragraph/
git commit -m "feat(atoms): add paragraph component (body, lead, small)"
```

---

### Task 3: Link atom

**Files:**
- Create: `resources/components/atoms/link/link.variants.php`
- Create: `resources/components/atoms/link/link.php`
- Create: `resources/components/atoms/link/link.scss`
- Create: `resources/components/atoms/link/link.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/atoms/link
```

- [ ] **Step 2: Create `link.variants.php`**

```php
<?php
return [
    'targets' => ['_self', '_blank'],
    'defaults' => [
        'text' => 'Link',
        'href' => '#',
        'target' => '_self',
    ],
];
```

- [ ] **Step 3: Create `link.php`**

```php
<?php
/**
 * Atom: Link
 *
 * @param array $props {
 *   @type string $text    Link text
 *   @type string $href    URL
 *   @type string $target  '_self' | '_blank'
 * }
 */

$text = $props['text'] ?? 'Link';
$href = $props['href'] ?? '#';
$target = $props['target'] ?? '_self';

$attrs = '';
if ($target === '_blank') {
    $attrs = ' target="_blank" rel="noopener noreferrer"';
}
?>
<a href="<?= htmlspecialchars($href) ?>"<?= $attrs ?>><?= htmlspecialchars($text) ?></a>
```

- [ ] **Step 4: Create `link.scss`**

```scss
// Atom: Link
// Base link styles (color, hover, font-weight, text-decoration) are defined
// in global/presets.scss. This file is reserved for link-specific overrides.
```

- [ ] **Step 5: Create `link.stories.json`**

```json
{
  "title": "Atoms/Link",
  "stories": [
    {
      "name": "Default",
      "parameters": {
        "server": {
          "id": "atoms/link",
          "params": { "text": "Mehr erfahren", "href": "#" }
        }
      }
    },
    {
      "name": "External",
      "parameters": {
        "server": {
          "id": "atoms/link",
          "params": { "text": "KlimaGlarus.ch", "href": "https://klimaglarus.ch", "target": "_blank" }
        }
      }
    }
  ]
}
```

- [ ] **Step 6: Verify rendering**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=atoms/link&text=Test&href=/about&target=_blank"
kill $PID
```

Expected: `<a href="/about" target="_blank" rel="noopener noreferrer">Test</a>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/atoms/link/
git commit -m "feat(atoms): add link component (default, external)"
```

---

### Task 4: Image atom

**Files:**
- Create: `resources/components/atoms/image/image.variants.php`
- Create: `resources/components/atoms/image/image.php`
- Create: `resources/components/atoms/image/image.scss`
- Create: `resources/components/atoms/image/image.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/atoms/image
```

- [ ] **Step 2: Create `image.variants.php`**

```php
<?php
return [
    'fits' => ['cover', 'contain'],
    'ratios' => ['auto', '16/9', '3/2', '4/3', '1/1'],
    'defaults' => [
        'src' => 'https://placehold.co/800x450/B7CD57/353535?text=Innovationsbox',
        'alt' => 'Platzhalterbild',
        'ratio' => 'auto',
        'fit' => 'cover',
        'rounded' => 'false',
        'width' => '100%',
    ],
];
```

- [ ] **Step 3: Create `image.php`**

```php
<?php
/**
 * Atom: Image
 *
 * @param array $props {
 *   @type string $src      Image URL
 *   @type string $alt      Alt text
 *   @type string $ratio    'auto' | '16/9' | '3/2' | '4/3' | '1/1'
 *   @type string $fit      'cover' | 'contain'
 *   @type string $rounded  'true' | 'false'
 *   @type string $width    CSS width value (e.g. '100%', '50vw')
 * }
 */

$src = $props['src'] ?? '';
$alt = $props['alt'] ?? '';
$ratio = $props['ratio'] ?? 'auto';
$fit = $props['fit'] ?? 'cover';
$rounded = ($props['rounded'] ?? 'false') === 'true';
$width = $props['width'] ?? '100%';

$classes = 'image';
if ($rounded) {
    $classes .= ' image--rounded';
}

$style = "width: {$width};";
if ($ratio !== 'auto') {
    $style .= " aspect-ratio: {$ratio};";
}
$style .= " object-fit: {$fit};";
?>
<img
    class="<?= $classes ?>"
    src="<?= htmlspecialchars($src) ?>"
    alt="<?= htmlspecialchars($alt) ?>"
    style="<?= htmlspecialchars($style) ?>"
    loading="lazy"
>
```

- [ ] **Step 4: Create `image.scss`**

```scss
// Atom: Image
.image {
  display: block;
  max-width: 100%;
  height: auto;

  &--rounded {
    border-radius: var(--border-radius-small);
  }
}
```

- [ ] **Step 5: Create `image.stories.json`**

```json
{
  "title": "Atoms/Image",
  "stories": [
    {
      "name": "Default",
      "parameters": {
        "server": {
          "id": "atoms/image",
          "params": {
            "src": "https://placehold.co/800x450/B7CD57/353535?text=Innovationsbox",
            "alt": "Platzhalterbild"
          }
        }
      }
    },
    {
      "name": "Rounded 3:2",
      "parameters": {
        "server": {
          "id": "atoms/image",
          "params": {
            "src": "https://placehold.co/600x400/89705B/FFF?text=Projekt",
            "alt": "Projektbild",
            "ratio": "3/2",
            "rounded": "true"
          }
        }
      }
    },
    {
      "name": "Square Contain",
      "parameters": {
        "server": {
          "id": "atoms/image",
          "params": {
            "src": "https://placehold.co/400x400/7B876F/FFF?text=Logo",
            "alt": "Logo",
            "ratio": "1/1",
            "fit": "contain",
            "width": "200px"
          }
        }
      }
    }
  ]
}
```

- [ ] **Step 6: Verify rendering**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=atoms/image&src=test.jpg&alt=Test&rounded=true"
kill $PID
```

Expected: `<img class="image image--rounded" src="test.jpg" alt="Test" ...>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/atoms/image/
git commit -m "feat(atoms): add image component (ratio, fit, rounded variants)"
```

---

### Task 5: Container atom

**Files:**
- Create: `resources/components/atoms/container/container.variants.php`
- Create: `resources/components/atoms/container/container.php`
- Create: `resources/components/atoms/container/container.scss`
- Create: `resources/components/atoms/container/container.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/atoms/container
```

- [ ] **Step 2: Create `container.variants.php`**

```php
<?php
return [
    'variants' => ['default', 'medium', 'small', 'narrow'],
    'defaults' => [
        'variant' => 'default',
        'spaced' => 'false',
        'content' => '<p>Container-Inhalt</p>',
    ],
];
```

- [ ] **Step 3: Create `container.php`**

```php
<?php
/**
 * Atom: Container
 *
 * @param array $props {
 *   @type string $variant  'default' | 'medium' | 'small' | 'narrow'
 *   @type string $spaced   'true' | 'false' — adds spaced-block margin
 *   @type string $content  HTML content to wrap
 * }
 */

$variant = $props['variant'] ?? 'default';
$spaced = ($props['spaced'] ?? 'false') === 'true';
$content = $props['content'] ?? '';

if ($variant === 'narrow') {
    $classes = 'lb-container-narrow';
} else {
    $classes = 'lb-container';
    if ($variant !== 'default') {
        $classes .= ' ' . htmlspecialchars($variant);
    }
}

if ($spaced) {
    $classes .= ' spaced-block';
}
?>
<div class="<?= $classes ?>">
    <?= $content ?>
</div>
```

- [ ] **Step 4: Create `container.scss`**

```scss
// Atom: Container
// Container styles (.lb-container, .lb-container-narrow, .spaced-block) are defined
// in global/mixins.scss and global/global.scss. This file is reserved for overrides.
```

- [ ] **Step 5: Create `container.stories.json`**

```json
{
  "title": "Atoms/Container",
  "stories": [
    {
      "name": "Default",
      "parameters": {
        "server": {
          "id": "atoms/container",
          "params": {
            "variant": "default",
            "content": "<p>Standard-Container mit max-width 1620px und 85vw Breite.</p>"
          }
        }
      }
    },
    {
      "name": "Medium",
      "parameters": {
        "server": {
          "id": "atoms/container",
          "params": {
            "variant": "medium",
            "content": "<p>Mittlerer Container (max-width 1340px).</p>"
          }
        }
      }
    },
    {
      "name": "Narrow",
      "parameters": {
        "server": {
          "id": "atoms/container",
          "params": {
            "variant": "narrow",
            "content": "<p>Schmaler Container (57vw, für Textinhalte).</p>"
          }
        }
      }
    },
    {
      "name": "Spaced",
      "parameters": {
        "server": {
          "id": "atoms/container",
          "params": {
            "variant": "default",
            "spaced": "true",
            "content": "<p>Container mit spaced-block Margin oben und unten.</p>"
          }
        }
      }
    }
  ]
}
```

- [ ] **Step 6: Verify rendering**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=atoms/container&variant=narrow&spaced=true&content=Test"
kill $PID
```

Expected: `<div class="lb-container-narrow spaced-block">Test</div>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/atoms/container/
git commit -m "feat(atoms): add container component (default, medium, small, narrow, spaced)"
```

---

### Task 6: Skip-link atom

**Files:**
- Create: `resources/components/atoms/skip-link/skip-link.variants.php`
- Create: `resources/components/atoms/skip-link/skip-link.php`
- Create: `resources/components/atoms/skip-link/skip-link.scss`
- Create: `resources/components/atoms/skip-link/skip-link.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/atoms/skip-link
```

- [ ] **Step 2: Create `skip-link.variants.php`**

```php
<?php
return [
    'defaults' => [
        'text' => 'Zum Hauptinhalt springen',
        'target' => '#main-content-area',
    ],
];
```

- [ ] **Step 3: Create `skip-link.php`**

```php
<?php
/**
 * Atom: Skip Link (accessibility)
 *
 * @param array $props {
 *   @type string $text    Visible text when focused
 *   @type string $target  Anchor target (e.g. '#main-content-area')
 * }
 */

$text = $props['text'] ?? 'Zum Hauptinhalt springen';
$target = $props['target'] ?? '#main-content-area';
?>
<a href="<?= htmlspecialchars($target) ?>" class="skip-link visually-hidden"><?= htmlspecialchars($text) ?></a>
```

- [ ] **Step 4: Create `skip-link.scss`**

```scss
// Atom: Skip Link
// The .visually-hidden and .skip-link base styles are defined in global/global.scss.
// On focus, the skip link should become visible:
.skip-link:focus {
  position: static !important;
  width: auto !important;
  height: auto !important;
  padding: 8px 16px !important;
  margin: 0 !important;
  overflow: visible !important;
  clip: auto !important;
  white-space: normal !important;
  background-color: var(--color-primary);
  color: var(--color-white);
  font-weight: 600;
  z-index: 9999;
}
```

- [ ] **Step 5: Create `skip-link.stories.json`**

```json
{
  "title": "Atoms/Skip Link",
  "stories": [
    {
      "name": "Default",
      "parameters": {
        "server": {
          "id": "atoms/skip-link",
          "params": {
            "text": "Zum Hauptinhalt springen",
            "target": "#main-content-area"
          }
        }
      }
    }
  ]
}
```

- [ ] **Step 6: Verify rendering**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=atoms/skip-link"
kill $PID
```

Expected: `<a href="#main-content-area" class="skip-link visually-hidden">Zum Hauptinhalt springen</a>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/atoms/skip-link/
git commit -m "feat(atoms): add skip-link component (accessibility)"
```

---

### Task 7: Overlay atom

**Files:**
- Create: `resources/components/atoms/overlay/overlay.variants.php`
- Create: `resources/components/atoms/overlay/overlay.php`
- Create: `resources/components/atoms/overlay/overlay.scss`
- Create: `resources/components/atoms/overlay/overlay.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/atoms/overlay
```

- [ ] **Step 2: Create `overlay.variants.php`**

```php
<?php
return [
    'variants' => ['light', 'dark'],
    'defaults' => [
        'variant' => 'light',
        'content' => '<h2>Overlay-Inhalt</h2><p>Text auf halbtransparentem Hintergrund.</p>',
    ],
];
```

- [ ] **Step 3: Create `overlay.php`**

```php
<?php
/**
 * Atom: Overlay
 *
 * A semi-transparent surface with backdrop blur, used for text over images.
 *
 * @param array $props {
 *   @type string $variant  'light' (white 50% + blur) | 'dark' (black 40% + blur)
 *   @type string $content  HTML content to display inside the overlay
 * }
 */

$variant = $props['variant'] ?? 'light';
$content = $props['content'] ?? '';
?>
<div class="overlay overlay--<?= htmlspecialchars($variant) ?>">
    <?= $content ?>
</div>
```

- [ ] **Step 4: Create `overlay.scss`**

```scss
@use "../../../css/helper/functions" as f;

// Atom: Overlay
.overlay {
  border-radius: var(--border-radius);
  padding: f.fluidVal(16, 32);
  backdrop-filter: blur(32px);
  -webkit-backdrop-filter: blur(32px);

  &--light {
    background-color: var(--color-light-overlay);
    color: var(--color-black);
  }

  &--dark {
    background-color: rgba(0, 0, 0, 0.4);
    color: var(--color-white);
  }
}
```

- [ ] **Step 5: Create `overlay.stories.json`**

```json
{
  "title": "Atoms/Overlay",
  "stories": [
    {
      "name": "Light",
      "parameters": {
        "server": {
          "id": "atoms/overlay",
          "params": {
            "variant": "light",
            "content": "<h2>Projektidee</h2><p>Klimarelevante Ideen für Glarus.</p>"
          }
        }
      }
    },
    {
      "name": "Dark",
      "parameters": {
        "server": {
          "id": "atoms/overlay",
          "params": {
            "variant": "dark",
            "content": "<h2>Projektraum</h2><p>Geschützter Bereich für die Zusammenarbeit.</p>"
          }
        }
      }
    }
  ]
}
```

- [ ] **Step 6: Verify rendering**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=atoms/overlay&variant=dark&content=Test"
kill $PID
```

Expected: `<div class="overlay overlay--dark">Test</div>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/atoms/overlay/
git commit -m "feat(atoms): add overlay component (light, dark with backdrop blur)"
```

---

### Task 8: Add E2E tests for all atoms and run full verification

**Files:**
- Create: `tests/e2e/specs/storybook/atoms.spec.ts`

- [ ] **Step 1: Create `tests/e2e/specs/storybook/atoms.spec.ts`**

```typescript
import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Atom components', () => {
  test('button: primary renders with correct class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/button&variant=primary&label=Test`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('class="button button--primary"');
  });

  test('heading: renders correct tag for each level', async ({ request }) => {
    for (const level of [1, 2, 3, 4, 5, 6]) {
      const res = await request.get(`${URL}/?id=atoms/heading&level=${level}&text=Test`);
      expect(res.status()).toBe(200);
      const html = await res.text();
      expect(html).toContain(`<h${level}>`);
      expect(html).toContain(`</h${level}>`);
    }
  });

  test('paragraph: body renders plain p tag', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/paragraph&variant=body&text=Test`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<p>');
    expect(html).not.toContain('class=');
  });

  test('paragraph: lead renders p with lead class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/paragraph&variant=lead&text=Test`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('class="lead"');
  });

  test('link: external adds target and rel', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/link&text=Test&href=/x&target=_blank`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('target="_blank"');
    expect(html).toContain('rel="noopener noreferrer"');
  });

  test('image: rounded adds modifier class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/image&src=test.jpg&alt=Test&rounded=true`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('image--rounded');
  });

  test('container: narrow renders lb-container-narrow', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/container&variant=narrow&content=Test`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('lb-container-narrow');
  });

  test('container: spaced adds spaced-block class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/container&spaced=true&content=Test`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('spaced-block');
  });

  test('skip-link: renders with visually-hidden class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/skip-link`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('visually-hidden');
    expect(html).toContain('Zum Hauptinhalt springen');
  });

  test('overlay: dark variant renders correct class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/overlay&variant=dark&content=Test`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('overlay--dark');
  });
});
```

- [ ] **Step 2: Verify test file parses**

```bash
npx playwright test tests/e2e/specs/storybook/atoms.spec.ts --config=tests/e2e/playwright.config.ts --list
```

Expected: Lists 11 test cases.

- [ ] **Step 3: Run E2E tests (requires PHP server)**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
npx playwright test tests/e2e/specs/storybook/atoms.spec.ts --config=tests/e2e/playwright.config.ts
kill $PID
```

Expected: 11 tests passed.

- [ ] **Step 4: Commit**

```bash
git add tests/e2e/specs/storybook/atoms.spec.ts
git commit -m "test(atoms): add E2E tests for all 8 atom components"
```
