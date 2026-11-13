<?php

declare(strict_types=1);

/**
 * Storybook bootstrap — stubs for Concrete CMS functions so that
 * component partials can render outside the CMS context.
 */

// Concrete CMS translation function
if (!function_exists('t')) {
    function t(string $text, ...$args): string
    {
        if (empty($args)) {
            return $text;
        }
        return vsprintf($text, $args);
    }
}

// Concrete CMS HTML escaping helper
if (!function_exists('h')) {
    function h(?string $text): string
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Component base path
define('DIR_COMPONENTS', dirname(__DIR__) . '/public/packages/inno_frontend/components');
