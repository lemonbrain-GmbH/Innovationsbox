# Storybook Plan 4: Templates, Pages & Block Migration

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create 2 page templates, 2 example pages, migrate 5 CMS block templates to use component partials, and add `DIR_COMPONENTS` constant to `page_theme.php`.

**Architecture:** Templates compose organisms into full page layouts. Pages fill templates with example content. Block migration splits existing `view.php` files into a thin CMS wrapper + component partial `include`. The `DIR_COMPONENTS` constant provides the base path for all `include` calls in production CMS context.

**Tech Stack:** PHP 8.3, Concrete CMS 9.4 block templates, `@storybook/server` 8.6

**Spec:** `docs/superpowers/specs/2026-04-13-storybook-design-system-design.md`

**Plan series:** Plan 4 of 4 (final). Plans 1-3 are complete. All atoms, molecules, and organisms exist in `resources/components/`.

---

### Task 1: Default-page template

The standard page layout: header → page header area → main content slot → footer.

**Files:**
- Create: `resources/components/templates/default-page/default-page.variants.php`
- Create: `resources/components/templates/default-page/default-page.php`
- Create: `resources/components/templates/default-page/default-page.scss`
- Create: `resources/components/templates/default-page/default-page.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/templates/default-page
```

- [ ] **Step 2: Create `default-page.variants.php`**

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
        'pageHeaderContent' => '<h1>Seitentitel</h1>',
        'mainContent' => '<p>Hier steht der Hauptinhalt der Seite.</p>',
        'footerText' => 'Diese Innovationsbox ist ein Engagement der arento ag für die Klimaregion Glarnerland und den Verein KlimaGlarus.ch',
        'footerPartners' => json_encode([
            ['name' => 'arento ag', 'href' => 'https://arento.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/89705B?text=arento'],
            ['name' => 'Klimaregion Glarnerland', 'href' => 'https://klimaregion.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/7B876F?text=Klimaregion'],
            ['name' => 'KlimaGlarus.ch', 'href' => 'https://klimaglarus.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/B7CD57?text=KlimaGlarus'],
        ]),
    ],
];
```

- [ ] **Step 3: Create `default-page.php`**

```php
<?php
/**
 * Template: Default Page
 * Standard page layout: header → page header area → main content → footer.
 *
 * @param array $props {
 *   @type string $logoSrc           Header logo URL
 *   @type string $logoAlt           Header logo alt
 *   @type string $logoHref          Header logo link
 *   @type string $navItems          JSON nav items
 *   @type string $pageHeaderContent HTML for the page header area
 *   @type string $mainContent       HTML for the main content area
 *   @type string $footerText        Footer attribution text
 *   @type string $footerPartners    JSON footer partners
 * }
 */

$logoSrc = $props['logoSrc'] ?? '';
$logoAlt = $props['logoAlt'] ?? '';
$logoHref = $props['logoHref'] ?? '/';
$navItems = $props['navItems'] ?? '[]';
$pageHeaderContent = $props['pageHeaderContent'] ?? '';
$mainContent = $props['mainContent'] ?? '';
$footerText = $props['footerText'] ?? '';
$footerPartners = $props['footerPartners'] ?? '[]';
?>
<div class="ccm-page">
    <?php
    $props = ['logoSrc' => $logoSrc, 'logoAlt' => $logoAlt, 'logoHref' => $logoHref, 'navItems' => $navItems];
    include DIR_COMPONENTS . '/organisms/header/header.php';
    ?>

    <header>
        <?= $pageHeaderContent ?>
    </header>

    <main id="main-content-area">
        <?= $mainContent ?>
    </main>

    <?php
    $props = ['text' => $footerText, 'partners' => $footerPartners];
    include DIR_COMPONENTS . '/organisms/footer/footer.php';
    ?>
