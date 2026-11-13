# Storybook Plan 3: Molecules & Organisms

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create 6 molecule components and 6 organism components that compose the atoms from Plan 2 into meaningful UI groups, following Atomic Design methodology.

**Architecture:** Molecules combine 2-3 atoms into simple groups. Organisms compose molecules and atoms into complex, self-contained sections. Each component is a PHP partial that `include`s its child components, passing `$props` down. The same PHP story server from Plan 1 renders them.

**Tech Stack:** PHP 8.3 partials (composition via `include`), SCSS, `@storybook/server` 8.6

**Spec:** `docs/superpowers/specs/2026-04-13-storybook-design-system-design.md`

**Plan series:** Plan 3 of 4. Plans 1 (Infrastructure) and 2 (Atoms) are complete. All 8 atoms exist in `resources/components/atoms/`.

**Key pattern for composition:** Child components are included with:
```php
$props = ['variant' => 'primary', 'label' => $buttonLabel];
include DIR_COMPONENTS . '/atoms/button/button.php';
```

---

## MOLECULES

---

### Task 1: Feature-button molecule

Extracts the styled CTA from the existing `feature_link` block. Composes button atom + optional icon.

**Files:**
- Create: `resources/components/molecules/feature-button/feature-button.variants.php`
- Create: `resources/components/molecules/feature-button/feature-button.php`
- Create: `resources/components/molecules/feature-button/feature-button.scss`
- Create: `resources/components/molecules/feature-button/feature-button.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/molecules/feature-button
```

- [ ] **Step 2: Create `feature-button.variants.php`**

```php
<?php
return [
    'defaults' => [
        'label' => 'Projektidee einreichen',
        'href' => '#',
        'icon' => '',
    ],
];
```

- [ ] **Step 3: Create `feature-button.php`**

```php
<?php
/**
 * Molecule: Feature Button
 * A prominent CTA button, optionally with an icon. Used in hero sections and standalone CTAs.
 *
 * @param array $props {
 *   @type string $label  Button text
 *   @type string $href   Link target URL
 *   @type string $icon   Optional icon HTML (e.g. SVG or icon class)
 * }
 */

$label = $props['label'] ?? 'Projektidee einreichen';
$href = $props['href'] ?? '#';
$icon = $props['icon'] ?? '';
?>
<div class="feature-link">
    <a class="feature-button button" href="<?= htmlspecialchars($href) ?>">
        <?php if ($icon): ?>
            <span class="me-3"><?= $icon ?></span>
        <?php endif; ?>
        <?= htmlspecialchars($label) ?>
    </a>
</div>
```

- [ ] **Step 4: Create `feature-button.scss`**

```scss
@use "../../../css/helper/functions.scss" as f;

.feature-link {
  text-align: center;

  .feature-button {
    color: var(--color-white);
    background-color: var(--color-primary);
    border: none;
    border-radius: var(--border-radius);
    font-weight: 600;
    padding: f.fluidVal(12, 16) 32px;
    box-shadow: var(--box-shadow);
    display: inline-block;
    cursor: pointer;
    transition: color var(--transition-time) ease,
      background-color var(--transition-time) ease;

    &:hover {
      background-color: var(--color-green);
      color: var(--color-white);
    }
  }
}
```

- [ ] **Step 5: Create `feature-button.stories.json`**

```json
{
  "title": "Molecules/Feature Button",
  "stories": [
    {
      "name": "Default",
      "parameters": { "server": { "id": "molecules/feature-button", "params": { "label": "Projektidee einreichen", "href": "#" } } }
    },
    {
      "name": "With Icon",
      "parameters": { "server": { "id": "molecules/feature-button", "params": { "label": "Download", "href": "#", "icon": "↓" } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=molecules/feature-button&label=Test&href=/go"
kill $PID
```

Expected: HTML containing `class="feature-button button"` and `Test`

- [ ] **Step 7: Commit**

```bash
git add resources/components/molecules/feature-button/
git commit -m "feat(molecules): add feature-button component"
```

---

### Task 2: Page-card molecule

A single page thumbnail + title + link. Extracted from the `page_list` block loop item.

**Files:**
- Create: `resources/components/molecules/page-card/page-card.variants.php`
- Create: `resources/components/molecules/page-card/page-card.php`
- Create: `resources/components/molecules/page-card/page-card.scss`
- Create: `resources/components/molecules/page-card/page-card.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/molecules/page-card
```

- [ ] **Step 2: Create `page-card.variants.php`**

```php
<?php
return [
    'defaults' => [
        'title' => 'Projektidee',
        'href' => '#',
        'target' => '_self',
        'imageSrc' => 'https://placehold.co/600x400/B7CD57/353535?text=Projekt',
        'imageAlt' => 'Projektbild',
    ],
];
```

- [ ] **Step 3: Create `page-card.php`**

```php
<?php
/**
 * Molecule: Page Card
 * A linked card with thumbnail image and title. Used in page list grids.
 *
 * @param array $props {
 *   @type string $title     Page title
 *   @type string $href      Link URL
 *   @type string $target    '_self' | '_blank'
 *   @type string $imageSrc  Thumbnail image URL
 *   @type string $imageAlt  Image alt text
 * }
 */

$title = $props['title'] ?? 'Projektidee';
$href = $props['href'] ?? '#';
$target = $props['target'] ?? '_self';
$imageSrc = $props['imageSrc'] ?? '';
$imageAlt = $props['imageAlt'] ?? '';
?>
<a href="<?= htmlspecialchars($href) ?>" target="<?= htmlspecialchars($target) ?>" class="page-link">
    <?php if ($imageSrc): ?>
        <?php
        $props = ['src' => $imageSrc, 'alt' => $imageAlt, 'ratio' => '3/2', 'fit' => 'cover', 'rounded' => 'true', 'width' => '100%'];
        include DIR_COMPONENTS . '/atoms/image/image.php';
        ?>
    <?php endif; ?>
    <span class="page-link-text">
        <?= htmlspecialchars($title) ?>
    </span>
</a>
```

