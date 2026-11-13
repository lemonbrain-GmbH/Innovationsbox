<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../../resources/tokens/tokens.json'), true);

return [
    'defaults' => [
        'scales' => json_encode([
            [
                'name' => 'Primary',
                'description' => 'Main brand color — actions, links, emphasis.',
                'steps' => $tokens['color']['brand']['primary'],
            ],
            [
                'name' => 'Secondary',
                'description' => 'Secondary brand color — supporting elements.',
                'steps' => $tokens['color']['brand']['secondary'],
            ],
            [
                'name' => 'Accent',
                'description' => 'Accent color — hover states, highlights.',
                'steps' => $tokens['color']['brand']['accent'],
            ],
        ]),
    ],
];