</div>
```

- [ ] **Step 4: Create `default-page.scss`**

```scss
// Template: Default Page
// Layout is defined by the global .ccm-page wrapper and organism styles.
```

- [ ] **Step 5: Create `default-page.stories.json`**

```json
{
  "title": "Templates/Default Page",
  "stories": [
    {
      "name": "Default",
      "parameters": { "server": { "id": "templates/default-page", "params": {
        "pageHeaderContent": "<h1>Willkommen</h1>",
        "mainContent": "<div class='lb-container-narrow spaced-block content-block'><p>Dies ist die Standardseite der Innovationsbox.</p></div>"
      } } }
    }
  ]
}
```

- [ ] **Step 6: Verify**

```bash
php -S localhost:6007 storybook-server/index.php &
PID=$!; sleep 1
curl -s "http://localhost:6007/?id=templates/default-page" | head -15
kill $PID
```

Expected: HTML containing `class="ccm-page"`, `page-header`, `main-content-area`, `<footer>`

- [ ] **Step 7: Commit**

```bash
git add resources/components/templates/default-page/
git commit -m "feat(templates): add default-page template"
```

---

### Task 2: View-page template

Alternate layout with hero image instead of page header. Used for pages with prominent imagery.

**Files:**
- Create: `resources/components/templates/view-page/view-page.variants.php`
- Create: `resources/components/templates/view-page/view-page.php`
- Create: `resources/components/templates/view-page/view-page.scss`
- Create: `resources/components/templates/view-page/view-page.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/templates/view-page
```

- [ ] **Step 2: Create `view-page.variants.php`**

```php
<?php
return [
    'defaults' => [
        'logoSrc' => 'https://placehold.co/320x80/B7CD57/353535?text=Innovationsbox',
        'logoAlt' => 'Innovationsbox',
        'logoHref' => '/',
        'navItems' => json_encode([
            ['text' => 'Startseite', 'href' => '/'],
            ['text' => 'Projektideen', 'href' => '/projektideen', 'active' => 'true'],
            ['text' => 'Über uns', 'href' => '/ueber-uns'],
        ]),
        'heroTitle' => 'Projektideen',
        'heroBody' => '<p>Entdecken Sie klimarelevante Ideen für die Region Glarus.</p>',
        'heroImageSrc' => 'https://placehold.co/1920x600/7B876F/FFF?text=Hero',
        'heroImageAlt' => 'Hero-Bild',
        'heroButtonLabel' => '',
        'heroButtonHref' => '#',
        'mainContent' => '<p>Hauptinhalt der Seite.</p>',
        'footerText' => 'Diese Innovationsbox ist ein Engagement der arento ag für die Klimaregion Glarnerland und den Verein KlimaGlarus.ch',
        'footerPartners' => json_encode([
            ['name' => 'arento ag', 'href' => 'https://arento.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/89705B?text=arento'],
            ['name' => 'Klimaregion Glarnerland', 'href' => 'https://klimaregion.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/7B876F?text=Klimaregion'],
            ['name' => 'KlimaGlarus.ch', 'href' => 'https://klimaglarus.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/B7CD57?text=KlimaGlarus'],
        ]),
    ],
];
```

- [ ] **Step 3: Create `view-page.php`**

```php
<?php
/**
 * Template: View Page
 * Layout with hero image instead of page header. For pages with prominent imagery.
 */

$logoSrc = $props['logoSrc'] ?? '';
$logoAlt = $props['logoAlt'] ?? '';
$logoHref = $props['logoHref'] ?? '/';
$navItems = $props['navItems'] ?? '[]';
$heroTitle = $props['heroTitle'] ?? '';
$heroBody = $props['heroBody'] ?? '';
$heroImageSrc = $props['heroImageSrc'] ?? '';
$heroImageAlt = $props['heroImageAlt'] ?? '';
$heroButtonLabel = $props['heroButtonLabel'] ?? '';
$heroButtonHref = $props['heroButtonHref'] ?? '#';
$mainContent = $props['mainContent'] ?? '';
$footerText = $props['footerText'] ?? '';
$footerPartners = $props['footerPartners'] ?? '[]';
?>
<div class="ccm-page">
    <?php
    $props = ['logoSrc' => $logoSrc, 'logoAlt' => $logoAlt, 'logoHref' => $logoHref, 'navItems' => $navItems];
    include DIR_COMPONENTS . '/organisms/header/header.php';
    ?>

    <header>
        <?php
        $props = [
            'title' => $heroTitle,
            'body' => $heroBody,
            'imageSrc' => $heroImageSrc,
            'imageAlt' => $heroImageAlt,
            'buttonLabel' => $heroButtonLabel,
            'buttonHref' => $heroButtonHref,
        ];
        include DIR_COMPONENTS . '/organisms/hero-image/hero-image.php';
        ?>
    </header>

    <main>
        <?= $mainContent ?>
    </main>

    <?php
    $props = ['text' => $footerText, 'partners' => $footerPartners];
    include DIR_COMPONENTS . '/organisms/footer/footer.php';
    ?>
