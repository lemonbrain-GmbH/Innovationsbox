# Storybook Shared Fixtures — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a shared PHP fixtures file with realistic content from the reference site. All 24 component variants load from this single source.

**Architecture:** One `_fixtures/sample-data.php` file returns an associative array of reusable content. Each `variants.php` requires it and picks what it needs. Stories.json files are simplified to rely on variant defaults where possible.

**Tech Stack:** PHP (variants), JSON (stories)

---

### Task 1: Create the shared fixtures file

**Files:**
- Create: `public/packages/inno_frontend/components/_fixtures/sample-data.php`

- [ ] **Step 1: Create the fixtures directory and file**

Create `public/packages/inno_frontend/components/_fixtures/sample-data.php` with:

```php
<?php

declare(strict_types=1);

/**
 * Shared sample data for Storybook component variants.
 * Content extracted from the reference site (innovationsbox.lemonbrain.ch).
 * All components load their defaults from here — single source of truth.
 */
return [
    'site' => [
        'name' => 'Innovationsbox',
        'logoSrc' => 'https://placehold.co/320x80/B7CD57/353535?text=InnovationsBox',
        'logoAlt' => 'Innovationsbox Logo',
        'logoHref' => '/',
    ],

    'nav' => [
        ['text' => 'Projektidee finden', 'href' => '/projektidee-finden', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
        ['text' => 'Projektidee einbringen', 'href' => '/projektidee-einbringen', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
        ['text' => 'Übersicht Projektideen', 'href' => '/uebersicht-projektideen', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => true, 'subDepth' => 0],
        ['text' => 'Bauen', 'href' => '/uebersicht-projektideen/bauen', 'target' => '_self', 'active' => 'false', 'level' => '2', 'hasSubmenu' => false, 'subDepth' => 0],
        ['text' => 'Wohnen', 'href' => '/uebersicht-projektideen/wohnen', 'target' => '_self', 'active' => 'false', 'level' => '2', 'hasSubmenu' => false, 'subDepth' => 0],
        ['text' => 'Landwirtschaft', 'href' => '/uebersicht-projektideen/landwirtschaft', 'target' => '_self', 'active' => 'false', 'level' => '2', 'hasSubmenu' => false, 'subDepth' => 0],
        ['text' => 'Tourismus', 'href' => '/uebersicht-projektideen/tourismus', 'target' => '_self', 'active' => 'false', 'level' => '2', 'hasSubmenu' => false, 'subDepth' => 0],
        ['text' => 'Mobilität', 'href' => '/uebersicht-projektideen/mobilitaet', 'target' => '_self', 'active' => 'false', 'level' => '2', 'hasSubmenu' => false, 'subDepth' => 1],
        ['text' => 'Expertinnen-Pool', 'href' => '/expertinnen-pool', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
        ['text' => 'News', 'href' => '/news', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
        ['text' => 'Infos / FAQ', 'href' => '/infos-faq', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
        ['text' => 'Kontakt', 'href' => '/kontakt', 'target' => '_self', 'active' => 'false', 'level' => '1', 'hasSubmenu' => false, 'subDepth' => 0],
    ],

    'hero' => [
        'title' => 'Innovationsbox',
        'body' => '<p>für alle, die Klimaschutzideen erfolgreich entwickeln und umsetzen wollen</p>',
        'imageSrc' => 'https://placehold.co/1920x600/7B876F/FFF?text=Berglandschaft+Glarus',
        'imageAlt' => 'Berglandschaft Glarus',
    ],

    'categories' => [
        ['title' => 'Bauen', 'href' => '/uebersicht-projektideen/bauen', 'imageSrc' => 'https://placehold.co/600x400/B7CD57/353535?text=Bauen', 'imageAlt' => 'Bauen'],
        ['title' => 'Wohnen', 'href' => '/uebersicht-projektideen/wohnen', 'imageSrc' => 'https://placehold.co/600x400/89705B/FFF?text=Wohnen', 'imageAlt' => 'Wohnen'],
        ['title' => 'Landwirtschaft', 'href' => '/uebersicht-projektideen/landwirtschaft', 'imageSrc' => 'https://placehold.co/600x400/7B876F/FFF?text=Landwirtschaft', 'imageAlt' => 'Landwirtschaft'],
        ['title' => 'Tourismus', 'href' => '/uebersicht-projektideen/tourismus', 'imageSrc' => 'https://placehold.co/600x400/B7CD57/353535?text=Tourismus', 'imageAlt' => 'Tourismus'],
        ['title' => 'Mobilität', 'href' => '/uebersicht-projektideen/mobilitaet', 'imageSrc' => 'https://placehold.co/600x400/89705B/FFF?text=Mobilität', 'imageAlt' => 'Mobilität'],
    ],

    'partners' => [
        ['name' => 'arento ag', 'href' => 'https://arento.ch', 'imageSrc' => 'https://placehold.co/120x60/FFF/89705B?text=arento'],
        ['name' => 'Klimaregion Glarnerland', 'href' => 'https://www.klimaglarus.ch/', 'imageSrc' => 'https://placehold.co/120x60/FFF/7B876F?text=Klimaregion'],
        ['name' => 'KlimaGlarus.ch', 'href' => 'https://www.klimaglarus.ch/', 'imageSrc' => 'https://placehold.co/120x60/FFF/B7CD57?text=KlimaGlarus'],
    ],

    'content' => [
        'quote' => 'Die Innovationsbox stellt digitale Räume zur Verfügung, in welchen sich innovative Entwicklerinnen mit anderen Menschen treffen können, um klimafreundliche Ideen gemeinsam zu entwickeln und zum Erfolg zu bringen.',
        'callToAction' => 'Jede erfolgreiche Innovation hilft dem Klimaschutz – sei Teil dieser Innovationsbox!',
        'introTitle' => 'Die Innovationsbox',
        'introText' => 'Diese digitale Plattform dient zur gemeinsamen Entwicklung klimafreundlicher Ideen und Konzepte welche als Team erfolgreich auf den Markt gebracht werden können. Nebst dem Klimanutzen soll ein kleiner Teil des Gewinns erfolgreicher Projekte der Klimaregion Glarnerland zugutekommen.',
        'aboutTitle' => 'Über uns',
        'aboutText' => 'Initiiert und finanziert wird die Innovationsbox von der Firma arento ag, welche diese der Klimaregion Glarnerland und dem Verein KlimaGlarus.ch zur Verfügung stellt. Der Verein KlimaGlarus.ch lanciert und unterstützt Klimaschutzprojekte im Glarnerland.',
        'faqQuestions' => [
            'Welche Projektideen werden in dieser Box bearbeitet?',
            'Wer prüft die Projektideen, bevor ein Raum geöffnet wird?',
            'Wie kann ich eine Innovation zum Erfolg bringen?',
        ],
    ],

    'buttons' => [
        'submit' => 'Ich möchte eine Projektidee einbringen',
        'expertise' => 'Ich stelle gerne meine Expertise zur Verfügung',
        'moreInfo' => 'Mehr Informationen',
    ],

    'footer' => [
        'text' => 'Diese Innovationsbox ist ein Engagement der arento ag für die Klimaregion Glarnerland und den Verein KlimaGlarus.ch',
    ],
];
```

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_frontend/components/_fixtures/sample-data.php
git commit -m "feat: add shared Storybook sample data fixtures"
```

---

### Task 2: Update atom variants (8 files)

**Files:**
- Modify: `public/packages/inno_frontend/components/atoms/button/button.variants.php`
- Modify: `public/packages/inno_frontend/components/atoms/container/container.variants.php`
- Modify: `public/packages/inno_frontend/components/atoms/heading/heading.variants.php`
- Modify: `public/packages/inno_frontend/components/atoms/image/image.variants.php`
- Modify: `public/packages/inno_frontend/components/atoms/link/link.variants.php`
- Modify: `public/packages/inno_frontend/components/atoms/overlay/overlay.variants.php`
- Modify: `public/packages/inno_frontend/components/atoms/paragraph/paragraph.variants.php`
- Modify: `public/packages/inno_frontend/components/atoms/skip-link/skip-link.variants.php` (no change — already has correct static data)

- [ ] **Step 1: Update button.variants.php**

Replace the entire file:

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'variants' => ['primary', 'secondary'],
    'sizes' => ['default', 'small', 'large'],
    'defaults' => [
        'variant' => 'primary',
        'size' => 'default',
        'label' => $data['buttons']['submit'],
        'href' => '#',
    ],
];
```

