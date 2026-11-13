# Storybook Shared Fixtures — Sub-project 2

**Date:** 2026-04-14
**Status:** Approved
**Goal:** Create a shared PHP fixtures file with realistic content from the reference site. All 24 component variants load from this single source — no hardcoded strings in story files.

## Approach

Create `public/packages/inno_frontend/components/_fixtures/sample-data.php` that returns an associative array of reusable content extracted from the live Innovationsbox site. Each `variants.php` requires this file and picks what it needs.

## Fixtures File Structure

```php
<?php
return [
    'site' => [ 'name', 'logoSrc', 'logoAlt', 'logoHref' ],
    'nav'  => [ /* 12-item hierarchical nav array */ ],
    'hero' => [ 'title', 'body', 'imageSrc', 'imageAlt' ],
    'categories' => [ /* 5 categories: Bauen, Wohnen, Landwirtschaft, Tourismus, Mobilität */ ],
    'partners' => [ /* 3 partners: arento, Klimaregion, KlimaGlarus */ ],
    'content' => [
        'quote' => '...',       // accent quote from homepage
        'callToAction' => '...', // h4 under quote
        'introTitle' => '...',  // "Die Innovationsbox"
        'introText' => '...',   // platform description paragraph
        'aboutTitle' => '...',  // "Über uns"
        'aboutText' => '...',   // about paragraph
        'faqQuestions' => [...], // 3 FAQ questions from reference
    ],
    'buttons' => [
        'submit' => 'Ich möchte eine Projektidee einbringen',
        'expertise' => 'Ich stelle gerne meine Expertise zur Verfügung',
        'moreInfo' => 'Mehr Informationen',
    ],
    'footer' => [
        'text' => 'Diese Innovationsbox ist ein Engagement der ...',
    ],
];
```

## Real Content from Reference Site

### Site
- name: `Innovationsbox`
- logoSrc: `https://placehold.co/320x80/B7CD57/353535?text=InnovationsBox` (placehold.co for Storybook)
- logoAlt: `Innovationsbox Logo`

### Hero
- title: `Innovationsbox`
- body: `für alle, die Klimaschutzideen erfolgreich entwickeln und umsetzen wollen`
- imageSrc: `https://placehold.co/1920x600/7B876F/FFF?text=Berglandschaft+Glarus`

### Content blocks
- quote: `Die Innovationsbox stellt digitale Räume zur Verfügung, in welchen sich innovative Entwicklerinnen mit anderen Menschen treffen können, um klimafreundliche Ideen gemeinsam zu entwickeln und zum Erfolg zu bringen.`
- callToAction: `Jede erfolgreiche Innovation hilft dem Klimaschutz – sei Teil dieser Innovationsbox!`
- introTitle: `Die Innovationsbox`
- introText: `Diese digitale Plattform dient zur gemeinsamen Entwicklung klimafreundlicher Ideen und Konzepte welche als Team erfolgreich auf den Markt gebracht werden können. Nebst dem Klimanutzen soll ein kleiner Teil des Gewinns erfolgreicher Projekte der Klimaregion Glarnerland zugutekommen.`
- aboutTitle: `Über uns`
- aboutText: `Initiiert und finanziert wird die Innovationsbox von der Firma arento ag, welche diese der Klimaregion Glarnerland und dem Verein KlimaGlarus.ch zur Verfügung stellt. Der Verein KlimaGlarus.ch lanciert und unterstützt Klimaschutzprojekte im Glarnerland.`

### FAQ questions
1. `Welche Projektideen werden in dieser Box bearbeitet?`
2. `Wer prüft die Projektideen, bevor ein Raum geöffnet wird?`
3. `Wie kann ich eine Innovation zum Erfolg bringen?`

### Categories (5)
Bauen, Wohnen, Landwirtschaft, Tourismus, Mobilität — each with a placehold.co image using the project's color palette.

### Partners (3)
arento ag (https://arento.ch), Klimaregion Glarnerland (https://www.klimaglarus.ch/), KlimaGlarus.ch (https://www.klimaglarus.ch/)

### Buttons (3)
`Ich möchte eine Projektidee einbringen`, `Ich stelle gerne meine Expertise zur Verfügung`, `Mehr Informationen`

## Component-to-Fixture Mapping

| Component | Fixture keys used |
|---|---|
| **button** | `buttons.submit` |
| **container** | `content.introText` |
| **heading** | `content.introTitle` |
| **image** | `categories[0].imageSrc` |
| **link** | `buttons.moreInfo`, partner hrefs |
| **overlay** | `hero.title`, `hero.body` |
| **paragraph** | `content.introText`, `content.aboutText` |
| **skip-link** | static (unchanged) |
| **accent-text** | `content.quote` |
| **feature-button** | `buttons.submit` |
| **nav-item** | `nav[0]` |
| **page-card** | `categories[0]` |
| **partner-logo** | `partners[0]` |
| **question-text** | `content.faqQuestions[0]` |
| **content-block** | `content.quote` (accent), `content.faqQuestions[0]` (question) |
| **footer** | `footer.text`, `partners` |
| **header** | `site`, `nav` |
| **hero-image** | `hero`, `buttons.submit` |
| **navigation-drawer** | `site`, `nav` |
| **page-list-grid** | `categories` |
| **homepage** | all of the above composed |
| **inner-page** | `site`, `nav`, `content`, `partners` |
| **default-page** | `site`, `nav`, `content`, `partners` |
| **view-page** | `site`, `nav`, `hero`, `categories`, `partners` |

## Stories.json Changes

Story files become minimal — they reference the server ID and only override params when a specific story needs a variant (e.g. "Without Button" sets `buttonLabel: ""`). The realistic defaults come from `variants.php`.

## What Doesn't Change

- Component PHP partials (no code changes)
- Component SCSS (no style changes)
- `storybook-server/` (render.php, bootstrap.php)
- `.storybook/main.js`, `preview.js`, `preview-head.html`

## Verification

`npm run storybook` — every component renders with realistic German content. No generic `Button`, `Link`, `Projektidee` placeholder labels. Content matches the reference site's text.