- [ ] **Step 4: Create `page-card.scss`**

```scss
@use "../../../css/helper/functions.scss" as f;

.page-link {
  position: relative;
  display: block;
  text-decoration: none;
  filter: none;
  transition: filter var(--transition-time), color var(--transition-time);

  &:hover {
    filter: sepia(100%);
  }

  .image {
    border-radius: var(--border-radius-small);
    overflow: hidden;
  }

  .page-link-text {
    display: block;
    width: 100%;
    text-align: center;
    margin-top: f.fluidVal(8, 12);
  }
}
```

- [ ] **Step 5: Create `page-card.stories.json`**

```json
{
  "title": "Molecules/Page Card",
  "stories": [
    {
      "name": "Default",
      "parameters": { "server": { "id": "molecules/page-card", "params": { "title": "Solarenergie für Glarus", "href": "#", "imageSrc": "https://placehold.co/600x400/B7CD57/353535?text=Solar", "imageAlt": "Solarprojekt" } } }
    },
    {
      "name": "External Link",
      "parameters": { "server": { "id": "molecules/page-card", "params": { "title": "KlimaGlarus.ch", "href": "https://klimaglarus.ch", "target": "_blank", "imageSrc": "https://placehold.co/600x400/89705B/FFF?text=Klima", "imageAlt": "KlimaGlarus" } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=molecules/page-card&title=Test&href=/p&imageSrc=img.jpg&imageAlt=Img"
kill $PID
```

Expected: HTML containing `class="page-link"`, `class="image image--rounded"`, and `class="page-link-text"`

- [ ] **Step 7: Commit**

```bash
git add resources/components/molecules/page-card/
git commit -m "feat(molecules): add page-card component"
```

---

### Task 3: Nav-item molecule

A navigation link with active state indicator.

**Files:**
- Create: `resources/components/molecules/nav-item/nav-item.variants.php`
- Create: `resources/components/molecules/nav-item/nav-item.php`
- Create: `resources/components/molecules/nav-item/nav-item.scss`
- Create: `resources/components/molecules/nav-item/nav-item.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/molecules/nav-item
```

- [ ] **Step 2: Create `nav-item.variants.php`**

```php
<?php
return [
    'defaults' => [
        'text' => 'Navigation',
        'href' => '#',
        'active' => 'false',
        'level' => '1',
    ],
];
```

- [ ] **Step 3: Create `nav-item.php`**

```php
<?php
/**
 * Molecule: Nav Item
 * A navigation link with optional active state. Level 2 items render in green.
 *
 * @param array $props {
 *   @type string $text    Link text
 *   @type string $href    URL
 *   @type string $active  'true' | 'false'
 *   @type string $level   '1' (primary) | '2' (submenu, green)
 * }
 */

$text = $props['text'] ?? 'Navigation';
$href = $props['href'] ?? '#';
$active = ($props['active'] ?? 'false') === 'true';
$level = $props['level'] ?? '1';

$class = '';
if ($active) {
    $class = ' class="nav-selected"';
}
?>
<li>
    <a href="<?= htmlspecialchars($href) ?>"<?= $class ?>><?= htmlspecialchars($text) ?></a>
</li>
```

- [ ] **Step 4: Create `nav-item.scss`**

```scss
// Molecule: Nav Item
// Navigation link styles are defined in the autonav block SCSS
// (nav-drawer ul li a). This file is reserved for component-level overrides.
```

- [ ] **Step 5: Create `nav-item.stories.json`**

```json
{
  "title": "Molecules/Nav Item",
  "stories": [
    {
      "name": "Default",
      "parameters": { "server": { "id": "molecules/nav-item", "params": { "text": "Projektideen", "href": "/projektideen" } } }
    },
    {
      "name": "Active",
      "parameters": { "server": { "id": "molecules/nav-item", "params": { "text": "Projektideen", "href": "/projektideen", "active": "true" } } }
    },
    {
      "name": "Submenu (Level 2)",
      "parameters": { "server": { "id": "molecules/nav-item", "params": { "text": "Solar", "href": "/solar", "level": "2" } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=molecules/nav-item&text=Test&active=true"
kill $PID
```

Expected: `<li><a href="#" class="nav-selected">Test</a></li>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/molecules/nav-item/
git commit -m "feat(molecules): add nav-item component"
```

---

### Task 4: Accent-text molecule

Content with decorative guillemets « ». Used in WYSIWYG editor for accent quotes.

**Files:**
- Create: `resources/components/molecules/accent-text/accent-text.variants.php`
- Create: `resources/components/molecules/accent-text/accent-text.php`
- Create: `resources/components/molecules/accent-text/accent-text.scss`
- Create: `resources/components/molecules/accent-text/accent-text.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/molecules/accent-text
```

- [ ] **Step 2: Create `accent-text.variants.php`**

```php
<?php
return [
    'defaults' => [
        'text' => 'Gemeinsam für das Klima',
    ],
];
```

- [ ] **Step 3: Create `accent-text.php`**

```php
<?php
/**
 * Molecule: Accent Text
 * A text block with decorative guillemets « » in the background.
 *
 * @param array $props {
 *   @type string $text  The accent quote text
 * }
 */

$text = $props['text'] ?? '';
?>
<span class="accent"><?= htmlspecialchars($text) ?></span>
```

- [ ] **Step 4: Create `accent-text.scss`**

