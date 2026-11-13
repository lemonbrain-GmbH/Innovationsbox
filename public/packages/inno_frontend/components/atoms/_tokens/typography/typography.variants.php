<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../../resources/tokens/tokens.json'), true);

$samples = [];
foreach ($tokens['textScale'] as $name => $t) {
    $samples[] = [
        'token' => "text-{$name}",
        'sizes' => "{$t['sizes'][0]}px → {$t['sizes'][3]}px",
        'fontSize' => "{$t['sizes'][1]}px",
        'lineHeight' => "{$t['lineHeights'][1]}px",
        'weight' => '400',
    ];
}

return [
    'defaults' => [
        'samples' => json_encode($samples),
        'sampleText' => 'Die Innovationsbox stellt digitale Räume zur Verfügung',
    ],
];
