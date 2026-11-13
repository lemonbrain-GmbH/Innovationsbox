<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';

return [
    'variants' => ['primary', 'secondary', 'ghost', 'danger', 'accent', 'muted', 'dark'],
    'sizes' => ['sm', 'md', 'lg'],
    'defaults' => [
        'variant' => 'primary',
        'size' => 'md',
        'label' => $data['buttons']['submit'],
        'href' => '#',
        'type' => 'button',
        'disabled' => 'false',
        'loading' => 'false',
        'iconLeft' => '',
        'iconRight' => '',
        'fullWidth' => 'false',
    ],
];
