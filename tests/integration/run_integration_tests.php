<?php

declare(strict_types=1);

/**
 * Integration test runner for cross-cutting concern adapters.
 *
 * Run via: php public/concrete/bin/concrete5 c5:exec tests/integration/run_integration_tests.php
 *
 * This script runs inside the full CMS context (booted by c5:exec),
 * so we have access to real services: database, config, cache, logging, users.
 */

class IntegrationTestRunner
{
    public static int $passed = 0;
    public static int $failed = 0;
    public static array $errors = [];

    public static function run(string $name, callable $fn): void
    {
        try {
            $fn();
            self::$passed++;
            echo "  PASS  {$name}\n";
        } catch (\Throwable $e) {
            self::$failed++;
            self::$errors[] = $name . ': ' . $e->getMessage();
            echo "  FAIL  {$name}: {$e->getMessage()}\n";
        }
    }
}

function assert_true(bool $condition, string $msg = 'Expected true'): void
{
    if (!$condition) {
        throw new \RuntimeException($msg);
    }
}

function assert_eq(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        $msg = $msg ?: 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true);
        throw new \RuntimeException($msg);
    }
}

function assert_not_null(mixed $value, string $msg = 'Expected not null'): void
{
    if ($value === null) {
        throw new \RuntimeException($msg);
    }
}

function assert_instance(string $class, mixed $value, string $msg = ''): void
{
    if (!($value instanceof $class)) {
        $msg = $msg ?: "Expected instance of {$class}, got " . get_class($value);
        throw new \RuntimeException($msg);
    }
}

// -------------------------------------------------------------------
// Get CMS app container
// -------------------------------------------------------------------
$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();

echo "\n=== Cross-Cutting Concerns Integration Tests ===\n\n";

// -------------------------------------------------------------------
// 1. ConcreteCmsConfig
// -------------------------------------------------------------------
echo "--- ConfigInterface ---\n";

use Concrete\Package\InnoShared\Infrastructure\Adapter\Config\ConcreteCmsConfig;
use Concrete\Package\InnoShared\Infrastructure\Exception\InfrastructureException;

$configRepo = $app->make(\Concrete\Core\Config\Repository\Repository::class);
$config = new ConcreteCmsConfig($configRepo);

IntegrationTestRunner::run('Config::get() reads concrete.version via prefix', function () use ($configRepo, $config) {
    // Set a test value directly in CMS config
    $configRepo->set('innovationsbox.test.integration_key', 'hello_integration');
    assert_eq('hello_integration', $config->get('test.integration_key'));
});

IntegrationTestRunner::run('Config::get() returns default for missing key', function () use ($config) {
    assert_eq('fallback', $config->get('nonexistent.key.xyz', 'fallback'));
});

IntegrationTestRunner::run('Config::has() returns true for existing key', function () use ($configRepo, $config) {
    $configRepo->set('innovationsbox.test.has_key', true);
    assert_true($config->has('test.has_key'));
});

IntegrationTestRunner::run('Config::has() returns false for missing key', function () use ($config) {
    assert_true(!$config->has('nonexistent.key.abc'));
});

IntegrationTestRunner::run('Config::getRequired() throws for missing key', function () use ($config) {
    $threw = false;
    try {
        $config->getRequired('nonexistent.required.key');
    } catch (InfrastructureException $e) {
        $threw = true;
    }
    assert_true($threw, 'Expected InfrastructureException');
});

IntegrationTestRunner::run('Config::set() stores a value', function () use ($config) {
    $config->set('test.set_value', 42);
    assert_eq(42, $config->get('test.set_value'));
});

// -------------------------------------------------------------------
// 2. ConcreteCmsLogger
// -------------------------------------------------------------------
echo "\n--- LoggerInterface ---\n";

use Concrete\Package\InnoShared\Infrastructure\Adapter\Logger\ConcreteCmsLogger;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Logger\SensitiveDataProcessor;

$loggerFactory = $app->make(\Concrete\Core\Logging\LoggerFactory::class);
$cmsLogger = $loggerFactory->createLogger('integration_test');
$cmsLogger->pushProcessor(new SensitiveDataProcessor());
$logger = new ConcreteCmsLogger($cmsLogger);

IntegrationTestRunner::run('Logger::info() does not throw', function () use ($logger) {
    $logger->info('Integration test log entry', ['test' => true]);
    assert_true(true); // no exception = pass
});

IntegrationTestRunner::run('Logger::withContext() returns new instance', function () use ($logger) {
    $contextLogger = $logger->withContext(['requestId' => 'test-123']);
    assert_true($contextLogger !== $logger, 'Should return a new instance');
    $contextLogger->info('Message with context');
    assert_true(true);
});

IntegrationTestRunner::run('Logger::error() does not throw', function () use ($logger) {
    $logger->error('Integration test error', ['code' => 500]);
    assert_true(true);
});

// -------------------------------------------------------------------
// 3. ConcreteCmsTransaction
// -------------------------------------------------------------------
echo "\n--- TransactionInterface ---\n";

use Concrete\Package\InnoShared\Infrastructure\Adapter\Transaction\ConcreteCmsTransaction;

