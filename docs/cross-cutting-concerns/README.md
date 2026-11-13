# Cross-Cutting Concerns

Cross-cutting concerns are technical capabilities that every domain package needs — configuration, logging, error handling, and security — but which must not leak Concrete CMS dependencies into domain or application code.

## Architecture Principle

Every concern follows the same layering rule:

```
[Domain Code] -> [Our Interface] -> [CMS Adapter] -> [Concrete CMS]
```

Domain and application code depend only on interfaces defined in `inno_shared`. Concrete CMS is an implementation detail, replaceable without touching any domain logic.

## Architecture Diagram

```
+----------------------------------------------------------+
|  Domain / Application Layer (inno_project_idea, etc.)   |
|                                                          |
|  Depends on:                                             |
|    ConfigInterface    LoggerInterface                    |
|    AuthInterface      SessionInterface                   |
|    ExceptionHandlerInterface                             |
+----------------------------------------------------------+
                          |
                          v  (interfaces only)
+----------------------------------------------------------+
|  inno_shared / Infrastructure / Contract /               |
|                                                          |
|    ConfigInterface          LoggerInterface              |
|    AuthInterface            SessionInterface             |
|    ExceptionHandlerInterface                             |
|    TransactionInterface     MetricsInterface             |
|    CacheInterface           HealthCheckInterface         |
+----------------------------------------------------------+
                          |
                          v  (CMS implementations)
+----------------------------------------------------------+
|  inno_shared / Infrastructure / Adapter /                |
|                                                          |
|    ConcreteCmsConfig        ConcreteCmsLogger            |
|    ConcreteCmsAuth          ConcreteCmsSession           |
|    ConcreteCmsExceptionHandler                           |
+----------------------------------------------------------+
                          |
                          v
+----------------------------------------------------------+
|  Concrete CMS Core                                       |
|                                                          |
|    Config Repository        Monolog / LoggerFactory      |
|    User / Group system      Session                      |
|    Error handling           DB Connection                |
+----------------------------------------------------------+
```

## Package Structure

All shared infrastructure lives in `public/packages/inno_shared/src/`:

```
public/packages/inno_shared/src/
  Domain/
    Event/                     — shared domain event classes
      ProjectIdeaSubmitted.php
      ProjectIdeaApproved.php
      ProjectIdeaRejected.php
      ModerationDecisionMade.php
      ProjectSpaceCreated.php
  Infrastructure/
    Contract/                  — interfaces (ports)
      ConfigInterface.php
      LoggerInterface.php
      CacheInterface.php
      AuthInterface.php
      SessionInterface.php
      TransactionInterface.php
      MetricsInterface.php
      HealthCheckInterface.php
      ExceptionHandlerInterface.php
    Adapter/                   — Concrete CMS implementations
      ConcreteCmsConfig.php
      ConcreteCmsLogger.php
      ConcreteCmsAuth.php
      ConcreteCmsSession.php
      ConcreteCmsExceptionHandler.php
    Exception/                 — shared exception hierarchy
      BaseException.php
      ValidationException.php
      NotFoundException.php
      AuthException.php
      InfrastructureException.php
```

## Concerns Overview

| # | Concern | Interface(s) | Adapter | Doc |
|---|---------|-------------|---------|-----|
| 1 | Configuration | `ConfigInterface` | `ConcreteCmsConfig` | [01-configuration.md](01-configuration.md) |
| 2 | Logging | `LoggerInterface` | `ConcreteCmsLogger` | [02-logging.md](02-logging.md) |
| 3 | Error Handling | `ExceptionHandlerInterface` | `ConcreteCmsExceptionHandler` | [03-error-handling.md](03-error-handling.md) |
| 4 | Security & Auth | `AuthInterface`, `SessionInterface` | `ConcreteCmsAuth`, `ConcreteCmsSession` | [04-security-auth.md](04-security-auth.md) |
| 5 | Caching | `CacheInterface` | `ConcreteCmsCache`, `NullCache` | [05-caching.md](05-caching.md) |
| 6 | Transaction Management | `TransactionInterface` | `ConcreteCmsTransaction` | [06-transaction-management.md](06-transaction-management.md) |
| 7 | Monitoring & Metrics | `MetricsInterface`, `HealthCheckInterface` | `LogBasedMetrics`, `ConcreteCmsHealthCheck` | [07-monitoring-metrics.md](07-monitoring-metrics.md) |

## Dependency Injection

All interface-to-adapter bindings are registered in `inno_shared/controller.php` inside `on_start()`:

```php
// public/packages/inno_shared/controller.php

public function on_start(): void
{
    $this->app->bind(
        \Concrete\Package\InnoShared\Infrastructure\Contract\ConfigInterface::class,
        \Concrete\Package\InnoShared\Infrastructure\Adapter\ConcreteCmsConfig::class
    );

    $this->app->bind(
        \Concrete\Package\InnoShared\Infrastructure\Contract\LoggerInterface::class,
        \Concrete\Package\InnoShared\Infrastructure\Adapter\ConcreteCmsLogger::class
    );

    $this->app->bind(
        \Concrete\Package\InnoShared\Infrastructure\Contract\AuthInterface::class,
        \Concrete\Package\InnoShared\Infrastructure\Adapter\ConcreteCmsAuth::class
    );

    $this->app->bind(
        \Concrete\Package\InnoShared\Infrastructure\Contract\SessionInterface::class,
        \Concrete\Package\InnoShared\Infrastructure\Adapter\ConcreteCmsSession::class
    );

    $this->app->bind(
        \Concrete\Package\InnoShared\Infrastructure\Contract\ExceptionHandlerInterface::class,
        \Concrete\Package\InnoShared\Infrastructure\Adapter\ConcreteCmsExceptionHandler::class
    );
}
```

Because `inno_shared` is installed before all domain packages, its bindings are available when any other package's `on_start()` runs. Domain packages inject the interface — never the adapter class — via constructor injection.

## Related Documents

- [Glossary](glossary.md) — roles, permissions, domain terms, and architecture concepts
- [CLAUDE.md](../../CLAUDE.md) — developer commands, architecture overview, and package communication
- [Architecture Overview](../architecture/) — full arc42 documentation (Typst source in `../innovationsbox-dokumentation`)
