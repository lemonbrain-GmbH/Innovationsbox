<?php

declare(strict_types=1);

return [
    // Block
    'block' => [
        'name' => '/* @name */',
        'description' => t('Created with Block Developer'),
    ],

    // Fields
    'fields' => [
        'title' => [
            'type' => 'Text',
            'label' => t('A Text Input Field'),
            'description' => t('This is the description of the field'),
            'required' => true,
            'searchable' => true,
        ],
    ],
];
