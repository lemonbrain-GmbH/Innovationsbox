<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Logging\LoggerFactory;
use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Core\Support\Facade\Events;
use Concrete\Core\User\User;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Auth\ConcreteCmsAuth;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Auth\ConcreteCmsSession;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Cache\ConcreteCmsCache;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Config\ConcreteCmsConfig;
use Concrete\Package\InnoShared\Infrastructure\Adapter\ErrorHandling\ConcreteCmsExceptionHandler;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Logger\ConcreteCmsLogger;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Logger\SensitiveDataProcessor;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Monitoring\ConcreteCmsHealthCheck;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Monitoring\LogBasedMetrics;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Transaction\ConcreteCmsTransaction;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\SessionInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Cache\CacheInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring\HealthCheckInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring\MetricsInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class Controller extends Package
{
    protected $pkgHandle = 'inno_shared';
    protected $appVersionRequired = '9.5.0';
    protected $pkgVersion = '1.2.0';

    public function getPackageDescription(): string
    {
        return t('Shared kernel: cross-package contracts, adapters, and event classes for the Innovationsbox platform.');
    }

    public function getPackageName(): string
    {
        return t('Innovationsbox – Shared Kernel');
    }

    public function on_start(): void
    {
        // --- Config ---
        $this->app->singleton(ConfigInterface::class, function ($app) {
            return new ConcreteCmsConfig($app->make(Repository::class));
        });

        // --- Logging ---
        $this->app->singleton(LoggerInterface::class, function ($app) {
            $factory = $app->make(LoggerFactory::class);
            $cmsLogger = $factory->createLogger('innovationsbox');
            $cmsLogger->pushProcessor(new SensitiveDataProcessor());

            return new ConcreteCmsLogger($cmsLogger);
        });

        // --- Error Handling ---
        $this->app->singleton(ExceptionHandlerInterface::class, ConcreteCmsExceptionHandler::class);

        // --- Auth ---
        $this->app->bind(AuthInterface::class, function ($app) {
            return new ConcreteCmsAuth($app->make(User::class));
        });
        $this->app->bind(SessionInterface::class, function ($app) {
            return new ConcreteCmsSession($app->make(Session::class));
        });

        // --- Cache ---
        $this->app->singleton(CacheInterface::class, function ($app) {
            $cmsCache = $app->make('cache');

            return new ConcreteCmsCache($cmsCache->pool);
        });

        // --- Event Dispatcher ---
        // CMS registers Symfony\Component\EventDispatcherInterface. Our domain handlers
        // type-hint the Contracts interface (Symfony\Contracts\EventDispatcher\EventDispatcherInterface).
        // Bridge the two so DI can resolve either.
        $this->app->bind(
            \Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class,
            \Symfony\Component\EventDispatcher\EventDispatcherInterface::class,
        );

        // --- Transactions ---
        $this->app->bind(TransactionInterface::class, function ($app) {
            return new ConcreteCmsTransaction($app->make(Connection::class));
        });

        // --- User Resolver ---
        $this->app->bind(
            \Concrete\Package\InnoShared\Port\Out\UserResolverPort::class,
            \Concrete\Package\InnoShared\Infrastructure\Adapter\User\ConcreteCmsUserResolver::class,
        );
        $this->app->bind(
            \Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort::class,
            \Concrete\Package\InnoShared\Infrastructure\Adapter\Space\ConcreteProjectSpaceReader::class,
        );
        $this->app->bind(
            \Concrete\Package\InnoShared\Port\Out\ProjectIdeaReaderPort::class,
            \Concrete\Package\InnoShared\Infrastructure\Adapter\Idea\ConcreteProjectIdeaReader::class,
        );
        $this->app->bind(
            \Concrete\Package\InnoShared\Port\Out\SiteUrlPort::class,
            \Concrete\Package\InnoShared\Infrastructure\Adapter\Site\ConcreteCmsSiteUrl::class,
        );

        // --- Monitoring ---
        $this->app->singleton(MetricsInterface::class, LogBasedMetrics::class);
        $this->app->singleton(HealthCheckInterface::class, function ($app) {
            return new ConcreteCmsHealthCheck(
                $app->make(Connection::class),
                $app->make(Repository::class),
            );
        });

        // --- Health endpoint ---
        $this->registerHealthRoute();

        // --- Global API exception handler ---
        $this->registerExceptionHandler();

        // --- Profile completion redirect ---
        $this->registerProfileCompletionCheck();

        // --- Security response headers ---
        $this->registerSecurityHeaders();

        // --- API documentation ---
        $this->registerDocsRoutes();
    }

    private function registerSecurityHeaders(): void
    {
        Events::addListener('on_before_render', function () {
            $middleware = new \Concrete\Package\InnoShared\Infrastructure\Http\SecurityHeadersMiddleware();
            $request = \Concrete\Core\Http\Request::getInstance();
            $isSecure = $request->isSecure();

            // Build a temporary response just to collect headers via the middleware,
            // then emit them via header() before the CMS renders its output.
            $collector = new \Symfony\Component\HttpFoundation\Response();
            $middleware->applyHeaders($collector, $isSecure);

            foreach ($collector->headers->all() as $name => $values) {
                foreach ($values as $value) {
                    header($name . ': ' . $value, true);
                }
            }
        });
    }

    private function registerProfileCompletionCheck(): void
    {
        Events::addListener('on_before_render', function () {
            $user = $this->app->make(\Concrete\Core\User\User::class);
            if (!$user->isRegistered()) {
                return;
            }

            $request = \Concrete\Core\Http\Request::getInstance();
            $path = $request->getPathInfo();

            $allowedPrefixes = ['/account/', '/login', '/ccm/', '/api/'];
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return;
                }
            }
            if ($path === '/logout') {
                return;
            }

            $ui = \Concrete\Core\User\UserInfo::getByID($user->getUserID());
            if (!$ui) {
                return;
            }

            $keys = \Concrete\Core\Attribute\Key\UserKey::getEditableInProfileList();
            foreach ($keys as $key) {
                if ($key->isAttributeKeyRequiredOnProfile()) {
                    $value = $ui->getAttributeValueObject($key);
                    if (!$value || (string) $value->getDisplayValue() === '') {
                        $session = $this->app->make(\Symfony\Component\HttpFoundation\Session\Session::class);
                        $session->set('profile_completion_redirect', $path);

                        $response = \Redirect::to('/account/edit_profile?complete=1');
                        $response->send();
                        exit;
                    }
                }
            }
        });
    }

    private function registerDocsRoutes(): void
    {
        $config = $this->app->make(ConfigInterface::class);
        if (!$config->get('api.docs_enabled', true)) {
            return;
        }

        $router = $this->app->make(Router::class);

        $router->register('/api/docs', function () {
            ob_start();
            include __DIR__ . '/src/Infrastructure/OpenApi/View/swagger-ui.php';
            $html = ob_get_clean();

            return new \Symfony\Component\HttpFoundation\Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }, 'api.docs', ['GET']);

        $router->register('/api/docs/openapi.json', function () {
            $specPath = DIR_BASE . '/../docs/api/openapi.json';
            if (!file_exists($specPath)) {
                return new JsonResponse(['error' => 'Spec not generated. Run: composer openapi:generate'], 404);
            }

            return new JsonResponse(
                json_decode(file_get_contents($specPath), true),
                200,
            );
        }, 'api.docs.spec', ['GET']);
    }

    private function registerExceptionHandler(): void
    {
        Events::addListener('on_before_render', function ($event) {
            $request = \Concrete\Core\Http\Request::getInstance();
            $path = $request->getPathInfo();

            // Only handle exceptions for API routes
            if (!str_starts_with($path, '/api/')) {
                return;
            }

            $exception = $request->attributes->get('exception');
            if ($exception instanceof \Throwable) {
                $handler = $this->app->make(ExceptionHandlerInterface::class);
                $response = $handler->toJsonResponse($exception);
                $response->send();
                exit;
            }
        });
    }

    private function registerHealthRoute(): void
    {
        $router = $this->app->make(Router::class);
        $router->register('/api/health', function () {
            $config = $this->app->make(ConfigInterface::class);
            if (!$config->get('monitoring.health_enabled', true)) {
                return new JsonResponse(['error' => 'Health check disabled'], 404);
            }

            $healthCheck = $this->app->make(HealthCheckInterface::class);
            $results = $healthCheck->check();

            $allHealthy = true;
            $services = [];
            foreach ($results as $name => $status) {
                $services[$name] = $status->value;
                if ($status->value !== 'healthy') {
                    $allHealthy = false;
                }
            }

            return new JsonResponse([
                'status' => $allHealthy ? 'healthy' : 'unhealthy',
                'services' => $services,
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ], $allHealthy ? 200 : 503);
        }, 'api.health', ['GET']);
    }
}