```scss
@use "../../../css/helper/functions.scss" as f;

// Molecule: Accent Text
// Decorative guillemets behind the text.
// Note: The .content-block span.accent styles in the current block SCSS handle this.
// This file defines the standalone component version.
.accent {
  position: relative;
  display: block;
  width: 80%;
  margin-left: 10%;
  text-align: center;

  &:before {
    content: "«";
    display: block;
    color: var(--color-primary);
    font-size: f.fluidVal(48, 96);
    position: absolute;
    top: 0;
    left: 0;
    z-index: -1;
  }
  &:after {
    content: "»";
    display: block;
    color: var(--color-primary);
    font-size: f.fluidVal(48, 96);
    position: absolute;
    bottom: 0;
    right: 0;
    z-index: -1;
  }
}
```

- [ ] **Step 5: Create `accent-text.stories.json`**

```json
{
  "title": "Molecules/Accent Text",
  "stories": [
    {
      "name": "Default",
      "parameters": { "server": { "id": "molecules/accent-text", "params": { "text": "Gemeinsam für das Klima" } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=molecules/accent-text&text=Test"
kill $PID
```

Expected: `<span class="accent">Test</span>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/molecules/accent-text/
git commit -m "feat(molecules): add accent-text component (guillemets)"
```

---

### Task 5: Question-text molecule

Content with decorative question mark ?. Used in WYSIWYG editor for FAQ styling.

**Files:**
- Create: `resources/components/molecules/question-text/question-text.variants.php`
- Create: `resources/components/molecules/question-text/question-text.php`
- Create: `resources/components/molecules/question-text/question-text.scss`
- Create: `resources/components/molecules/question-text/question-text.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/molecules/question-text
```

- [ ] **Step 2: Create `question-text.variants.php`**

```php
<?php
return [
    'defaults' => [
        'text' => 'Wie kann ich eine Projektidee einreichen?',
    ],
];
```

- [ ] **Step 3: Create `question-text.php`**

```php
<?php
/**
 * Molecule: Question Text
 * A text block with decorative question mark ? in the background.
 *
 * @param array $props {
 *   @type string $text  The question text
 * }
 */

$text = $props['text'] ?? '';
?>
<span class="question"><?= htmlspecialchars($text) ?></span>
```

- [ ] **Step 4: Create `question-text.scss`**

```scss
@use "../../../css/helper/functions.scss" as f;

// Molecule: Question Text
.question {
  position: relative;
  display: block;

  &:before {
    content: "?";
    display: block;
    color: var(--color-primary);
    font-size: f.fluidVal(48, 96);
    position: absolute;
    font-weight: bold;
    top: 0;
    left: -1rem;
    z-index: -1;
  }
}
```

- [ ] **Step 5: Create `question-text.stories.json`**

```json
{
  "title": "Molecules/Question Text",
  "stories": [
    {
      "name": "Default",
      "parameters": { "server": { "id": "molecules/question-text", "params": { "text": "Wie kann ich eine Projektidee einreichen?" } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=molecules/question-text&text=Test"
kill $PID
```

Expected: `<span class="question">Test</span>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/molecules/question-text/
git commit -m "feat(molecules): add question-text component"
```

---

### Task 6: Partner-logo molecule

A linked logo image used in the footer for partner organizations.

**Files:**
- Create: `resources/components/molecules/partner-logo/partner-logo.variants.php`
- Create: `resources/components/molecules/partner-logo/partner-logo.php`
- Create: `resources/components/molecules/partner-logo/partner-logo.scss`
- Create: `resources/components/molecules/partner-logo/partner-logo.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/molecules/partner-logo
```

- [ ] **Step 2: Create `partner-logo.variants.php`**

```php
<?php
return [
    'defaults' => [
        'name' => 'Partner',
        'href' => '#',
        'imageSrc' => 'https://placehold.co/120x60/FFF/89705B?text=Logo',
    ],
];
```

- [ ] **Step 3: Create `partner-logo.php`**

```php
<?php
/**
 * Molecule: Partner Logo
 * A linked logo image. Used in the footer for partner organizations.
 *
 * @param array $props {
 *   @type string $name      Organization name (used as alt text)
 *   @type string $href      Link URL
 *   @type string $imageSrc  Logo image URL
 * }
 */

$name = $props['name'] ?? 'Partner';
$href = $props['href'] ?? '#';
$imageSrc = $props['imageSrc'] ?? '';
?>
<a href="<?= htmlspecialchars($href) ?>" target="_blank" rel="noopener noreferrer" class="logo-link">
    <img src="<?= htmlspecialchars($imageSrc) ?>" alt="<?= htmlspecialchars($name) ?>">
</a>
```

- [ ] **Step 4: Create `partner-logo.scss`**

```scss
// Molecule: Partner Logo
// Styles are defined in layout/footer.scss (.logos a.logo-link).
// This file is reserved for component-level overrides.
```

- [ ] **Step 5: Create `partner-logo.stories.json`**

```json
{
  "title": "Molecules/Partner Logo",
  "stories": [
    {
      "name": "arento",
      "parameters": { "server": { "id": "molecules/partner-logo", "params": { "name": "arento ag", "href": "https://arento.ch", "imageSrc": "https://placehold.co/120x60/FFF/89705B?text=arento" } } }
    },
    {
      "name": "KlimaGlarus",
      "parameters": { "server": { "id": "molecules/partner-logo", "params": { "name": "KlimaGlarus.ch", "href": "https://klimaglarus.ch", "imageSrc": "https://placehold.co/120x60/FFF/7B876F?text=KlimaGlarus" } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=molecules/partner-logo&name=Test&href=/x&imageSrc=logo.png"
kill $PID
```

Expected: HTML containing `class="logo-link"`, `alt="Test"`, `src="logo.png"`

- [ ] **Step 7: Commit**

```bash
git add resources/components/molecules/partner-logo/
git commit -m "feat(molecules): add partner-logo component"
```

---

## ORGANISMS

---

### Task 7: Hero-image organism

Full-width image with text overlay, heading, body, and optional CTA button.

