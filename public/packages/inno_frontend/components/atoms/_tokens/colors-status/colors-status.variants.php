<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../../resources/tokens/tokens.json'), true);

return [
    'defaults' => [
        'scales' => json_encode([
            ['name' => 'Success', 'description' => 'Confirmations, successful actions.', 'steps' => $tokens['color']['status']['success']],
            ['name' => 'Warning', 'description' => 'Warnings, pending states.', 'steps' => $tokens['color']['status']['warning']],
            ['name' => 'Error', 'description' => 'Errors, destructive actions.', 'steps' => $tokens['color']['status']['error']],
            ['name' => 'Info', 'description' => 'Informational messages.', 'steps' => $tokens['color']['status']['info']],
        ]),
    ],
];
