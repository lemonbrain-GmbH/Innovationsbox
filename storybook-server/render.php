<?php

declare(strict_types=1);

/**
 * Renders a component partial and returns the HTML string.
 *
 * @param string $componentPath Relative path like "atoms/button"
 * @param array<string, mixed> $props Props to pass to the partial
 * @return string Rendered HTML
 */
function renderComponent(string $componentPath, array $props): string
{
    $componentName = basename($componentPath);
    $fullPath = DIR_COMPONENTS . '/' . $componentPath . '/' . $componentName . '.php';

    if (!file_exists($fullPath)) {
        http_response_code(404);
        return '<div style="color:red;padding:20px;">Component not found: '
            . htmlspecialchars($componentPath) . '</div>';
    }

    // Load variant defaults if available
    $variantsFile = DIR_COMPONENTS . '/' . $componentPath . '/' . $componentName . '.variants.php';
    if (file_exists($variantsFile)) {
        $variants = require $variantsFile;
        $defaults = $variants['defaults'] ?? [];
        $props = array_merge($defaults, $props);
    }

    ob_start();
    require $fullPath;
    return ob_get_clean();
}
