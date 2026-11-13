<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'demo-tabs',
        'tabs' => json_encode([
            ['label' => 'Übersicht', 'panelContent' => '<p>Zusammenfassung der Projektidee.</p>'],
            ['label' => 'Details', 'panelContent' => '<p>Ausführliche Beschreibung und Kontext.</p>'],
            ['label' => 'Team', 'panelContent' => '<p>Beteiligte Personen und Rollen.</p>'],
        ]),
        'activeIndex' => '0',
        'variant' => 'line',
        'ariaLabel' => 'Abschnitte',
    ],
];
