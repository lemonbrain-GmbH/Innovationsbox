<?php

declare(strict_types=1);

return [
    'defaults' => [
        'label' => 'E-Mail-Adresse',
        'id' => 'email',
        'name' => 'email',
        'type' => 'email',
        'value' => '',
        'placeholder' => 'name@beispiel.ch',
        'hint' => 'Wir versenden nie Spam.',
        'error' => '',
        'required' => 'false',
        'disabled' => 'false',
        'options' => json_encode([
            ['value' => 'ch', 'label' => 'Schweiz'],
            ['value' => 'de', 'label' => 'Deutschland'],
            ['value' => 'at', 'label' => 'Österreich'],
        ]),
        'rows' => '4',
    ],
];