- [ ] **Step 2: Update container.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'variants' => ['default', 'medium', 'small', 'narrow'],
    'defaults' => [
        'variant' => 'default',
        'spaced' => 'false',
        'content' => '<p>' . $data['content']['introText'] . '</p>',
    ],
];
```

- [ ] **Step 3: Update heading.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'levels' => [1, 2, 3, 4, 5, 6],
    'defaults' => [
        'level' => '2',
        'text' => $data['content']['introTitle'],
    ],
];
```

- [ ] **Step 4: Update image.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'fits' => ['cover', 'contain'],
    'ratios' => ['auto', '16/9', '3/2', '4/3', '1/1'],
    'defaults' => [
        'src' => $data['categories'][0]['imageSrc'],
        'alt' => $data['categories'][0]['imageAlt'],
        'ratio' => 'auto',
        'fit' => 'cover',
        'rounded' => 'false',
        'width' => '100%',
    ],
];
```

- [ ] **Step 5: Update link.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'targets' => ['_self', '_blank'],
    'defaults' => [
        'text' => $data['buttons']['moreInfo'],
        'href' => '#',
        'target' => '_self',
    ],
];
```

- [ ] **Step 6: Update overlay.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'variants' => ['light', 'dark'],
    'defaults' => [
        'variant' => 'light',
        'content' => '<h2>' . $data['hero']['title'] . '</h2>' . $data['hero']['body'],
    ],
];
```

- [ ] **Step 7: Update paragraph.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'variants' => ['body', 'lead', 'small'],
    'defaults' => [
        'variant' => 'body',
        'text' => $data['content']['introText'],
    ],
];
```