**Files:**
- Create: `resources/components/organisms/hero-image/hero-image.variants.php`
- Create: `resources/components/organisms/hero-image/hero-image.php`
- Create: `resources/components/organisms/hero-image/hero-image.scss`
- Create: `resources/components/organisms/hero-image/hero-image.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/organisms/hero-image
```

- [ ] **Step 2: Create `hero-image.variants.php`**

```php
<?php
return [
    'defaults' => [
        'title' => 'Willkommen bei der Innovationsbox',
        'body' => '<p>Gemeinsam für das Klima — Ideen einreichen, entwickeln und umsetzen.</p>',
        'imageSrc' => 'https://placehold.co/1920x600/7B876F/FFF?text=Hero+Image',
        'imageAlt' => 'Hero-Bild',
        'buttonLabel' => '',
        'buttonHref' => '#',
    ],
];
```

- [ ] **Step 3: Create `hero-image.php`**

```php
<?php
/**
 * Organism: Hero Image
 * Full-width image with text overlay containing heading, body text, and optional button.
 *
 * @param array $props {
 *   @type string $title        Heading text (h1)
 *   @type string $body         Body HTML
 *   @type string $imageSrc     Hero image URL
 *   @type string $imageAlt     Image alt text
 *   @type string $buttonLabel  CTA button text (empty = no button)
 *   @type string $buttonHref   CTA button URL
 * }
 */

$title = $props['title'] ?? '';
$body = $props['body'] ?? '';
$imageSrc = $props['imageSrc'] ?? '';
$imageAlt = $props['imageAlt'] ?? '';
$buttonLabel = $props['buttonLabel'] ?? '';
$buttonHref = $props['buttonHref'] ?? '#';
?>
<div class="hero-image-wrapper">
    <?php
    $props = ['src' => $imageSrc, 'alt' => $imageAlt, 'fit' => 'cover', 'width' => '100vw'];
    include DIR_COMPONENTS . '/atoms/image/image.php';
    ?>

    <div class="hero-image-text">
        <?php if ($title): ?>
            <?php
            $props = ['level' => '1', 'text' => $title];
            include DIR_COMPONENTS . '/atoms/heading/heading.php';
            ?>
        <?php endif; ?>
        <?php if ($body): ?>
            <?= $body ?>
        <?php endif; ?>
        <?php if ($buttonLabel): ?>
            <?php
            $props = ['label' => $buttonLabel, 'href' => $buttonHref];
            include DIR_COMPONENTS . '/molecules/feature-button/feature-button.php';
            ?>
        <?php endif; ?>
    </div>
</div>
```

- [ ] **Step 4: Create `hero-image.scss`**

```scss
@use "../../../css/helper/functions.scss" as f;

.hero-image-wrapper {
  position: relative;
  margin-bottom: f.fluidVal(32, 48);

  > .image {
    width: 100vw;
    object-fit: cover;
  }

  .hero-image-text {
    position: absolute;
    bottom: 20%;
    left: 50%;
    transform: translate(-50%, 0);
    text-align: center;
    color: var(--color-white);
    padding: f.fluidVal(20, 40) f.fluidVal(30, 60);
    border-radius: var(--border-radius);
    background-color: var(--color-light-overlay);
    backdrop-filter: blur(32px);
    -webkit-backdrop-filter: blur(32px);
  }

  @media screen and (max-width: #{f.$upperBoundTablet-factor}px) {
    .hero-image-text {
      position: relative;
      bottom: 0;
      left: 0;
      transform: none;
      margin-top: -1rem;
    }
  }
}
```

- [ ] **Step 5: Create `hero-image.stories.json`**

```json
{
  "title": "Organisms/Hero Image",
  "stories": [
    {
      "name": "With Button",
      "parameters": { "server": { "id": "organisms/hero-image", "params": {
        "title": "Innovationsbox Glarus",
        "body": "<p>Klimarelevante Ideen für die Region.</p>",
        "imageSrc": "https://placehold.co/1920x600/7B876F/FFF?text=Hero",
        "imageAlt": "Hero-Bild",
        "buttonLabel": "Idee einreichen",
        "buttonHref": "/einreichen"
      } } }
    },
    {
      "name": "Without Button",
      "parameters": { "server": { "id": "organisms/hero-image", "params": {
        "title": "Über die Innovationsbox",
        "body": "<p>Ein Engagement der arento ag für die Klimaregion Glarnerland.</p>",
        "imageSrc": "https://placehold.co/1920x600/89705B/FFF?text=About",
        "imageAlt": "Über uns"
      } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=organisms/hero-image&title=Test&imageSrc=hero.jpg&imageAlt=Hero"
kill $PID
```

Expected: HTML containing `class="hero-image-wrapper"`, `class="hero-image-text"`, `<h1>Test</h1>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/organisms/hero-image/
git commit -m "feat(organisms): add hero-image component"
```

---

### Task 8: Page-list-grid organism

A flex grid of page-card molecules. The container around the page list.

**Files:**
- Create: `resources/components/organisms/page-list-grid/page-list-grid.variants.php`
- Create: `resources/components/organisms/page-list-grid/page-list-grid.php`
- Create: `resources/components/organisms/page-list-grid/page-list-grid.scss`
- Create: `resources/components/organisms/page-list-grid/page-list-grid.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/organisms/page-list-grid
```

- [ ] **Step 2: Create `page-list-grid.variants.php`**

```php
<?php
return [
    'defaults' => [
        'pages' => json_encode([
            ['title' => 'Solarenergie', 'href' => '#', 'imageSrc' => 'https://placehold.co/600x400/B7CD57/353535?text=Solar', 'imageAlt' => 'Solar'],
            ['title' => 'Windkraft', 'href' => '#', 'imageSrc' => 'https://placehold.co/600x400/89705B/FFF?text=Wind', 'imageAlt' => 'Wind'],
            ['title' => 'Gebäudesanierung', 'href' => '#', 'imageSrc' => 'https://placehold.co/600x400/7B876F/FFF?text=Gebäude', 'imageAlt' => 'Gebäude'],
        ]),
    ],
];
```

