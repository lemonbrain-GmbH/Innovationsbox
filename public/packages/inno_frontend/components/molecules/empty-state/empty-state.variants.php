<?php

declare(strict_types=1);

$defaultIllustration = '<svg viewBox="0 0 200 200" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><rect x="20" y="40" width="160" height="120" rx="8" fill="none" stroke="currentColor" stroke-width="4"/><path d="M60 80 L140 80 M60 100 L120 100 M60 120 L130 120" stroke="currentColor" stroke-width="4" stroke-linecap="round" fill="none"/></svg>';

return [
    'defaults' => [
        'illustration' => $defaultIllustration,
        'title' => 'Noch keine Projektideen',
        'description' => 'Sei die erste Person, die eine klimarelevante Idee für das Glarnerland einreicht.',
        'ctaLabel' => 'Idee einreichen',
        'ctaHref' => '/projektidee-einbringen',
        'size' => 'md',
    ],
];