- [ ] **Step 8: skip-link.variants.php — no change needed** (already uses correct static text)

- [ ] **Step 9: Commit**

```bash
git add public/packages/inno_frontend/components/atoms/
git commit -m "refactor: update atom variants to use shared fixtures"
```

---

### Task 3: Update molecule variants (6 files)

**Files:**
- Modify: `public/packages/inno_frontend/components/molecules/accent-text/accent-text.variants.php`
- Modify: `public/packages/inno_frontend/components/molecules/feature-button/feature-button.variants.php`
- Modify: `public/packages/inno_frontend/components/molecules/nav-item/nav-item.variants.php`
- Modify: `public/packages/inno_frontend/components/molecules/page-card/page-card.variants.php`
- Modify: `public/packages/inno_frontend/components/molecules/partner-logo/partner-logo.variants.php`
- Modify: `public/packages/inno_frontend/components/molecules/question-text/question-text.variants.php`

- [ ] **Step 1: Update accent-text.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'text' => $data['content']['quote'],
    ],
];
```

- [ ] **Step 2: Update feature-button.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'label' => $data['buttons']['submit'],
        'href' => '#',
        'icon' => '',
    ],
];
```

- [ ] **Step 3: Update nav-item.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'text' => $data['nav'][0]['text'],
        'href' => $data['nav'][0]['href'],
        'active' => 'false',
        'level' => '1',
    ],
];
```

- [ ] **Step 4: Update page-card.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
$cat = $data['categories'][0];
return [
    'defaults' => [
        'title' => $cat['title'],
        'href' => $cat['href'],
        'target' => '_self',
        'imageSrc' => $cat['imageSrc'],
        'imageAlt' => $cat['imageAlt'],
    ],
];
```

