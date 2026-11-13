<?php

declare(strict_types=1);

return [
    'defaults' => [
        'id' => 'country',
        'name' => 'country',
        'value' => '',
        'placeholder' => 'Land auswählen…',
        'size' => 'md',
        'options' => json_encode([
            ['value' => 'ch', 'label' => 'Schweiz'],
            ['value' => 'de', 'label' => 'Deutschland'],
            ['value' => 'at', 'label' => 'Österreich'],
            ['value' => 'li', 'label' => 'Liechtenstein'],
        ]),
        'disabled' => 'false',
        'required' => 'false',
        'invalid' => 'false',
        'describedBy' => '',
        'ariaLabel' => 'Land auswählen',
    ],
];
