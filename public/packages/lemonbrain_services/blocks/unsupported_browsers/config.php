<?php

declare(strict_types=1);

return [
    // Block
    'block'  => [
        'name'        => t('Unsupported Browsers'),
        'description' => t('Created with Block Developer'),
        'default_set' => t('lemonbrain_services'),
    ],
    'interface' => [
        'width'  => 960,
        'height' => 960,
    ],
    'cache' => [
        'block_output' => true,
    ],

    // Fields
    'fields' => [
        'description' =>    [
            'type' => 'Wysiwyg',
            'label'       => t('Hinweis'),
            'description' => t('Dieser Hinweis wird dem Benutzer angezeigt wenn die Version tiefer als erlaubt ist.'),
            'required'    => true,
            'searchable'  => false,
        ],
    ],
];