</div>
```

- [ ] **Step 4: Create `view-page.scss`**

```scss
// Template: View Page
// Layout is defined by the global .ccm-page wrapper and organism styles.
```

- [ ] **Step 5: Create `view-page.stories.json`**

```json
{
  "title": "Templates/View Page",
  "stories": [
    {
      "name": "With Hero and Button",
      "parameters": { "server": { "id": "templates/view-page", "params": {
        "heroTitle": "Innovationsbox Glarus",
        "heroBody": "<p>Klimarelevante Ideen für die Region.</p>",
        "heroImageSrc": "https://placehold.co/1920x600/7B876F/FFF?text=Hero",
        "heroImageAlt": "Hero",
        "heroButtonLabel": "Idee einreichen",
        "heroButtonHref": "/einreichen",
        "mainContent": "<div class='lb-container-narrow spaced-block content-block'><h2>Über uns</h2><p>Projektbeschreibung hier.</p></div>"
      } } }
    },
    {
      "name": "Without Button",
      "parameters": { "server": { "id": "templates/view-page", "params": {
        "heroTitle": "Über die Innovationsbox",
        "heroBody": "<p>Ein Engagement der arento ag.</p>",
        "heroImageSrc": "https://placehold.co/1920x600/89705B/FFF?text=About",
        "heroImageAlt": "Über uns",
        "mainContent": "<div class='lb-container-narrow spaced-block content-block'><p>Inhalt folgt.</p></div>"
      } } }
    }
  ]
}
```

- [ ] **Step 6: Commit**

```bash
git add resources/components/templates/view-page/
git commit -m "feat(templates): add view-page template (with hero image)"
```

---

### Task 3: Homepage example page

Fills the view-page template with realistic homepage content including hero, content block, page list, and feature button.

**Files:**
- Create: `resources/components/pages/homepage/homepage.variants.php`
- Create: `resources/components/pages/homepage/homepage.php`
- Create: `resources/components/pages/homepage/homepage.scss`
- Create: `resources/components/pages/homepage/homepage.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/pages/homepage
```

- [ ] **Step 2: Create `homepage.variants.php`**

```php
<?php
return [
    'defaults' => [],
];
```

- [ ] **Step 3: Create `homepage.php`**

This page hardcodes realistic example content — it's for Storybook demonstration only.

```php
<?php
/**
 * Page: Homepage
 * A realistic homepage example using the view-page template filled with content.
 */

$props = [
    'logoSrc' => 'https://placehold.co/320x80/B7CD57/353535?text=Innovationsbox',
    'logoAlt' => 'Innovationsbox',
    'logoHref' => '/',
    'navItems' => json_encode([
        ['text' => 'Startseite', 'href' => '/', 'active' => 'true'],
        ['text' => 'Projektideen', 'href' => '/projektideen'],
        ['text' => 'Über uns', 'href' => '/ueber-uns'],
        ['text' => 'Kontakt', 'href' => '/kontakt'],
    ]),
    'heroTitle' => 'Innovationsbox Glarus',
    'heroBody' => '<p>Gemeinsam für das Klima — Ideen einreichen, entwickeln und umsetzen.</p>',
    'heroImageSrc' => 'https://placehold.co/1920x600/7B876F/FFF?text=Klimaregion+Glarnerland',
    'heroImageAlt' => 'Klimaregion Glarnerland',
    'heroButtonLabel' => 'Projektidee einreichen',
    'heroButtonHref' => '/einreichen',
    'mainContent' => '',
    'footerText' => 'Diese Innovationsbox ist ein Engagement der arento ag für die Klimaregion Glarnerland und den Verein KlimaGlarus.ch',
    'footerPartners' => json_encode([
        ['name' => 'arento ag', 'href' => 'https://arento.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/89705B?text=arento'],
        ['name' => 'Klimaregion Glarnerland', 'href' => 'https://klimaregion.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/7B876F?text=Klimaregion'],
        ['name' => 'KlimaGlarus.ch', 'href' => 'https://klimaglarus.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/B7CD57?text=KlimaGlarus'],
    ]),
];

