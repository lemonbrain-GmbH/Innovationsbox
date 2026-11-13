<?php

declare(strict_types=1);

$data = require __DIR__ . '/../../_fixtures/sample-data.php';
return [
    'fits' => ['cover', 'contain'],
    'ratios' => ['auto', '16/9', '3/2', '4/3', '1/1'],
    'defaults' => [
        'src' => $data['categories'][0]['imageSrc'],
        'alt' => $data['categories'][0]['imageAlt'],
        'ratio' => 'auto',
        'fit' => 'cover',
        'rounded' => 'false',
        'width' => '100%',
    ],
];
