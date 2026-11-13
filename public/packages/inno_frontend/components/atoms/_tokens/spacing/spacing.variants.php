<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../../resources/tokens/tokens.json'), true);

$scale = [];
foreach ($tokens['space'] as $step => $px) {
    $scale[] = ['step' => $step, 'px' => $px];
}

return [
    'defaults' => [
        'scale' => json_encode($scale),
    ],
];