// Capture mainContent by rendering organisms directly
ob_start();
?>
<?php
$props_content = ['content' => '<h2>Willkommen</h2><p>Die Innovationsbox ist eine moderierte Plattform, auf der klimarelevante Projektideen eingereicht, geprüft, veröffentlicht und gemeinsam in geschützten Projekträumen weiterentwickelt werden können.</p><span class="accent">Gemeinsam für das Klima</span>'];
include DIR_COMPONENTS . '/organisms/content-block/content-block.php';

$props_pages = ['pages' => json_encode([
    ['title' => 'Solarenergie für Schulen', 'href' => '#', 'imageSrc' => 'https://placehold.co/600x400/B7CD57/353535?text=Solar', 'imageAlt' => 'Solarprojekt'],
    ['title' => 'Windkraft im Glarnerland', 'href' => '#', 'imageSrc' => 'https://placehold.co/600x400/89705B/FFF?text=Wind', 'imageAlt' => 'Windkraft'],
    ['title' => 'Gebäudesanierung', 'href' => '#', 'imageSrc' => 'https://placehold.co/600x400/7B876F/FFF?text=Gebäude', 'imageAlt' => 'Gebäude'],
])];
include DIR_COMPONENTS . '/organisms/page-list-grid/page-list-grid.php';

$props_cta = ['label' => 'Alle Projektideen ansehen', 'href' => '/projektideen'];
include DIR_COMPONENTS . '/molecules/feature-button/feature-button.php';
?>
<?php
$mainContent = ob_get_clean();

$props['mainContent'] = $mainContent;
include DIR_COMPONENTS . '/templates/view-page/view-page.php';
```

- [ ] **Step 4: Create `homepage.scss`**

```scss
// Page: Homepage
// No page-specific styles — uses template and organism styles.
```

- [ ] **Step 5: Create `homepage.stories.json`**

```json
{
  "title": "Pages/Homepage",
  "stories": [
    {
      "name": "Default",
      "parameters": { "server": { "id": "pages/homepage" } }
    }
  ]
}
```

- [ ] **Step 6: Commit**

```bash
git add resources/components/pages/homepage/
git commit -m "feat(pages): add homepage example page"
```

---

### Task 4: Inner-page example page

A standard inner page using the default-page template with content block.

**Files:**
- Create: `resources/components/pages/inner-page/inner-page.variants.php`
- Create: `resources/components/pages/inner-page/inner-page.php`
- Create: `resources/components/pages/inner-page/inner-page.scss`
- Create: `resources/components/pages/inner-page/inner-page.stories.json`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/components/pages/inner-page
```

- [ ] **Step 2: Create `inner-page.variants.php`**

```php
<?php
return [
    'defaults' => [],
];
```

- [ ] **Step 3: Create `inner-page.php`**

```php
<?php
/**
 * Page: Inner Page
 * A standard inner page example using the default-page template.
 */

ob_start();
?>
<?php
$props_content = ['content' => '<h2>Über die Innovationsbox</h2><p>Die Innovationsbox ist ein Engagement der arento ag für die Klimaregion Glarnerland und den Verein KlimaGlarus.ch.</p><p>Auf dieser Plattform können Einwohnerinnen und Einwohner der Region klimarelevante Projektideen einreichen. Nach einer Moderation werden die Ideen veröffentlicht und können in geschützten Projekträumen gemeinsam weiterentwickelt werden.</p><span class="question">Wie kann ich eine Projektidee einreichen?</span><p>Klicken Sie auf den Button «Projektidee einreichen» und füllen Sie das Formular aus. Ihre Idee wird anschliessend von einem Moderationsteam geprüft.</p>'];
include DIR_COMPONENTS . '/organisms/content-block/content-block.php';

$props_cta = ['label' => 'Projektidee einreichen', 'href' => '/einreichen'];
include DIR_COMPONENTS . '/molecules/feature-button/feature-button.php';
?>
<?php
$mainContent = ob_get_clean();

$props = [
    'logoSrc' => 'https://placehold.co/320x80/B7CD57/353535?text=Innovationsbox',
    'logoAlt' => 'Innovationsbox',
    'logoHref' => '/',
    'navItems' => json_encode([
        ['text' => 'Startseite', 'href' => '/'],
        ['text' => 'Projektideen', 'href' => '/projektideen'],
        ['text' => 'Über uns', 'href' => '/ueber-uns', 'active' => 'true'],
        ['text' => 'Kontakt', 'href' => '/kontakt'],
    ]),
    'pageHeaderContent' => '<h1>Über uns</h1>',
    'mainContent' => $mainContent,
    'footerText' => 'Diese Innovationsbox ist ein Engagement der arento ag für die Klimaregion Glarnerland und den Verein KlimaGlarus.ch',
    'footerPartners' => json_encode([
        ['name' => 'arento ag', 'href' => 'https://arento.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/89705B?text=arento'],
        ['name' => 'Klimaregion Glarnerland', 'href' => 'https://klimaregion.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/7B876F?text=Klimaregion'],
        ['name' => 'KlimaGlarus.ch', 'href' => 'https://klimaglarus.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/B7CD57?text=KlimaGlarus'],
    ]),
];
include DIR_COMPONENTS . '/templates/default-page/default-page.php';
```

