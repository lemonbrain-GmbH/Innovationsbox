<?php

declare(strict_types=1);

/**
 * Integration test bootstrap.
 *
 * Boots the Concrete CMS application for Pest-based integration tests.
 * Requires a fully installed CMS with a reachable database.
 *
 * Prerequisites:
 *   - `make install` (or manual CMS install) has been run
 *   - `.env` is configured with valid DB_* credentials
 *   - All five packages are installed in the CMS back-end
 *
 * Run via:
 *   composer test:integration
 *
 * If the CMS is not available the suite is skipped gracefully.
 */

$projectRoot = dirname(__DIR__, 2);
$concretePath = $projectRoot . '/public/concrete';

// ── Guard: CMS not installed ─────────────────────────────────────────────────
if (!is_dir($concretePath)) {
    fwrite(
        STDERR,
        "\n[Integration] Skipping — Concrete CMS not found at public/concrete.\n" .
        "              Run `make install` first.\n\n"
    );
    exit(0);
}

// ── Composer autoloader ───────────────────────────────────────────────────────
require_once $projectRoot . '/vendor/autoload.php';

// ── Concrete CMS bootstrap ────────────────────────────────────────────────────
// DIR_BASE must resolve to the web root (public/) so the CMS can locate its
// own paths. We define it explicitly here because there is no HTTP dispatcher.
if (!defined('DIR_BASE')) {
    define('DIR_BASE', str_replace(DIRECTORY_SEPARATOR, '/', $projectRoot . '/public'));
}

if (!defined('C5_EXECUTE')) {
    define('C5_EXECUTE', true);
}

// Load the CMS path constants (sets DIR_BASE_CORE, DIR_APPLICATION, etc.)
require_once $concretePath . '/bootstrap/configure.php';

// Load the CMS class autoloader (required before start.php — registers PSR-4
// namespaces and sets up ClassAutoloader that start.php depends on).
require_once $concretePath . '/bootstrap/autoload.php';

// Boot the full Concrete application (IoC container, DB connection, packages…).
// start.php internally calls Runtime->boot() which registers class aliases,
// service providers, and the full Application lifecycle.
/** @var \Concrete\Core\Application\Application $app */
$app = require $concretePath . '/bootstrap/start.php';

// ── Package initialization ───────────────────────────────────────────────────
// The CMS boot does NOT call on_start() for packages (that happens during HTTP
// dispatch). We trigger it manually so event listeners are registered.
$pkgService = $app->make(\Concrete\Core\Package\PackageService::class);
foreach (['inno_shared', 'inno_frontend', 'inno_moderation', 'inno_project_idea', 'inno_project_space', 'inno_notification'] as $handle) {
    $pkg = $pkgService->getClass($handle);
    if ($pkg && method_exists($pkg, 'on_start')) {
        $pkg->on_start();
    }
}

// Make the booted application available globally so test helpers can reach it.
if (!function_exists('app')) {
    function app(string $abstract = null): mixed
    {
        /** @var \Concrete\Core\Application\Application $cms */
        $cms = \Concrete\Core\Support\Facade\Application::getFacadeApplication();

        return $abstract === null ? $cms : $cms->make($abstract);
    }
}
