<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../../resources/tokens/tokens.json'), true);

return [
    'defaults' => [
        'scales' => json_encode([
            [
                'name' => 'Neutral',
                'description' => 'Warm gray — page backgrounds, text, borders.',
                'steps' => $tokens['color']['neutral'],
            ],
        ]),
    ],
];