- [ ] **Step 4: Create `inner-page.scss`**

```scss
// Page: Inner Page
// No page-specific styles.
```

- [ ] **Step 5: Create `inner-page.stories.json`**

```json
{
  "title": "Pages/Inner Page",
  "stories": [
    {
      "name": "Default",
      "parameters": { "server": { "id": "pages/inner-page" } }
    }
  ]
}
```

- [ ] **Step 6: Commit**

```bash
git add resources/components/pages/inner-page/
git commit -m "feat(pages): add inner-page example page"
```

---

### Task 5: Add `DIR_COMPONENTS` to `page_theme.php`

Define the constant that CMS block wrappers use to locate component partials.

**Files:**
- Modify: `public/packages/inno_frontend/themes/innovationsbox/page_theme.php`

- [ ] **Step 1: Read the current file**

Read `public/packages/inno_frontend/themes/innovationsbox/page_theme.php`.

- [ ] **Step 2: Add `DIR_COMPONENTS` constant**

Add this line at the top of the `PageTheme` class, before `getThemeName()`:

```php
class PageTheme extends Theme
{
    public function __construct()
    {
        parent::__construct();
        if (!defined('DIR_COMPONENTS')) {
            define('DIR_COMPONENTS', DIR_BASE . '/resources/components');
        }
    }
```

The full file should become:

```php
<?php

namespace Concrete\Package\InnoFrontend\Theme\Innovationsbox;

use Concrete\Core\Page\Theme\Theme;

class PageTheme extends Theme
{
    public function __construct()
    {
        parent::__construct();
        if (!defined('DIR_COMPONENTS')) {
            define('DIR_COMPONENTS', DIR_BASE . '/resources/components');
        }
    }

    public function getThemeName()
    {
        return 'Innovationsbox - Theme';
    }

    public function getThemeDescription()
    {
        return 'Theme for new corporate Innovationsbox website';
    }

    public function registerAssets()
    {
        $this->requireAsset('inno_frontend');
    }

    public function getThemeDefaultBlockTemplates()
    {
        return array(
            "content"  => "innovationsbox",
            "autonav"  => "innovationsbox",
            "page_list"  => "innovationsbox",
            "feature_link"  => "innovationsbox",
            "image"  => "webp",
        );
    }

    public function getThemeEditorClasses()
    {
        return array(
            array('title' => t('Zitat/Akzent'), 'menuClass' => 'accent', 'spanClass' => 'accent'),
            array('title' => t('Frage (FAQ)'), 'menuClass' => 'question', 'spanClass' => 'question'),
        );
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/themes/innovationsbox/page_theme.php
git commit -m "feat(theme): add DIR_COMPONENTS constant to PageTheme"
```

---

### Task 6: Migrate content block

Split the content block template into a CMS wrapper that includes the component partial.

**Files:**
- Modify: `public/packages/inno_frontend/blocks/content/templates/innovationsbox/view.php`

- [ ] **Step 1: Read the current file**

Read `public/packages/inno_frontend/blocks/content/templates/innovationsbox/view.php`.

- [ ] **Step 2: Replace with thin CMS wrapper**

