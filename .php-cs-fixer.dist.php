<?php

declare(strict_types=1);
$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/public/packages/inno_project_idea/src',
        __DIR__ . '/public/packages/inno_moderation/src',
        __DIR__ . '/public/packages/inno_notification/src',
        __DIR__ . '/public/packages/inno_project_space/src',
        __DIR__ . '/public/packages/inno_shared/src',
        __DIR__ . '/public/packages/inno_frontend',
        __DIR__ . '/storybook-server',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2x0' => true,
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