- [ ] **Step 5: Update partner-logo.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
$partner = $data['partners'][0];
return [
    'defaults' => [
        'name' => $partner['name'],
        'href' => $partner['href'],
        'imageSrc' => $partner['imageSrc'],
    ],
];
```

- [ ] **Step 6: Update question-text.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'text' => $data['content']['faqQuestions'][0],
    ],
];
```

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/
git commit -m "refactor: update molecule variants to use shared fixtures"
```

---

### Task 4: Update organism variants (6 files)

**Files:**
- Modify: `public/packages/inno_frontend/components/organisms/content-block/content-block.variants.php`
- Modify: `public/packages/inno_frontend/components/organisms/footer/footer.variants.php`
- Modify: `public/packages/inno_frontend/components/organisms/header/header.variants.php`
- Modify: `public/packages/inno_frontend/components/organisms/hero-image/hero-image.variants.php`
- Modify: `public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.variants.php`
- Modify: `public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.variants.php`

- [ ] **Step 1: Update content-block.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'content' => '<h3>' . $data['content']['introTitle'] . '</h3><p>' . $data['content']['introText'] . '</p>',
    ],
];
```

- [ ] **Step 2: Update footer.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'text' => $data['footer']['text'],
        'partners' => json_encode($data['partners']),
    ],
];
```

- [ ] **Step 3: Update header.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'logoSrc' => $data['site']['logoSrc'],
        'logoAlt' => $data['site']['logoAlt'],
        'logoHref' => $data['site']['logoHref'],
        'navItems' => json_encode($data['nav']),
    ],
];
```

- [ ] **Step 4: Update hero-image.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'title' => $data['hero']['title'],
        'body' => $data['hero']['body'],
        'imageSrc' => $data['hero']['imageSrc'],
        'imageAlt' => $data['hero']['imageAlt'],
        'buttonLabel' => $data['buttons']['submit'],
        'buttonHref' => '#',
    ],
];
```

- [ ] **Step 5: Update navigation-drawer.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'logoSrc' => $data['site']['logoSrc'],
        'logoAlt' => $data['site']['logoAlt'],
        'logoHref' => $data['site']['logoHref'],
        'items' => json_encode($data['nav']),
    ],
];
```

- [ ] **Step 6: Update page-list-grid.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'pages' => json_encode($data['categories']),
    ],
];
```

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_frontend/components/organisms/
git commit -m "refactor: update organism variants to use shared fixtures"
```

---

### Task 5: Update template and page variants (4 files)

**Files:**
- Modify: `public/packages/inno_frontend/components/templates/default-page/default-page.variants.php`
- Modify: `public/packages/inno_frontend/components/templates/view-page/view-page.variants.php`
- Modify: `public/packages/inno_frontend/components/pages/homepage/homepage.variants.php`
- Modify: `public/packages/inno_frontend/components/pages/inner-page/inner-page.variants.php`

- [ ] **Step 1: Update default-page.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'logoSrc' => $data['site']['logoSrc'],
        'logoAlt' => $data['site']['logoAlt'],
        'logoHref' => $data['site']['logoHref'],
        'navItems' => json_encode($data['nav']),
        'pageHeaderContent' => '<h1>Infos / FAQ</h1>',
        'mainContent' => '<div class="lb-container-narrow spaced-block content-block"><h3>' . $data['content']['aboutTitle'] . '</h3><p>' . $data['content']['aboutText'] . '</p></div>',
        'footerText' => $data['footer']['text'],
        'footerPartners' => json_encode($data['partners']),
    ],
];
```

- [ ] **Step 2: Update view-page.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'logoSrc' => $data['site']['logoSrc'],
        'logoAlt' => $data['site']['logoAlt'],
        'logoHref' => $data['site']['logoHref'],
        'navItems' => json_encode($data['nav']),
        'heroTitle' => $data['hero']['title'],
        'heroBody' => $data['hero']['body'],
        'heroImageSrc' => $data['hero']['imageSrc'],
        'heroImageAlt' => $data['hero']['imageAlt'],
        'heroButtonLabel' => $data['buttons']['submit'],
        'heroButtonHref' => '#',
        'mainContent' => '<div class="lb-container-narrow spaced-block content-block"><h3>' . $data['content']['introTitle'] . '</h3><p>' . $data['content']['introText'] . '</p></div>',
        'footerText' => $data['footer']['text'],
        'footerPartners' => json_encode($data['partners']),
    ],
];
```