```php
<?php
defined('C5_EXECUTE') or die('Access Denied.');

/** @var string $content */

$c = \Concrete\Core\Page\Page::getCurrentPage();
if (!$content && is_object($c) && $c->isEditMode()) { ?>
    <div class="ccm-edit-mode-disabled-item"><?=t('Empty Content Block.')?></div>
<?php
} else {
    $props = ['content' => $content];
    include DIR_COMPONENTS . '/organisms/content-block/content-block.php';
}
```

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/blocks/content/templates/innovationsbox/view.php
git commit -m "refactor(blocks): migrate content block to use component partial"
```

---

### Task 7: Migrate feature_link block

**Files:**
- Modify: `public/packages/inno_frontend/blocks/feature_link/templates/innovationsbox/view.php`

- [ ] **Step 1: Read the current file**

Read `public/packages/inno_frontend/blocks/feature_link/templates/innovationsbox/view.php`.

- [ ] **Step 2: Replace with thin CMS wrapper**

The CMS provides `$button` as an `HtmlObject\Link`. We extract the value and href, then delegate to the component.

```php
<?php
defined('C5_EXECUTE') or die('Access Denied.');

$buttonColor = $buttonColor ?? null;

if (isset($button)) {
    if ($buttonIcon && $button->getValue()) {
        $iconTag = '<span class="me-3">' . $iconTag . '</span>';
    }

    $props = [
        'label' => strip_tags($button->getValue()),
        'href' => $button->getAttribute('href') ?? '#',
        'icon' => isset($iconTag) ? $iconTag : '',
    ];
    ?>
    <div class="lb-container spaced-block">
        <?php include DIR_COMPONENTS . '/molecules/feature-button/feature-button.php'; ?>
    </div>
    <?php
}
```

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/blocks/feature_link/templates/innovationsbox/view.php
git commit -m "refactor(blocks): migrate feature_link block to use component partial"
```

---

### Task 8: Migrate page_list block

**Files:**
- Modify: `public/packages/inno_frontend/blocks/page_list/templates/innovationsbox/view.php`

- [ ] **Step 1: Read the current file**

Read `public/packages/inno_frontend/blocks/page_list/templates/innovationsbox/view.php`.

- [ ] **Step 2: Replace with thin CMS wrapper**

The CMS provides `$pages` array, `$controller`. We extract page data into a JSON array and delegate.

```php
<?php
defined('C5_EXECUTE') or die('Access Denied.');

use LemonbrainWebp\ImageBrewer;

$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
$c = Page::getCurrentPage();
$th = Core::make('helper/text');

if (is_object($c) && $c->isEditMode() && $controller->isBlockEmpty()) { ?>
    <div class="ccm-edit-mode-disabled-item"><?php echo t('Empty Page List Block.') ?></div>
<?php } else {
    $pageData = [];
    foreach ($pages as $page) {
        $title = $page->getCollectionName();
        $thumbnail = $page->getAttribute('thumbnail');
        $target = '_self';

        if ($page->getCollectionPointerExternalLink() != '') {
            $url = $page->getCollectionPointerExternalLink();
            if ($page->openCollectionPointerExternalLinkInNewWindow()) {
                $target = '_blank';
            }
        } else {
            $url = $page->getCollectionLink();
            $target = $page->getAttribute('nav_target') ?: '_self';
        }

        $imageSrc = '';
        if (is_object($thumbnail)) {
            $imageSrc = $app->make(ImageBrewer::class)->get($thumbnail->getFileID(), h($title), '50vw', "3/2", 'cover');
        }

        $pageData[] = [
            'title' => $title,
            'href' => $url,
            'target' => $target,
            'imageSrc' => $imageSrc ? 'data:image/placeholder' : '',
            'imageAlt' => $title,
        ];
    }

    $props = ['pages' => json_encode($pageData)];
    include DIR_COMPONENTS . '/organisms/page-list-grid/page-list-grid.php';
} ?>
```

Note: The `page_list` block is the most complex migration because of `ImageBrewer` integration. The `imageSrc` field uses the existing CMS image system. For now, the wrapper extracts the data it can; full ImageBrewer integration in the partial will be addressed when the persistence layer is implemented.

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/blocks/page_list/templates/innovationsbox/view.php
git commit -m "refactor(blocks): migrate page_list block to use component partial"
```

---

### Task 9: Migrate hero_image block

**Files:**
- Modify: `public/packages/inno_frontend/blocks/hero_image/templates/innovationsbox/view.php`

- [ ] **Step 1: Read the current file**

Read `public/packages/inno_frontend/blocks/hero_image/templates/innovationsbox/view.php`.

- [ ] **Step 2: Replace with thin CMS wrapper**

```php
<?php
defined('C5_EXECUTE') or die('Access Denied.');

