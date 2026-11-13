<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'demo-dropdown',
        'triggerLabel' => 'Aktionen',
        'items' => json_encode([
            ['label' => 'Bearbeiten', 'href' => '#edit'],
            ['label' => 'Duplizieren', 'href' => '#duplicate'],
            ['divider' => 'true'],
            ['label' => 'Löschen', 'href' => '#delete', 'danger' => 'true'],
        ]),
        'position' => 'bottom-start',
        'variant' => 'ghost',
    ],
];