- [ ] **Step 3: Create `page-list-grid.php`**

```php
<?php
/**
 * Organism: Page List Grid
 * A flex grid of page-card molecules wrapped in a spaced container.
 *
 * @param array $props {
 *   @type string $pages  JSON-encoded array of page objects, each with: title, href, target, imageSrc, imageAlt
 * }
 */

$pagesJson = $props['pages'] ?? '[]';
$pages = json_decode($pagesJson, true) ?: [];
?>
<div class="lb-container spaced-block">
    <div class="page-list-wrapper">
        <div class="page-list-pages">
            <?php foreach ($pages as $page): ?>
                <?php
                $props = [
                    'title' => $page['title'] ?? '',
                    'href' => $page['href'] ?? '#',
                    'target' => $page['target'] ?? '_self',
                    'imageSrc' => $page['imageSrc'] ?? '',
                    'imageAlt' => $page['imageAlt'] ?? '',
                ];
                include DIR_COMPONENTS . '/molecules/page-card/page-card.php';
                ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
```

- [ ] **Step 4: Create `page-list-grid.scss`**

```scss
@use "../../../css/helper/functions.scss" as f;

.page-list-wrapper {
  .page-list-pages {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: f.fluidVal(8, 16);

    .page-link {
      flex: 1 0 15%;
      width: 30%;
    }
  }

  @media screen and (max-width: #{f.$lowerBound-factor - 1}px) {
    .page-list-pages .page-link {
      flex: 0 0 30%;
    }
  }
  @media screen and (max-width: #{f.$upperBoundTablet-factor - 1}px) {
    .page-list-pages .page-link {
      flex: 0 0 45%;
    }
  }
}
```

- [ ] **Step 5: Create `page-list-grid.stories.json`**

```json
{
  "title": "Organisms/Page List Grid",
  "stories": [
    {
      "name": "Three Pages",
      "parameters": { "server": { "id": "organisms/page-list-grid", "params": {
        "pages": "[{\"title\":\"Solarenergie\",\"href\":\"#\",\"imageSrc\":\"https://placehold.co/600x400/B7CD57/353535?text=Solar\",\"imageAlt\":\"Solar\"},{\"title\":\"Windkraft\",\"href\":\"#\",\"imageSrc\":\"https://placehold.co/600x400/89705B/FFF?text=Wind\",\"imageAlt\":\"Wind\"},{\"title\":\"Gebäudesanierung\",\"href\":\"#\",\"imageSrc\":\"https://placehold.co/600x400/7B876F/FFF?text=Gebäude\",\"imageAlt\":\"Gebäude\"}]"
      } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=organisms/page-list-grid" | head -10
kill $PID
```

Expected: HTML containing `page-list-wrapper`, `page-list-pages`, multiple `page-link` elements

- [ ] **Step 7: Commit**

```bash
git add resources/components/organisms/page-list-grid/
git commit -m "feat(organisms): add page-list-grid component"
```

---

### Task 9: Navigation-drawer organism

The sidebar drawer navigation with toggle button, overlay, and nav items.

**Files:**
- Create: `resources/components/organisms/navigation-drawer/navigation-drawer.variants.php`
- Create: `resources/components/organisms/navigation-drawer/navigation-drawer.php`
- Create: `resources/components/organisms/navigation-drawer/navigation-drawer.scss`
- Create: `resources/components/organisms/navigation-drawer/navigation-drawer.ts`
- Create: `resources/components/organisms/navigation-drawer/navigation-drawer.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/organisms/navigation-drawer
```

- [ ] **Step 2: Create `navigation-drawer.variants.php`**

```php
<?php
return [
    'defaults' => [
        'logoSrc' => 'https://placehold.co/320x80/B7CD57/353535?text=Innovationsbox',
        'logoAlt' => 'Innovationsbox',
        'logoHref' => '/',
        'items' => json_encode([
            ['text' => 'Startseite', 'href' => '/', 'active' => 'true'],
            ['text' => 'Projektideen', 'href' => '/projektideen'],
            ['text' => 'Über uns', 'href' => '/ueber-uns'],
        ]),
    ],
];
```

- [ ] **Step 3: Create `navigation-drawer.php`**

```php
<?php
/**
 * Organism: Navigation Drawer
 * Sidebar drawer navigation with toggle button, overlay, logo, and nav items.
 *
 * @param array $props {
 *   @type string $logoSrc   Logo image URL
 *   @type string $logoAlt   Logo alt text
 *   @type string $logoHref  Logo link URL
 *   @type string $items     JSON-encoded array of nav items, each with: text, href, active
 * }
 */

$logoSrc = $props['logoSrc'] ?? '';
$logoAlt = $props['logoAlt'] ?? '';
$logoHref = $props['logoHref'] ?? '/';
$itemsJson = $props['items'] ?? '[]';
$items = json_decode($itemsJson, true) ?: [];
?>
<nav class="nav-container">
    <?php if ($logoSrc): ?>
        <a href="<?= htmlspecialchars($logoHref) ?>">
            <img class="main-logo" src="<?= htmlspecialchars($logoSrc) ?>" alt="<?= htmlspecialchars($logoAlt) ?>">
        </a>
    <?php endif; ?>

    <button class="nav-toggle" aria-expanded="false" aria-label="Navigation öffnen">☰</button>

    <div class="nav-overlay"></div>

    <div class="nav-drawer" aria-hidden="true">
        <button class="nav-close" aria-label="Navigation schliessen">✕</button>
        <ul>
            <?php foreach ($items as $item): ?>
                <?php
                $props = [
                    'text' => $item['text'] ?? '',
                    'href' => $item['href'] ?? '#',
                    'active' => $item['active'] ?? 'false',
                    'level' => $item['level'] ?? '1',
                ];
                include DIR_COMPONENTS . '/molecules/nav-item/nav-item.php';
                ?>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
```