$connection = $app->make(\Concrete\Core\Database\Connection\Connection::class);
$tx = new ConcreteCmsTransaction($connection);

IntegrationTestRunner::run('Transaction::transactional() commits on success', function () use ($tx, $connection) {
    // Create a temp table for testing
    $connection->executeStatement('CREATE TEMPORARY TABLE IF NOT EXISTS _ccc_test (id INT, val VARCHAR(50))');
    $connection->executeStatement('DELETE FROM _ccc_test');

    $result = $tx->transactional(function () use ($connection) {
        $connection->executeStatement("INSERT INTO _ccc_test (id, val) VALUES (1, 'committed')");
        return 'ok';
    });

    assert_eq('ok', $result);
    $val = $connection->fetchOne('SELECT val FROM _ccc_test WHERE id = 1');
    assert_eq('committed', $val);
});

IntegrationTestRunner::run('Transaction::transactional() rolls back on exception', function () use ($tx, $connection) {
    $connection->executeStatement('DELETE FROM _ccc_test');

    $threw = false;
    try {
        $tx->transactional(function () use ($connection) {
            $connection->executeStatement("INSERT INTO _ccc_test (id, val) VALUES (2, 'should_rollback')");
            throw new \RuntimeException('test rollback');
        });
    } catch (\RuntimeException $e) {
        $threw = true;
    }

    assert_true($threw, 'Exception should propagate');
    $val = $connection->fetchOne('SELECT val FROM _ccc_test WHERE id = 2');
    assert_true($val === false || $val === null, 'Row should not exist after rollback');
});

IntegrationTestRunner::run('Transaction::getDepth() returns 0 outside transaction', function () use ($tx) {
    assert_eq(0, $tx->getDepth());
});

IntegrationTestRunner::run('Transaction::begin/commit work directly', function () use ($tx, $connection) {
    $connection->executeStatement('DELETE FROM _ccc_test');
    $tx->begin();
    $connection->executeStatement("INSERT INTO _ccc_test (id, val) VALUES (3, 'direct')");
    $tx->commit();
    $val = $connection->fetchOne('SELECT val FROM _ccc_test WHERE id = 3');
    assert_eq('direct', $val);
});

// Cleanup
$connection->executeStatement('DROP TEMPORARY TABLE IF EXISTS _ccc_test');

// -------------------------------------------------------------------
// 4. ConcreteCmsCache
// -------------------------------------------------------------------
echo "\n--- CacheInterface ---\n";

use Concrete\Package\InnoShared\Infrastructure\Adapter\Cache\ConcreteCmsCache;

$cmsCache = $app->make('cache');
$cache = new ConcreteCmsCache($cmsCache->pool);

IntegrationTestRunner::run('Cache::set() and get() round-trip', function () use ($cache) {
    $cache->set('integration.test.key', 'hello_cache', 60);
    assert_eq('hello_cache', $cache->get('integration.test.key'));
});

IntegrationTestRunner::run('Cache::get() returns default on miss', function () use ($cache) {
    assert_eq('default_val', $cache->get('integration.nonexistent.key', 'default_val'));
});

IntegrationTestRunner::run('Cache::has() returns true for existing key', function () use ($cache) {
    $cache->set('integration.test.has', true, 60);
    assert_true($cache->has('integration.test.has'));
});

IntegrationTestRunner::run('Cache::delete() removes key', function () use ($cache) {
    $cache->set('integration.test.delete', 'bye', 60);
    $cache->delete('integration.test.delete');
    assert_true(!$cache->has('integration.test.delete'));
});

IntegrationTestRunner::run('Cache::getOrSet() calls callback on miss', function () use ($cache) {
    $cache->delete('integration.test.getorset');
    $called = false;
    $result = $cache->getOrSet('integration.test.getorset', 60, function () use (&$called) {
        $called = true;
        return 'computed_value';
    });
    assert_true($called, 'Callback should be called on miss');
    assert_eq('computed_value', $result);
});

IntegrationTestRunner::run('Cache::getOrSet() returns cached on hit', function () use ($cache) {
    // Key was set by previous test
    $called = false;
    $result = $cache->getOrSet('integration.test.getorset', 60, function () use (&$called) {
        $called = true;
        return 'should_not_be_used';
    });
    assert_true(!$called, 'Callback should NOT be called on hit');
    assert_eq('computed_value', $result);
});

IntegrationTestRunner::run('Cache::setTagged() and flushTag() work', function () use ($cache) {
    $cache->setTagged('integration.test.tagged1', 'val1', 60, ['integration_tag']);
    $cache->setTagged('integration.test.tagged2', 'val2', 60, ['integration_tag']);
    assert_eq('val1', $cache->get('integration.test.tagged1'));
    assert_eq('val2', $cache->get('integration.test.tagged2'));

    $cache->flushTag('integration_tag');
    assert_true(!$cache->has('integration.test.tagged1'), 'Tagged key 1 should be flushed');
    assert_true(!$cache->has('integration.test.tagged2'), 'Tagged key 2 should be flushed');
});

