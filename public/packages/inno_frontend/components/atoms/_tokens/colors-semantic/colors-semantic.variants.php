<?php

declare(strict_types=1);

return [
    'defaults' => [
        'groups' => json_encode([
            [
                'name' => 'Text',
                'description' => 'Text color tokens — use these for text elements.',
                'tokens' => [
                    ['var' => 'color-text-primary', 'desc' => 'Primary text (headings, body)'],
                    ['var' => 'color-text-secondary', 'desc' => 'Muted body text'],
                    ['var' => 'color-text-muted', 'desc' => 'Captions, hints'],
                    ['var' => 'color-text-disabled', 'desc' => 'Disabled text'],
                    ['var' => 'color-text-inverse', 'desc' => 'Text on dark backgrounds'],
                    ['var' => 'color-text-link', 'desc' => 'Links'],
                    ['var' => 'color-text-link-hover', 'desc' => 'Link hover state'],
                    ['var' => 'color-text-error', 'desc' => 'Error text'],
                    ['var' => 'color-text-success', 'desc' => 'Success text'],
                    ['var' => 'color-text-warning', 'desc' => 'Warning text'],
                ],
            ],
            [
                'name' => 'Background',
                'description' => 'Background color tokens.',
                'tokens' => [
                    ['var' => 'color-bg-page', 'desc' => 'Page background'],
                    ['var' => 'color-bg-surface', 'desc' => 'Cards, panels'],
                    ['var' => 'color-bg-subtle', 'desc' => 'Section backgrounds'],
                    ['var' => 'color-bg-emphasis', 'desc' => 'Contrast blocks'],
                    ['var' => 'color-bg-inverse', 'desc' => 'Dark panels'],
                    ['var' => 'color-bg-overlay', 'desc' => 'Modal backdrop'],
                ],
            ],
            [
                'name' => 'Border',
                'description' => 'Border color tokens.',
                'tokens' => [
                    ['var' => 'color-border-subtle', 'desc' => 'Dividers'],
                    ['var' => 'color-border-default', 'desc' => 'Input borders'],
                    ['var' => 'color-border-strong', 'desc' => 'Emphasized borders'],
                    ['var' => 'color-border-focus', 'desc' => 'Focus ring'],
                ],
            ],
            [
                'name' => 'Action',
                'description' => 'Interactive action tokens (buttons, CTAs).',
                'tokens' => [
                    ['var' => 'color-action-primary', 'desc' => 'Primary button background'],
                    ['var' => 'color-action-primary-hover', 'desc' => 'Primary button hover'],
                    ['var' => 'color-action-primary-text', 'desc' => 'Primary button text'],
                    ['var' => 'color-action-secondary', 'desc' => 'Secondary button background'],
                    ['var' => 'color-action-secondary-hover', 'desc' => 'Secondary button hover'],
                    ['var' => 'color-action-secondary-text', 'desc' => 'Secondary button text'],
                    ['var' => 'color-action-danger', 'desc' => 'Danger button (AA verified)'],
                    ['var' => 'color-action-danger-hover', 'desc' => 'Danger hover'],
                    ['var' => 'color-action-danger-text', 'desc' => 'Danger text'],
                ],
            ],
        ]),
    ],
];