- [ ] **Step 4: Create `navigation-drawer.scss`**

```scss
@use "../../../css/helper/functions.scss" as f;

.main-logo {
  width: clamp(0px, 50vw, 320px);
  margin: f.fluidVal(8, 16) 0;
}

.nav-container {
  display: flex;
  justify-content: center;

  .nav-drawer {
    position: fixed;
    top: 0;
    right: 0;
    width: 400px;
    max-width: 75vw;
    height: 100%;
    background: var(--color-white);
    box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    transform: translateX(100%);
    transition: transform 0.3s ease;
    padding: 2rem;
    z-index: 1000;

    &.active {
      transform: translateX(0);
    }
  }

  .nav-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
    z-index: 999;

    &.active {
      opacity: 1;
      pointer-events: auto;
    }
  }

  .nav-toggle {
    position: absolute;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
  }

  .nav-close, .nav-toggle {
    margin-bottom: 1rem;
    aspect-ratio: 1;
    border-radius: 50%;
  }

  .nav-drawer ul {
    list-style: none;
    padding: 0;

    li {
      margin: 1rem 0;
    }
    a {
      text-decoration: none;
      font-size: f.fluidVal(18, 24);

      &.nav-selected {
        text-decoration: underline;
        text-decoration-color: var(--color-green);
      }
    }
  }
}
```

- [ ] **Step 5: Create `navigation-drawer.ts`**

This is the interactive behavior — copied from the existing `autonav/view.ts` but adapted for the component.

```typescript
enum NAV_SELECTORS {
  NAV_CONTAINER = '.nav-container',
  NAV_DRAWER = '.nav-drawer',
  NAV_OPENER = '.nav-toggle',
  NAV_CLOSER = '.nav-close',
  NAV_OVERLAY = '.nav-overlay',
}

class NavigationDrawer {
  #opener: HTMLButtonElement | null;
  #closer: HTMLButtonElement | null;
  #drawer: HTMLElement | null;
  #overlay: HTMLElement | null;

  constructor(private container: HTMLElement) {
    this.#opener = container.querySelector(NAV_SELECTORS.NAV_OPENER);
    this.#closer = container.querySelector(NAV_SELECTORS.NAV_CLOSER);
    this.#drawer = container.querySelector(NAV_SELECTORS.NAV_DRAWER);
    this.#overlay = container.querySelector(NAV_SELECTORS.NAV_OVERLAY);
    this.bindEvents();
  }

  private bindEvents() {
    this.#opener?.addEventListener('click', () => this.open());
    this.#closer?.addEventListener('click', () => this.close());
    this.#overlay?.addEventListener('click', () => this.close());
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') this.close();
    });
  }

  private open() {
    this.#drawer?.classList.add('active');
    this.#drawer?.setAttribute('aria-hidden', 'false');
    this.#overlay?.classList.add('active');
    this.#opener?.setAttribute('aria-expanded', 'true');
  }

  private close() {
    this.#drawer?.classList.remove('active');
    this.#drawer?.setAttribute('aria-hidden', 'true');
    this.#overlay?.classList.remove('active');
    this.#opener?.setAttribute('aria-expanded', 'false');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll<HTMLElement>(NAV_SELECTORS.NAV_CONTAINER).forEach(
    (el) => new NavigationDrawer(el)
  );
});
```

- [ ] **Step 6: Create `navigation-drawer.stories.json`**

```json
{
  "title": "Organisms/Navigation Drawer",
  "stories": [
    {
      "name": "Default",
      "parameters": { "server": { "id": "organisms/navigation-drawer", "params": {
        "logoSrc": "https://placehold.co/320x80/B7CD57/353535?text=Innovationsbox",
        "logoAlt": "Innovationsbox",
        "logoHref": "/",
        "items": "[{\"text\":\"Startseite\",\"href\":\"/\",\"active\":\"true\"},{\"text\":\"Projektideen\",\"href\":\"/projektideen\"},{\"text\":\"Über uns\",\"href\":\"/ueber-uns\"}]"
      } } }
    }
  ]
}
```

- [ ] **Step 7: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=organisms/navigation-drawer" | head -15
kill $PID
```

Expected: HTML containing `nav-container`, `nav-toggle`, `nav-overlay`, `nav-drawer`

- [ ] **Step 8: Commit**

```bash
git add resources/components/organisms/navigation-drawer/
git commit -m "feat(organisms): add navigation-drawer component with TS interaction"
```

---

### Task 10: Header organism

Page header with skip-link and navigation drawer.

**Files:**
- Create: `resources/components/organisms/header/header.variants.php`
- Create: `resources/components/organisms/header/header.php`
- Create: `resources/components/organisms/header/header.scss`
- Create: `resources/components/organisms/header/header.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/organisms/header
```

- [ ] **Step 2: Create `header.variants.php`**

```php
<?php
return [
    'defaults' => [
        'logoSrc' => 'https://placehold.co/320x80/B7CD57/353535?text=Innovationsbox',
        'logoAlt' => 'Innovationsbox',
        'logoHref' => '/',
        'navItems' => json_encode([
            ['text' => 'Startseite', 'href' => '/', 'active' => 'true'],
            ['text' => 'Projektideen', 'href' => '/projektideen'],
            ['text' => 'Über uns', 'href' => '/ueber-uns'],
        ]),
    ],
];
```

- [ ] **Step 3: Create `header.php`**

```php
<?php
/**
 * Organism: Header
 * Page header containing skip-link for accessibility and the navigation drawer.
 *
 * @param array $props {
 *   @type string $logoSrc    Logo image URL
 *   @type string $logoAlt    Logo alt text
 *   @type string $logoHref   Logo link URL
 *   @type string $navItems   JSON-encoded array of nav items
 * }
 */

