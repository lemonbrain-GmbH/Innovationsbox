<?php

declare(strict_types=1);

// Allow Storybook (port 6006) to fetch from this server (port 6007)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = preg_match('#^https?://(localhost|innovationsbox\.local|127\.0\.0\.1)(:\d+)?$#', $origin);
header('Access-Control-Allow-Origin: ' . ($allowed ? $origin : 'http://localhost:6006'));
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

// Parse the component path:
// - @storybook/server sends it as the URL path: /atoms/button?params...
// - Manual testing can use ?id= or ?component= query params
$urlPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$component = (!empty($urlPath) ? $urlPath : null) ?? $_GET['id'] ?? $_GET['component'] ?? null;

if ($component === null) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing ?id= or ?component= parameter']);
    exit;
}

// Sanitize: only allow alphanumeric, hyphens, underscores, slashes
if (!preg_match('#^[a-zA-Z0-9/_-]+$#', $component)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid component path']);
    exit;
}

// All remaining query params become $props
$props = $_GET;
unset($props['id'], $props['component']);

// Remap elementId → id for component props (avoids duplicate ?id= in query string)
if (isset($props['elementId'])) {
    $props['id'] = $props['elementId'];
    unset($props['elementId']);
}

// Render and output
header('Content-Type: text/html; charset=UTF-8');
echo renderComponent($component, $props);
