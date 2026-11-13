<?php

declare(strict_types=1);

return [
    'defaults' => [
        'items' => json_encode([
            ['label' => 'Start', 'href' => '/'],
            ['label' => 'Ideen', 'href' => '/ideen'],
            ['label' => 'Solarenergie im Quartier'],
        ]),
        'separator' => '/',
        'ariaLabel' => 'Brotkrumen-Navigation',
    ],
];