- [ ] **Step 3: Update homepage.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'logoSrc' => $data['site']['logoSrc'],
        'logoAlt' => $data['site']['logoAlt'],
        'logoHref' => $data['site']['logoHref'],
        'navItems' => json_encode($data['nav']),
        'heroTitle' => $data['hero']['title'],
        'heroBody' => $data['hero']['body'],
        'heroImageSrc' => $data['hero']['imageSrc'],
        'heroImageAlt' => $data['hero']['imageAlt'],
        'categories' => json_encode($data['categories']),
        'footerText' => $data['footer']['text'],
        'footerPartners' => json_encode($data['partners']),
    ],
];
```

- [ ] **Step 4: Update inner-page.variants.php**

```php
<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'defaults' => [
        'logoSrc' => $data['site']['logoSrc'],
        'logoAlt' => $data['site']['logoAlt'],
        'logoHref' => $data['site']['logoHref'],
        'navItems' => json_encode($data['nav']),
        'pageTitle' => 'Infos / FAQ',
        'mainContent' => '<div class="lb-container-narrow spaced-block content-block"><span class="question">' . $data['content']['faqQuestions'][0] . '</span><p>' . $data['content']['introText'] . '</p></div>',
        'footerText' => $data['footer']['text'],
        'footerPartners' => json_encode($data['partners']),
    ],
];
```

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/templates/ public/packages/inno_frontend/components/pages/
git commit -m "refactor: update template and page variants to use shared fixtures"
```

---

### Task 6: Simplify stories.json files

Many stories.json files duplicate data that now comes from variants.php defaults. Simplify them to only override what differs per story.

**Files to modify:** stories.json files that have inline params matching the new variant defaults.

- [ ] **Step 1: Simplify atom stories**

Replace `button.stories.json`:
```json
{
  "title": "Atoms/Button",
  "stories": [
    { "name": "Primary", "parameters": { "server": { "id": "atoms/button" } } },
    { "name": "Secondary", "parameters": { "server": { "id": "atoms/button", "params": { "variant": "secondary" } } } },
    { "name": "Small", "parameters": { "server": { "id": "atoms/button", "params": { "size": "small" } } } },
    { "name": "Large", "parameters": { "server": { "id": "atoms/button", "params": { "size": "large" } } } }
  ]
}
```

Replace `heading.stories.json`:
```json
{
  "title": "Atoms/Heading",
  "stories": [
    { "name": "H1", "parameters": { "server": { "id": "atoms/heading", "params": { "level": "1" } } } },
    { "name": "H2", "parameters": { "server": { "id": "atoms/heading" } } },
    { "name": "H3", "parameters": { "server": { "id": "atoms/heading", "params": { "level": "3" } } } },
    { "name": "H4", "parameters": { "server": { "id": "atoms/heading", "params": { "level": "4" } } } }
  ]
}
```

Replace `paragraph.stories.json`:
```json
{
  "title": "Atoms/Paragraph",
  "stories": [
    { "name": "Body", "parameters": { "server": { "id": "atoms/paragraph" } } },
    { "name": "Lead", "parameters": { "server": { "id": "atoms/paragraph", "params": { "variant": "lead" } } } },
    { "name": "Small", "parameters": { "server": { "id": "atoms/paragraph", "params": { "variant": "small" } } } }
  ]
}
```

Replace `skip-link.stories.json`:
```json
{
  "title": "Atoms/Skip Link",
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "atoms/skip-link" } } }
  ]
}
```

Replace `accent-text.stories.json`:
```json
{
  "title": "Molecules/Accent Text",
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/accent-text" } } }
  ]
}
```

Replace `feature-button.stories.json`:
```json
{
  "title": "Molecules/Feature Button",
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/feature-button" } } },
    { "name": "With Icon", "parameters": { "server": { "id": "molecules/feature-button", "params": { "icon": "↓" } } } }
  ]
}
```

