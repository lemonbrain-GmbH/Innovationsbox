<?php

declare(strict_types=1);

$tokens = json_decode(file_get_contents(__DIR__ . '/../../../../../../../resources/tokens/tokens.json'), true);

return [
    'defaults' => [
        'samples' => json_encode([
            ['name' => 'duration-fast + ease-standard', 'value' => $tokens['motion']['duration']['fast'] . ' / standard', 'duration' => $tokens['motion']['duration']['fast'], 'easing' => $tokens['motion']['easing']['standard']],
            ['name' => 'duration-normal + ease-standard', 'value' => $tokens['motion']['duration']['normal'] . ' / standard', 'duration' => $tokens['motion']['duration']['normal'], 'easing' => $tokens['motion']['easing']['standard']],
            ['name' => 'duration-slow + ease-standard', 'value' => $tokens['motion']['duration']['slow'] . ' / standard', 'duration' => $tokens['motion']['duration']['slow'], 'easing' => $tokens['motion']['easing']['standard']],
            ['name' => 'duration-normal + ease-emphasized', 'value' => $tokens['motion']['duration']['normal'] . ' / emphasized', 'duration' => $tokens['motion']['duration']['normal'], 'easing' => $tokens['motion']['easing']['emphasized']],
        ]),
    ],
];
