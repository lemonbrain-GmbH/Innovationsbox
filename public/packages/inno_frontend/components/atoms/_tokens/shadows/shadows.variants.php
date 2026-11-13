<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../../resources/tokens/tokens.json'), true);

$samples = [];
foreach (['sm', 'md', 'lg', 'xl'] as $name) {
    $samples[] = ['name' => $name, 'shadow' => $tokens['shadow'][$name]];
}

return [
    'defaults' => [
        'samples' => json_encode($samples),
    ],
];
