<?php

declare(strict_types=1);

/**
 * Shared sample data for Storybook component variants.
 * Content extracted from the reference site (innovationsbox.lemonbrain.ch).
 * All components load their defaults from here — single source of truth.
 *
 * Images:
 *   - Photos use picsum.photos with seeded URLs (deterministic, real photos)
 *   - Logos use dummyimage.com (text on color, looks like brand logos)
 */
return [
    'site' => [
        'name' => 'Innovationsbox',
        'logoSrc' => 'https://dummyimage.com/320x80/B7CD57/353535.png&text=innovationsBox',
        'logoAlt' => 'Innovationsbox',
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
        'imageSrc' => 'https://picsum.photos/seed/glarus-mountains/1920/600',
        'imageAlt' => 'Berglandschaft Glarus',
    ],

    'categories' => [
        ['title' => 'Bauen', 'href' => '/uebersicht-projektideen/bauen', 'imageSrc' => 'https://picsum.photos/seed/bauen/600/400', 'imageAlt' => 'Bauen'],
        ['title' => 'Wohnen', 'href' => '/uebersicht-projektideen/wohnen', 'imageSrc' => 'https://picsum.photos/seed/wohnen/600/400', 'imageAlt' => 'Wohnen'],
        ['title' => 'Landwirtschaft', 'href' => '/uebersicht-projektideen/landwirtschaft', 'imageSrc' => 'https://picsum.photos/seed/landwirtschaft/600/400', 'imageAlt' => 'Landwirtschaft'],
        ['title' => 'Tourismus', 'href' => '/uebersicht-projektideen/tourismus', 'imageSrc' => 'https://picsum.photos/seed/tourismus/600/400', 'imageAlt' => 'Tourismus'],
        ['title' => 'Mobilität', 'href' => '/uebersicht-projektideen/mobilitaet', 'imageSrc' => 'https://picsum.photos/seed/mobilitaet/600/400', 'imageAlt' => 'Mobilität'],
    ],

    'partners' => [
        ['name' => 'arento ag', 'href' => 'https://arento.ch', 'imageSrc' => 'https://dummyimage.com/120x60/ffffff/89705b.png&text=arento+ag'],
        ['name' => 'Klimaregion Glarnerland', 'href' => 'https://www.klimaglarus.ch/', 'imageSrc' => 'https://dummyimage.com/120x60/ffffff/7B876F.png&text=Klimaregion'],
        ['name' => 'KlimaGlarus.ch', 'href' => 'https://www.klimaglarus.ch/', 'imageSrc' => 'https://dummyimage.com/120x60/ffffff/B7CD57.png&text=KlimaGlarus'],
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