$logoSrc = $props['logoSrc'] ?? '';
$logoAlt = $props['logoAlt'] ?? '';
$logoHref = $props['logoHref'] ?? '/';
$navItems = $props['navItems'] ?? '[]';
?>
<header class="page-header">
    <?php
    $props = ['text' => 'Zum Hauptinhalt springen', 'target' => '#main-content-area'];
    include DIR_COMPONENTS . '/atoms/skip-link/skip-link.php';
    ?>

    <?php
    $props = [
        'logoSrc' => $logoSrc,
        'logoAlt' => $logoAlt,
        'logoHref' => $logoHref,
        'items' => $navItems,
    ];
    include DIR_COMPONENTS . '/organisms/navigation-drawer/navigation-drawer.php';
    ?>
</header>
```

- [ ] **Step 4: Create `header.scss`**

```scss
// Organism: Header
// Base header styles are in layout/header.scss.
// The page-header gets a colored bottom border from the theme.
```

- [ ] **Step 5: Create `header.stories.json`**

```json
{
  "title": "Organisms/Header",
  "stories": [
    {
      "name": "Default",
      "parameters": { "server": { "id": "organisms/header", "params": {
        "logoSrc": "https://placehold.co/320x80/B7CD57/353535?text=Innovationsbox",
        "logoAlt": "Innovationsbox",
        "logoHref": "/",
        "navItems": "[{\"text\":\"Startseite\",\"href\":\"/\",\"active\":\"true\"},{\"text\":\"Projektideen\",\"href\":\"/projektideen\"},{\"text\":\"Über uns\",\"href\":\"/ueber-uns\"}]"
      } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=organisms/header" | head -10
kill $PID
```

Expected: HTML containing `<header class="page-header">`, skip-link, nav-container

- [ ] **Step 7: Commit**

```bash
git add resources/components/organisms/header/
git commit -m "feat(organisms): add header component (skip-link + nav)"
```

---

### Task 11: Footer organism

Dark footer with partner logos and attribution text.

**Files:**
- Create: `resources/components/organisms/footer/footer.variants.php`
- Create: `resources/components/organisms/footer/footer.php`
- Create: `resources/components/organisms/footer/footer.scss`
- Create: `resources/components/organisms/footer/footer.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/organisms/footer
```

- [ ] **Step 2: Create `footer.variants.php`**

```php
<?php
return [
    'defaults' => [
        'text' => 'Diese Innovationsbox ist ein Engagement der arento ag für die Klimaregion Glarnerland und den Verein KlimaGlarus.ch',
        'partners' => json_encode([
            ['name' => 'arento ag', 'href' => 'https://arento.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/89705B?text=arento'],
            ['name' => 'Klimaregion Glarnerland', 'href' => 'https://klimaregion.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/7B876F?text=Klimaregion'],
            ['name' => 'KlimaGlarus.ch', 'href' => 'https://klimaglarus.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/B7CD57?text=KlimaGlarus'],
        ]),
    ],
];
```

- [ ] **Step 3: Create `footer.php`**

```php
<?php
/**
 * Organism: Footer
 * Dark footer with attribution text and partner logos.
 *
 * @param array $props {
 *   @type string $text      Attribution text
 *   @type string $partners  JSON-encoded array of partner objects (name, href, imageSrc)
 * }
 */

$text = $props['text'] ?? '';
$partnersJson = $props['partners'] ?? '[]';
$partners = json_decode($partnersJson, true) ?: [];
?>
<footer>
    <div class="lb-container static-content">
        <?php if ($text): ?>
            <?php
            $props = ['variant' => 'body', 'text' => $text];
            include DIR_COMPONENTS . '/atoms/paragraph/paragraph.php';
            ?>
        <?php endif; ?>

        <?php if (!empty($partners)): ?>
            <div class="logos">
                <?php foreach ($partners as $partner): ?>
                    <?php
                    $props = [
                        'name' => $partner['name'] ?? '',
                        'href' => $partner['href'] ?? '#',
                        'imageSrc' => $partner['imageSrc'] ?? '',
                    ];
                    include DIR_COMPONENTS . '/molecules/partner-logo/partner-logo.php';
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</footer>
```

- [ ] **Step 4: Create `footer.scss`**

```scss
// Organism: Footer
// Footer styles are defined in layout/footer.scss.
// This file is reserved for component-level overrides.
```

- [ ] **Step 5: Create `footer.stories.json`**

```json
{
  "title": "Organisms/Footer",
  "stories": [
    {
      "name": "Default",
      "parameters": { "server": { "id": "organisms/footer", "params": {
        "text": "Diese Innovationsbox ist ein Engagement der arento ag für die Klimaregion Glarnerland und den Verein KlimaGlarus.ch",
        "partners": "[{\"name\":\"arento ag\",\"href\":\"https://arento.ch\",\"imageSrc\":\"https://placehold.co/120x60/FFF/89705B?text=arento\"},{\"name\":\"Klimaregion Glarnerland\",\"href\":\"https://klimaregion.ch\",\"imageSrc\":\"https://placehold.co/120x60/FFF/7B876F?text=Klimaregion\"},{\"name\":\"KlimaGlarus.ch\",\"href\":\"https://klimaglarus.ch\",\"imageSrc\":\"https://placehold.co/120x60/FFF/B7CD57?text=KlimaGlarus\"}]"
      } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=organisms/footer" | head -15
kill $PID
```

Expected: HTML containing `<footer>`, `static-content`, `logos`, multiple `logo-link` elements

- [ ] **Step 7: Commit**

```bash
git add resources/components/organisms/footer/
git commit -m "feat(organisms): add footer component (text + partner logos)"
```

---

### Task 12: Content-block organism

A container for WYSIWYG content that supports accent and question inline styles.

**Files:**
- Create: `resources/components/organisms/content-block/content-block.variants.php`
- Create: `resources/components/organisms/content-block/content-block.php`
- Create: `resources/components/organisms/content-block/content-block.scss`
- Create: `resources/components/organisms/content-block/content-block.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/organisms/content-block
```

- [ ] **Step 2: Create `content-block.variants.php`**

```php
<?php
return [
    'defaults' => [
        'content' => '<p>Hier steht der Inhalt des Content-Blocks.</p>',
    ],
];
```

- [ ] **Step 3: Create `content-block.php`**

```php
<?php
/**
 * Organism: Content Block
 * A narrow container for WYSIWYG content. Supports .accent and .question inline styles.
 *
 * @param array $props {
 *   @type string $content  HTML content from the WYSIWYG editor
 * }
 */

$content = $props['content'] ?? '';
?>
<div class="lb-container-narrow spaced-block content-block">
    <?= $content ?>
</div>
```

- [ ] **Step 4: Create `content-block.scss`**

```scss
// Organism: Content Block
// The .content-block styles for span.accent and span.question are defined
// in the existing block SCSS. This file imports them for standalone use.
// See: molecules/accent-text and molecules/question-text for the component versions.
```

- [ ] **Step 5: Create `content-block.stories.json`**

```json
{
  "title": "Organisms/Content Block",
  "stories": [
    {
      "name": "Plain Text",
      "parameters": { "server": { "id": "organisms/content-block", "params": {
        "content": "<h2>Über die Innovationsbox</h2><p>Die Innovationsbox ist eine moderierte Plattform, auf der klimarelevante Projektideen eingereicht, geprüft und gemeinsam weiterentwickelt werden können.</p><p>Der Ablauf ist einfach: Idee einreichen, Moderation abwarten, veröffentlichen und gemeinsam umsetzen.</p>"
      } } }
    },
    {
      "name": "With Accent Quote",
      "parameters": { "server": { "id": "organisms/content-block", "params": {
        "content": "<p>Unser Motto:</p><span class=\"accent\">Gemeinsam für das Klima</span><p>Wir setzen auf Zusammenarbeit.</p>"
      } } }
    },
    {
      "name": "With FAQ Question",
      "parameters": { "server": { "id": "organisms/content-block", "params": {
        "content": "<span class=\"question\">Wie kann ich eine Projektidee einreichen?</span><p>Klicken Sie auf den Button \"Idee einreichen\" und füllen Sie das Formular aus.</p>"
      } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=organisms/content-block&content=<p>Test</p>"
kill $PID
```

Expected: `<div class="lb-container-narrow spaced-block content-block"><p>Test</p></div>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/organisms/content-block/
git commit -m "feat(organisms): add content-block component"
```

---

### Task 13: E2E tests for molecules and organisms

**Files:**
- Create: `tests/e2e/specs/storybook/molecules-organisms.spec.ts`

- [ ] **Step 1: Create `tests/e2e/specs/storybook/molecules-organisms.spec.ts`**

```typescript
import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Molecule components', () => {
  test('feature-button: renders with feature-button class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/feature-button&label=Test&href=/go`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('class="feature-button button"');
    expect(html).toContain('Test');
  });

  test('page-card: renders image and title', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/page-card&title=Solar&href=/s&imageSrc=img.jpg&imageAlt=Img`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('class="page-link"');
    expect(html).toContain('page-link-text');
    expect(html).toContain('Solar');
  });

  test('nav-item: active renders nav-selected class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/nav-item&text=Home&active=true`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('nav-selected');
  });

  test('accent-text: renders accent span', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/accent-text&text=Quote`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('class="accent"');
  });

  test('question-text: renders question span', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/question-text&text=FAQ`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('class="question"');
  });

  test('partner-logo: renders logo-link with image', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/partner-logo&name=Test&href=/x&imageSrc=logo.png`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('class="logo-link"');
    expect(html).toContain('alt="Test"');
  });
});

test.describe('Organism components', () => {
  test('hero-image: renders wrapper with heading and image', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/hero-image&title=Hello&imageSrc=hero.jpg&imageAlt=Hero`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('hero-image-wrapper');
    expect(html).toContain('hero-image-text');
    expect(html).toContain('<h1>Hello</h1>');
  });

  test('page-list-grid: renders grid with page cards from JSON', async ({ request }) => {
    const pages = JSON.stringify([
      { title: 'A', href: '#', imageSrc: 'a.jpg', imageAlt: 'A' },
      { title: 'B', href: '#', imageSrc: 'b.jpg', imageAlt: 'B' },
    ]);
    const res = await request.get(`${URL}/?id=organisms/page-list-grid&pages=${encodeURIComponent(pages)}`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('page-list-pages');
    expect(html).toContain('page-link-text');
  });

  test('navigation-drawer: renders nav-container with drawer', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/navigation-drawer`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('nav-container');
    expect(html).toContain('nav-drawer');
    expect(html).toContain('nav-overlay');
    expect(html).toContain('nav-toggle');
  });

  test('header: renders page-header with skip-link and nav', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/header`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('page-header');
    expect(html).toContain('skip-link');
    expect(html).toContain('nav-container');
  });

  test('footer: renders footer with logos', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/footer`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<footer>');
    expect(html).toContain('logo-link');
  });

  test('content-block: renders narrow container with content', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/content-block&content=<p>Test</p>`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('content-block');
    expect(html).toContain('lb-container-narrow');
    expect(html).toContain('<p>Test</p>');
  });
});
```

- [ ] **Step 2: Verify test file parses**

```bash
npx playwright test tests/e2e/specs/storybook/molecules-organisms.spec.ts --config=tests/e2e/playwright.config.ts --list
```

Expected: Lists 12 test cases (6 molecules + 6 organisms).

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/storybook/molecules-organisms.spec.ts
git commit -m "test(storybook): add E2E tests for molecules and organisms"
```