// Cleanup
$cache->delete('integration.test.key');
$cache->delete('integration.test.has');
$cache->delete('integration.test.getorset');

// -------------------------------------------------------------------
// 5. ConcreteCmsHealthCheck
// -------------------------------------------------------------------
echo "\n--- HealthCheckInterface ---\n";

use Concrete\Package\InnoShared\Infrastructure\Adapter\Monitoring\ConcreteCmsHealthCheck;
use Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring\HealthStatus;

$healthCheck = new ConcreteCmsHealthCheck($connection, $configRepo);

IntegrationTestRunner::run('HealthCheck::check() returns healthy for database and CMS', function () use ($healthCheck) {
    $results = $healthCheck->check();
    assert_eq(HealthStatus::Healthy, $results['database'], 'Database should be healthy');
    assert_eq(HealthStatus::Healthy, $results['cms'], 'CMS should be healthy');
});

// -------------------------------------------------------------------
// 6. LogBasedMetrics
// -------------------------------------------------------------------
echo "\n--- MetricsInterface ---\n";

use Concrete\Package\InnoShared\Infrastructure\Adapter\Monitoring\LogBasedMetrics;

$metrics = new LogBasedMetrics($logger);

IntegrationTestRunner::run('Metrics::increment() does not throw', function () use ($metrics) {
    $metrics->increment('integration.test.counter', 1, ['env' => 'test']);
    assert_true(true);
});

IntegrationTestRunner::run('Metrics::measure() times a callable and returns result', function () use ($metrics) {
    $result = $metrics->measure('integration.test.timing', function () {
        usleep(1000); // 1ms
        return 'measured';
    });
    assert_eq('measured', $result);
});

IntegrationTestRunner::run('Metrics::event() does not throw', function () use ($metrics) {
    $metrics->event('integration.test.event', ['action' => 'test']);
    assert_true(true);
});

// -------------------------------------------------------------------
// 7. ConcreteCmsExceptionHandler
// -------------------------------------------------------------------
echo "\n--- ExceptionHandlerInterface ---\n";

use Concrete\Package\InnoShared\Infrastructure\Adapter\ErrorHandling\ConcreteCmsExceptionHandler;
use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;

$exHandler = new ConcreteCmsExceptionHandler($logger, $config);

IntegrationTestRunner::run('ExceptionHandler maps NotFoundException to 404 JSON', function () use ($exHandler) {
    $e = new NotFoundException('Test not found', ['id' => '123']);
    $response = $exHandler->toJsonResponse($e);
    assert_eq(404, $response->getStatusCode());
    $body = json_decode($response->getContent(), true);
    assert_eq('The requested resource was not found.', $body['error']['message']);
    assert_eq(404, $body['error']['code']);
});

IntegrationTestRunner::run('ExceptionHandler maps RuntimeException to 500 JSON', function () use ($exHandler) {
    $e = new \RuntimeException('Unexpected failure');
    $response = $exHandler->toJsonResponse($e);
    assert_eq(500, $response->getStatusCode());
    $body = json_decode($response->getContent(), true);
    assert_eq('An internal error occurred. Please try again later.', $body['error']['message']);
});

// -------------------------------------------------------------------
// 8. ConcreteCmsAuth (limited — no logged-in user in CLI)
// -------------------------------------------------------------------
echo "\n--- AuthInterface ---\n";

use Concrete\Package\InnoShared\Infrastructure\Adapter\Auth\ConcreteCmsAuth;

$user = $app->make(\Concrete\Core\User\User::class);
$auth = new ConcreteCmsAuth($user);

IntegrationTestRunner::run('Auth::isAuthenticated() returns false for CLI (no session)', function () use ($auth) {
    // In CLI context, there's no logged-in user
    assert_true(!$auth->isAuthenticated(), 'CLI user should not be authenticated');
});

IntegrationTestRunner::run('Auth::getUserId() returns null for unauthenticated', function () use ($auth) {
    assert_true($auth->getUserId() === null);
});

IntegrationTestRunner::run('Auth::getCurrentUser() returns null for unauthenticated', function () use ($auth) {
    assert_true($auth->getCurrentUser() === null);
});

IntegrationTestRunner::run('Auth::hasPermission(idea.view) returns true for unauthenticated', function () use ($auth) {
    // idea.view is the only permission available to unauthenticated users
    assert_true($auth->hasPermission('idea.view'), 'idea.view should be public');
});

IntegrationTestRunner::run('Auth::hasPermission(idea.submit) returns false for unauthenticated', function () use ($auth) {
    assert_true(!$auth->hasPermission('idea.submit'), 'idea.submit requires authentication');
});

// -------------------------------------------------------------------
// Summary
// -------------------------------------------------------------------
echo "\n=== Results ===\n";
echo 'Passed: ' . IntegrationTestRunner::$passed . "\n";
echo 'Failed: ' . IntegrationTestRunner::$failed . "\n";

if (IntegrationTestRunner::$failed > 0) {
    echo "\nFailures:\n";
    foreach (IntegrationTestRunner::$errors as $err) {
        echo "  - {$err}\n";
    }
    exit(1);
}

echo "\nAll integration tests passed.\n";
exit(0);