Replace `question-text.stories.json`:
```json
{
  "title": "Molecules/Question Text",
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "molecules/question-text" } } }
  ]
}
```

- [ ] **Step 2: Simplify organism stories**

Replace `footer.stories.json`:
```json
{
  "title": "Organisms/Footer",
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "organisms/footer" } } }
  ]
}
```

Replace `header.stories.json`:
```json
{
  "title": "Organisms/Header",
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "organisms/header" } } }
  ]
}
```

Replace `hero-image.stories.json`:
```json
{
  "title": "Organisms/Hero Image",
  "stories": [
    { "name": "With Button", "parameters": { "server": { "id": "organisms/hero-image" } } },
    { "name": "Without Button", "parameters": { "server": { "id": "organisms/hero-image", "params": { "buttonLabel": "" } } } }
  ]
}
```

Replace `page-list-grid.stories.json`:
```json
{
  "title": "Organisms/Page List Grid",
  "stories": [
    { "name": "Five Categories", "parameters": { "server": { "id": "organisms/page-list-grid" } } }
  ]
}
```

Replace `content-block.stories.json`:
```json
{
  "title": "Organisms/Content Block",
  "stories": [
    { "name": "Plain Text", "parameters": { "server": { "id": "organisms/content-block" } } },
    { "name": "With Accent Quote", "parameters": { "server": { "id": "organisms/content-block", "params": { "content": "<p>Unser Motto:</p><span class=\"accent\">Die Innovationsbox stellt digitale Räume zur Verfügung, in welchen sich innovative Entwicklerinnen mit anderen Menschen treffen können, um klimafreundliche Ideen gemeinsam zu entwickeln und zum Erfolg zu bringen.</span>" } } } },
    { "name": "With FAQ Question", "parameters": { "server": { "id": "organisms/content-block", "params": { "content": "<span class=\"question\">Welche Projektideen werden in dieser Box bearbeitet?</span><p>Es dürfen alle Projektideen eingereicht werden, welche dem Klimaschutz helfen.</p>" } } } }
  ]
}
```

- [ ] **Step 3: Simplify template and page stories**

Replace `default-page.stories.json`:
```json
{
  "title": "Templates/Default Page",
  "stories": [
    { "name": "Default", "parameters": { "server": { "id": "templates/default-page" } } }
  ]
}
```

Replace `view-page.stories.json`:
```json
{
  "title": "Templates/View Page",
  "stories": [
    { "name": "With Hero and Button", "parameters": { "server": { "id": "templates/view-page" } } },
    { "name": "Without Button", "parameters": { "server": { "id": "templates/view-page", "params": { "heroButtonLabel": "" } } } }
  ]
}
```

Leave `homepage.stories.json` and `inner-page.stories.json` unchanged (already minimal).

Leave `container.stories.json`, `image.stories.json`, `link.stories.json`, `overlay.stories.json`, `nav-item.stories.json`, `page-card.stories.json`, `partner-logo.stories.json`, `navigation-drawer.stories.json` unchanged — they either show specific variants or are already minimal.

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/components/
git commit -m "refactor: simplify stories.json to rely on variant defaults"
```

---

### Task 7: Verify Storybook renders all components

- [ ] **Step 1: Start Storybook and test PHP server**

```bash
php -S localhost:6007 storybook-server/index.php &
```

Test a few components:

```bash
curl -s "http://localhost:6007/atoms/button" | head -5
curl -s "http://localhost:6007/organisms/footer" | head -5
curl -s "http://localhost:6007/organisms/navigation-drawer" | head -5
```

Expected: each renders with realistic German content from the fixtures (not generic placeholder text).

- [ ] **Step 2: Verify no component errors**

```bash
for comp in atoms/button atoms/heading molecules/accent-text molecules/feature-button organisms/footer organisms/hero-image organisms/navigation-drawer organisms/page-list-grid; do
  status=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:6007/$comp")
  echo "$comp: $status"
done
```

Expected: all return 200.

- [ ] **Step 3: Commit (if any fixes needed)**

```bash
git add -A
git commit -m "fix: resolve any Storybook rendering issues"
```