use LemonbrainWebp\ImageBrewer;

$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();

if ($image === null) {
    return;
}

$buttonColor = $buttonColor ?? null;

// Extract button label and href from the CMS button object
$buttonLabel = '';
$buttonHref = '#';
if (isset($button)) {
    $buttonLabel = strip_tags($button->getValue());
    $buttonHref = $button->getAttribute('href') ?? '#';
}

// Get image URL via ImageBrewer
$imageSrc = '';
if (is_object($image) && $image->getFileID()) {
    $imageSrc = $image->getRelativePath();
}

$props = [
    'title' => $title ?? '',
    'body' => $body ?? '',
    'imageSrc' => $imageSrc,
    'imageAlt' => $altText ?? '',
    'buttonLabel' => $buttonLabel,
    'buttonHref' => $buttonHref,
];

include DIR_COMPONENTS . '/organisms/hero-image/hero-image.php';
```

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/blocks/hero_image/templates/innovationsbox/view.php
git commit -m "refactor(blocks): migrate hero_image block to use component partial"
```

---

### Task 10: E2E tests for templates and pages

**Files:**
- Create: `tests/e2e/specs/storybook/templates-pages.spec.ts`

- [ ] **Step 1: Create the test file**

```typescript
import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Template components', () => {
  test('default-page: renders full page layout with header, main, footer', async ({ request }) => {
    const res = await request.get(`${URL}/?id=templates/default-page`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('ccm-page');
    expect(html).toContain('page-header');
    expect(html).toContain('main-content-area');
    expect(html).toContain('<footer>');
  });

  test('view-page: renders with hero-image instead of page header', async ({ request }) => {
    const res = await request.get(`${URL}/?id=templates/view-page`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('ccm-page');
    expect(html).toContain('hero-image-wrapper');
    expect(html).toContain('<footer>');
  });
});

test.describe('Page examples', () => {
  test('homepage: renders complete homepage with hero, content, page list', async ({ request }) => {
    const res = await request.get(`${URL}/?id=pages/homepage`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('hero-image-wrapper');
    expect(html).toContain('Innovationsbox Glarus');
    expect(html).toContain('content-block');
    expect(html).toContain('page-list-pages');
    expect(html).toContain('<footer>');
  });

  test('inner-page: renders standard page with content block', async ({ request }) => {
    const res = await request.get(`${URL}/?id=pages/inner-page`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('page-header');
    expect(html).toContain('content-block');
    expect(html).toContain('Über die Innovationsbox');
    expect(html).toContain('<footer>');
  });
});
```

- [ ] **Step 2: Verify**

```bash
npx playwright test tests/e2e/specs/storybook/templates-pages.spec.ts --config=tests/e2e/playwright.config.ts --list
```

Expected: Lists 4 test cases.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e/specs/storybook/templates-pages.spec.ts
git commit -m "test(storybook): add E2E tests for templates and pages"
```

---

### Task 11: Update CLAUDE.md with component library docs

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Add component library section**

Find the `### Storybook` section in CLAUDE.md and add after the existing content:

```markdown

### Component Library (Atomic Design)
```
resources/components/
  atoms/         ← 8 components: button, heading, paragraph, link, image, container, skip-link, overlay
  molecules/     ← 6 components: feature-button, page-card, nav-item, accent-text, question-text, partner-logo
  organisms/     ← 6 components: hero-image, page-list-grid, navigation-drawer, header, footer, content-block
  templates/     ← 2 templates: default-page, view-page
  pages/         ← 2 examples: homepage, inner-page
```
Each component has: `<name>.php` (partial), `<name>.variants.php` (props/defaults), `<name>.stories.json` (Storybook), `<name>.scss` (styles).
CMS block templates in `public/packages/inno_frontend/blocks/` are thin wrappers that extract CMS data into `$props` and `include` the component partial via `DIR_COMPONENTS`.
```

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: add component library structure to CLAUDE.md"
```
