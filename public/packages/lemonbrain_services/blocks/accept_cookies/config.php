<?php

declare(strict_types=1);

return [
    'block'  => [
        'name'        => t('Accept Cookies'),
        'description' => t('Accept Cookies Block'),
        'default_set' => t('lemonbrain_services'),
    ],
    'interface' => [
        'width'  => 960,
        'height' => 960,
    ],
    'cache' => [
        'block_output' => true,
        'block_output_lifetime' => 79200,
    ],

    // Fields
    'fields' => [
        'cookie_hint_title'    => [
            'type'  => 'Text',
            'label' => t('Title'),
            'required'  => true,
            'searchable'    => false,
        ],
        'cookie_hint'    => [
            'type'  => 'Wysiwyg',
            'label' => t('Cookie Hinweis'),
            'required'  => true,
            'searchable'    => false,
        ],
        'cookies_setting_description'    => [
            'type'  => 'Wysiwyg',
            'label' => t('Cookie Einstellung Beschreibung'),
            'required'  => true,
            'searchable'    => false,
        ],
        'cookies'   => [
            'type'  => 'Repeatable',
            'label' => t('Cookies'),
            'searchable'    => false,
            'config'    => [
                'minimum_entries'   => 1,
                'add_label' => t('Cookie hinzufügen'),
                'fields'    => [
                    'cookie_handler'  => [
                        'type'  => 'Text',
                        'label' => t('Handler'),
                        'description' => t('Dieser Handler wird von der Website verwendet um die Cookies effektiv zu aktivieren/deaktivieren und sollte nicht geändert werden.'),
                        'required'  => true,
                        'searchable'    => false,
                    ],
                    'cookie_title'  => [
                        'type'  => 'Text',
                        'label' => t('Title'),
                        'required'  => true,
                        'searchable'    => false,
                    ],
                    'cookie_description'    => [
                        'type'  => 'Wysiwyg',
                        'label' => t('Beschreibung'),
                        'required'  => true,
                        'searchable'    => false,
                    ],
                ],
            ],
        ],
    ],
];
