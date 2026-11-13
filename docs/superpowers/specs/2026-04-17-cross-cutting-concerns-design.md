# Cross-Cutting Concerns Design Spec

**Date:** 2026-04-17
**Status:** Approved
**Scope:** Design, document, and implement all cross-cutting concerns for the Innovationsbox project — abstracted from Concrete CMS, loosely coupled, production-ready.

---

## Table of Contents

1. [Architecture Principle](#1-architecture-principle)
2. [Package Structure](#2-package-structure)
3. [Glossary](#3-glossary)
4. [Configuration](#4-configuration)
5. [Logging](#5-logging)
6. [Error Handling](#6-error-handling)
7. [Security & Auth](#7-security--auth)
8. [Caching](#8-caching)
9. [Transaction Management](#9-transaction-management)
10. [Monitoring & Metrics](#10-monitoring--metrics)
11. [Implementation Order](#11-implementation-order)
12. [Testing Strategy](#12-testing-strategy)
13. [Impact on Domain Packages](#13-impact-on-domain-packages)

---

## 1. Architecture Principle

Every cross-cutting concern follows the same layered pattern:

```
[Domain Package Code] --> [Our Interface] --> [CMS Adapter] --> [Concrete CMS Internals]
```

- Domain code calls our interface — never the CMS directly.
- The adapter implements the interface using CMS features.
- Swapping CMS = writing new adapters, nothing else changes.
- Interfaces live in `inno_shared/src/Infrastructure/Contract/`.
- Adapters live in `inno_shared/src/Infrastructure/Adapter/`.
- DI bindings registered in `inno_shared/controller.php` `on_start()`.

All interfaces extend PSR standards where available (PSR-3 for logging, PSR-16 for caching), with project-specific extensions on top.

---

## 2. Package Structure

All cross-cutting contracts, adapters, and shared exceptions live in `inno_shared`. The existing events move from `src/Event/` to `src/Domain/Event/` for consistent separation.

```
inno_shared/src/
  Domain/
    Event/                              <-- existing events, moved here
      ProjectIdeaSubmitted.php
      ProjectIdeaApproved.php
      ProjectIdeaRejected.php
      ModerationDecisionMade.php
      ProjectSpaceCreated.php

  Infrastructure/
    Contract/
      Logger/
        LoggerInterface.php             <-- extends Psr\Log\LoggerInterface
      Cache/
        CacheInterface.php              <-- extends Psr\SimpleCache\CacheInterface
      Auth/
        AuthInterface.php
        SessionInterface.php
        UserContext.php                  <-- value object
      Transaction/
        TransactionInterface.php
      Config/
        ConfigInterface.php
      Monitoring/
        MetricsInterface.php
        HealthCheckInterface.php
        HealthStatus.php                <-- enum
      ErrorHandling/
        ExceptionHandlerInterface.php

    Exception/
      BaseException.php
      ValidationException.php
      NotFoundException.php
      AuthException.php
      InfrastructureException.php

    Adapter/
      Logger/
        ConcreteCmsLogger.php
        SensitiveDataProcessor.php
      Cache/
        ConcreteCmsCache.php
        NullCache.php                   <-- for testing / dev
      Auth/
        ConcreteCmsAuth.php
        ConcreteCmsSession.php
      Transaction/
        ConcreteCmsTransaction.php
      Config/
        ConcreteCmsConfig.php
      Monitoring/
        LogBasedMetrics.php
        ConcreteCmsHealthCheck.php
      ErrorHandling/
        ConcreteCmsExceptionHandler.php
```

**Namespace change:** `Innovationsbox\Shared\Event\*` becomes `Innovationsbox\Shared\Domain\Event\*`. All importing packages are updated.

---

## 3. Glossary

| German | English | Role Description |
|---|---|---|
| Mitglied | Member | Authenticated user, can view public sector and vote on ideas |
| Entwickler | Developer | Can join and collaborate in project spaces |
| Initiator | Initiator | Can submit project ideas |
| Moderator | Moderator | Reviews, approves, and rejects submitted ideas |
| Administrator | Administrator | Full system configuration and user management (extends Concrete CMS admin) |

Unauthenticated visitors can view the public sector (published ideas, project info, voting).

---

## 4. Configuration

### Interface

```php
namespace Innovationsbox\Shared\Infrastructure\Contract\Config;

interface ConfigInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function has(string $key): bool;

    /**
     * @throws InfrastructureException if key is missing
     */
    public function getRequired(string $key): mixed;

    public function set(string $key, mixed $value): void;
}
```

### Adapter

`ConcreteCmsConfig` wraps `Concrete\Core\Config\Repository\Repository`. All project keys use the `innovationsbox.` prefix (e.g. `innovationsbox.notification.admin_email`).

### Boot-time validation

`inno_shared/controller.php` `on_start()` calls `getRequired()` for all critical keys. If a required key is missing, the package refuses to start with a clear error message.

### Environment layering

- `.env` for environment variables
- `application/config/concrete.php` for base config
- `application/config/live.database.php` for production overrides (activated by `CONCRETE5_ENV=live`)

### Config keys

| Key | Type | Required | Default | Purpose |
|---|---|---|---|---|
| `notification.admin_email` | string | yes | — | Admin notification recipient |
| `notification.from_email` | string | yes | — | Sender address |
| `logging.level` | string | no | `warning` | Minimum log level |
| `logging.stdout_enabled` | bool | no | `false` | Also log to stdout |
| `cache.default_ttl` | int | no | `3600` | Default cache TTL (seconds) |
| `monitoring.health_enabled` | bool | no | `true` | Enable /health endpoint |
| `auth.session_timeout` | int | no | `1800` | Session timeout (seconds) |
| `auth.max_failed_logins` | int | no | `5` | Lockout threshold |

### Sensitive values

DB credentials, API keys, SMTP credentials — only via `.env`, never in committed files.

---

## 5. Logging

### Interface

```php
namespace Innovationsbox\Shared\Infrastructure\Contract\Logger;

interface LoggerInterface extends \Psr\Log\LoggerInterface
{
    /**
     * Returns a new logger instance with the given context
     * merged into every subsequent log call.
     */
    public function withContext(array $context): static;
}
```

PSR-3 provides: `debug()`, `info()`, `warning()`, `error()`, `critical()`, `log()` — all with a `$context` array.

### Adapter

`ConcreteCmsLogger` wraps `Concrete\Core\Logging\Logger` (Monolog-based):

- One channel per package (e.g. `inno_project_idea`, `inno_moderation`)
- `withContext()` returns a new instance with merged default context
- Automatically includes: timestamp, channel, log level
- Configurable stdout handler enabled via `logging.stdout_enabled` config

### Sensitive data scrubbing

`SensitiveDataProcessor` (Monolog processor) strips keys matching: `password`, `token`, `secret`, `authorization`, `cookie`, `credit_card`. Applied globally — domain code doesn't need to think about it.

### Log level per environment

| Environment | Minimum level |
|---|---|
| Development | `debug` |
| Staging | `info` |
| Production | `warning` (configurable) |

### When to log what

| Level | Use for |
|---|---|
| `debug` | Method entry/exit, variable state, SQL queries |
| `info` | Domain events dispatched, user actions, successful operations |
| `warning` | Recoverable failures, cache misses on expected data, deprecated usage |
| `error` | Failed operations, external service failures, caught exceptions |
| `critical` | System unusable — DB down, required config missing, auth system failure |

---

## 6. Error Handling

### Exception hierarchy

```php
namespace Innovationsbox\Shared\Infrastructure\Exception;

abstract class BaseException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array;
    abstract public function getHttpStatusCode(): int;
    abstract public function getUserMessage(): string;
}

class ValidationException extends BaseException
{
    // 400 — invalid input, missing fields, constraint violations
    public function getHttpStatusCode(): int { return 400; }
    public function getUserMessage(): string { return 'The provided data is invalid.'; }
}

class NotFoundException extends BaseException
{
    // 404 — entity not found
    public function getHttpStatusCode(): int { return 404; }
    public function getUserMessage(): string { return 'The requested resource was not found.'; }
}

class AuthException extends BaseException
{
    // 401 if not authenticated, 403 if authenticated but unauthorized
    private int $httpStatus;

    public function __construct(
        string $message,
        array $context = [],
        bool $authenticated = true,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        $this->httpStatus = $authenticated ? 403 : 401;
        parent::__construct($message, $context, $code, $previous);
    }

    public function getHttpStatusCode(): int { return $this->httpStatus; }
    public function getUserMessage(): string { return 'You do not have permission to perform this action.'; }
}

class InfrastructureException extends BaseException
{
    // 500 — DB failure, cache failure, external service down
    public function getHttpStatusCode(): int { return 500; }
    public function getUserMessage(): string { return 'An internal error occurred. Please try again later.'; }
}
```

### Domain-specific subclasses

Each domain package defines its own exceptions extending the shared base classes. Example:

```php
namespace Innovationsbox\ProjectIdea\Domain\Exception;

class ProjectIdeaNotFoundException extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self("ProjectIdea '{$id}' not found.", ['ideaId' => $id]);
    }
}

class InvalidProjectIdeaException extends ValidationException
{
    public static function emptyTitle(): self
    {
        return new self('Project idea title cannot be empty.', ['field' => 'title']);
    }
}
```

### Global exception handler

```php
namespace Innovationsbox\Shared\Infrastructure\Contract\ErrorHandling;

interface ExceptionHandlerInterface
{
    public function handle(\Throwable $e): void;
}
```

`ConcreteCmsExceptionHandler` hooks into Concrete CMS's request lifecycle as an event listener:

- **`BaseException` subclass:** log at appropriate level, return JSON with `getUserMessage()` and `getHttpStatusCode()`
- **Unexpected `\Throwable`:** log at `critical` with full stack trace + request context + user ID, return generic 500 JSON
- **Production:** never expose stack traces or internal details
- **Development:** include stack trace and exception class in response

### API error response format

**Production:**
```json
{
    "error": {
        "message": "The requested resource was not found.",
        "code": 404
    }
}
```

**Development** (additional debug info):
```json
{
    "error": {
        "message": "The requested resource was not found.",
        "code": 404,
        "debug": {
            "exception": "ProjectIdeaNotFoundException",
            "detail": "ProjectIdea 'abc-123' not found.",
            "trace": "..."
        }
    }
}
```

---

## 7. Security & Auth

### Interfaces

```php
namespace Innovationsbox\Shared\Infrastructure\Contract\Auth;

interface AuthInterface
{
    public function getCurrentUser(): ?UserContext;
    public function isAuthenticated(): bool;
    public function hasPermission(string $permission): bool;
    public function hasRole(string $role): bool;
    public function getUserId(): ?int;
}

interface SessionInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function remove(string $key): void;
    public function invalidate(): void;
}
```

### UserContext value object

```php
namespace Innovationsbox\Shared\Infrastructure\Contract\Auth;

final readonly class UserContext
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public array $roles,
    ) {}
}
```

Decouples domain code from Concrete CMS's `User` / `UserInfo` classes.

### Adapters

- `ConcreteCmsAuth` wraps `Concrete\Core\User\User` + `Concrete\Core\Permission\Checker`
- `ConcreteCmsSession` wraps `Symfony\Component\HttpFoundation\Session\Session`

### Roles

Mapped to Concrete CMS user groups:

| Role constant | CMS Group | Capabilities |
|---|---|---|
| `member` | Mitglied | View public sector, vote on ideas |
| `developer` | Entwickler | Join and collaborate in project spaces |
| `initiator` | Initiator | Submit project ideas |
| `moderator` | Moderator | Approve/reject ideas in moderation queue |
| `administrator` | Administrator | Full system config + CMS admin |

Checked via `hasRole('moderator')`, which the adapter maps to CMS group membership.

### Permissions

Action-based; roles grant permissions:

| Permission | Granted to | Used where |
|---|---|---|
| `idea.submit` | initiator, administrator | ProjectIdeaApiController |
| `idea.view` | all (including unauthenticated) | Public sector pages |
| `idea.vote` | member, developer, initiator, moderator, administrator | Voting endpoint |
| `moderation.review` | moderator, administrator | ModerationApiController |
| `space.join` | developer, initiator, administrator | ProjectSpaceApiController |
| `space.manage` | administrator | Space administration |

### Security flow

```
Request --> CMS session/auth middleware --> Controller
  --> AuthInterface::hasPermission() check
    --> Pass: execute use case
    --> Fail: throw AuthException --> ExceptionHandler --> 403 JSON
```

### CMS-level security (configured, not abstracted)

| Concern | Approach |
|---|---|
| CSRF | Concrete CMS token validation on all state-changing requests |
| Output escaping | CMS `h()` helper in templates, JSON responses naturally escaped |
| SQL injection | Parameterized queries via Doctrine DBAL Connection |
| Session timeout | Configured via `auth.session_timeout` config key |
| Failed login lockout | Configured via `auth.max_failed_logins` config key |
| Secure headers | CSP, X-Frame-Options, HSTS via `.htaccess` / nginx config |
| Password policy | Concrete CMS password validation settings |

---

## 8. Caching

### Interface

```php
namespace Innovationsbox\Shared\Infrastructure\Contract\Cache;

interface CacheInterface extends \Psr\SimpleCache\CacheInterface
{
    /**
     * Get or compute: returns cached value, or calls $callback,
     * caches the result, and returns it.
     */
    public function getOrSet(string $key, int $ttl, callable $callback): mixed;

    /**
     * Store a value with one or more tags for grouped invalidation.
     */
    public function setTagged(string $key, mixed $value, int $ttl, array $tags): void;

    /**
     * Tag-based invalidation: flush all entries sharing a tag.
     */
    public function flushTag(string $tag): void;
}
```

PSR-16 provides: `get()`, `set()`, `delete()`, `has()`, `clear()`, `getMultiple()`, `setMultiple()`, `deleteMultiple()`.

### Adapter

`ConcreteCmsCache` wraps Concrete CMS's file-based cache. Tag support implemented via a tag index — a cached array per tag listing associated keys. `flushTag()` reads the index and deletes each key.

`NullCache` — stores nothing, for testing and development.

### Cache key convention

```
innovationsbox:{package}:{entity}:{id}:{variant}
```

Examples:
- `innovationsbox:project_idea:idea:abc-123:detail`
- `innovationsbox:project_idea:ideas:status:submitted`
- `innovationsbox:moderation:review:idea:abc-123`

### Tag convention

```
{entity}:{id}
```

Examples:
- `project_idea:abc-123` — flush when idea is updated/approved/rejected
- `moderation:reviews` — flush when any review changes

### TTL strategy

| Data type | TTL | Reason |
|---|---|---|
| Single entity by ID | 1800s (30 min) | Changes infrequently, invalidated on write |
| List/collection queries | 600s (10 min) | More volatile, new entries appear |
| Public page data | 3600s (1 hour) | Rarely changes |
| User/auth context | 0 (no cache) | Must always be fresh |
| Config values | 3600s (1 hour) | Changes only on deploy |

### Invalidation via domain events

| Event | Tags flushed |
|---|---|
| `ProjectIdeaSubmitted` | `project_idea:list` |
| `ProjectIdeaApproved` | `project_idea:{id}`, `project_idea:list`, `moderation:reviews` |
| `ProjectIdeaRejected` | `project_idea:{id}`, `project_idea:list`, `moderation:reviews` |
| `ProjectSpaceCreated` | `project_space:list` |

### Development/testing

Cache disabled by default in dev (configurable). Tests use `NullCache`.

---

## 9. Transaction Management

### Interface

```php
namespace Innovationsbox\Shared\Infrastructure\Contract\Transaction;

interface TransactionInterface
{
    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;

    /**
     * Wraps a callable in begin/commit/rollback.
     * Returns the callable's return value.
     * Rolls back and rethrows on any exception.
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed;

    /**
     * Current nesting depth. 0 = no active transaction.
     */
    public function getDepth(): int;
}
```

### Adapter

`ConcreteCmsTransaction` wraps `Concrete\Core\Database\Connection\Connection` (Doctrine DBAL). Nested transactions handled via DBAL savepoints. `getDepth()` exposes nesting level for debugging.

```php
public function transactional(callable $operation): mixed
{
    $this->begin();
    try {
        $result = $operation();
        $this->commit();
        return $result;
    } catch (\Throwable $e) {
        $this->rollback();
        throw $e;
    }
}
```

### Where transactions are needed

| Handler | Atomic operations |
|---|---|
| `SubmitProjectIdeaHandler` | save idea + dispatch event |
| `ReviewProjectIdeaHandler` | save review + dispatch event |
| `OnModerationDecisionMade` | load idea + update status + save + dispatch event |
| `OnProjectIdeaApproved` | create space + save + dispatch event |

### Handler usage pattern

```php
public function handle(SubmitProjectIdeaCommand $command): ProjectIdea
{
    return $this->transaction->transactional(function () use ($command) {
        $idea = ProjectIdea::submit(/* ... */);
        $this->repository->save($idea);
        $this->eventDispatcher->dispatch(new ProjectIdeaSubmitted(/* ... */));
        $this->logger->info('Project idea submitted', ['ideaId' => $idea->getId()]);
        return $idea;
    });
}
```

### Event dispatch within transactions

Events are dispatched inside the transaction. If a listener fails, the whole transaction rolls back. This guarantees consistency. Listeners must be fast and reliable — slow work (e.g. email) should be queued rather than executed synchronously.

---

## 10. Monitoring & Metrics

### Interfaces

```php
namespace Innovationsbox\Shared\Infrastructure\Contract\Monitoring;

interface MetricsInterface
{
    public function increment(string $counter, int $value = 1, array $tags = []): void;
    public function gauge(string $name, float $value, array $tags = []): void;
    public function timing(string $name, float $milliseconds, array $tags = []): void;

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function measure(string $name, callable $operation, array $tags = []): mixed;

    public function event(string $name, array $context = []): void;
}

interface HealthCheckInterface
{
    /** @return array<string, HealthStatus> */
    public function check(): array;
}

enum HealthStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unhealthy = 'unhealthy';
}
```

### Adapters

`LogBasedMetrics` — all metrics go to the logger in structured format:

```
[INFO] metric.counter | name=idea.submitted value=1 status=draft
[INFO] metric.timing  | name=handler.duration ms=142.5 handler=SubmitProjectIdea
```

Parseable by log aggregation tools (Loki, ELK) when infrastructure grows.

`ConcreteCmsHealthCheck` checks:

| Service | Healthy | Degraded | Unhealthy |
|---|---|---|---|
| Database | `SELECT 1` < 100ms | < 1000ms | timeout/error |
| Cache | set + get test key works | — | fails |
| Filesystem | write + read temp file works | — | fails |
| CMS | `Config::get('concrete.version')` returns value | — | fails |

### Health endpoint

`/api/health` registered by `inno_shared`. Enabled/disabled via `monitoring.health_enabled` config.

```json
{
    "status": "healthy",
    "services": {
        "database": "healthy",
        "cache": "healthy",
        "filesystem": "healthy",
        "cms": "healthy"
    },
    "timestamp": "2026-04-17T10:30:00+00:00"
}
```

### Instrumented metrics

| Metric | Type | Where |
|---|---|---|
| `request.duration` | timing | Exception handler middleware |
| `handler.duration` | timing | Each use case handler |
| `db.query.count` | counter | Transaction adapter |
| `cache.hit` / `cache.miss` | counter | Cache adapter |
| `idea.submitted` | counter | SubmitProjectIdeaHandler |
| `idea.approved` / `idea.rejected` | counter | OnModerationDecisionMade |
| `auth.failure` | counter | Auth adapter |
| `email.sent` / `email.failed` | counter | Email adapter |
| `event.dispatched` | counter | Event dispatcher |

### Future APM

When an APM tool is added, write a new adapter implementing `MetricsInterface` and swap the binding. No other code changes.

---

## 11. Implementation Order

Each concern depends on the previous ones:

```
1. Namespace restructure (move events to Domain/Event/)
   |
2. Configuration (everything else reads config)
   |
3. Logging (needs config for log level/stdout)
   |
4. Error Handling (needs logging for exception handler)
   |
5. Security & Auth (needs logging + error handling for AuthException)
   |
6. Caching (needs config for TTL, logging for miss tracking)
   |
7. Transaction Management (needs logging for rollback tracking)
   |
8. Monitoring & Metrics (needs all of the above)
```

Per concern:
1. Define interface in `inno_shared/Infrastructure/Contract/`
2. Write CMS adapter in `inno_shared/Infrastructure/Adapter/`
3. Register DI binding in `inno_shared/controller.php`
4. Unit test the adapter (mock CMS layer)
5. Integration test against real CMS
6. Update domain packages to inject and use the interface
7. Write documentation

---

## 12. Testing Strategy

| Layer | What | How |
|---|---|---|
| Unit | Each adapter in isolation | Mock CMS classes (Mockery) |
| Unit | Handlers with cross-cutting concerns | Mock all interfaces |
| Integration | Adapters against real CMS | Boot CMS, test real logging/cache/DB |
| Integration | Exception handler | Trigger real exceptions, verify JSON responses |
| E2E | Auth flow | Playwright tests with different user roles |

Coverage: 80% minimum on all `inno_*/src/`, enforced in CI.

---

## 13. Impact on Domain Packages

### What changes

- **Handlers** get `LoggerInterface`, `TransactionInterface` injected via constructor
- **Controllers** lose try/catch — global exception handler takes over
- **Controllers** get `AuthInterface` injected for permission checks
- **Repositories** get `CacheInterface` injected for cached queries
- **Namespace update:** all `use Innovationsbox\Shared\Event\*` becomes `use Innovationsbox\Shared\Domain\Event\*`
- **Each package's `on_start()`** gets simpler — cross-cutting bindings are in `inno_shared`
- **Domain-specific exceptions** added per package under `Domain/Exception/`

### Handler pattern after implementation

```php
class SubmitProjectIdeaHandler implements SubmitProjectIdeaPort
{
    public function __construct(
        private TransactionInterface $transaction,
        private ProjectIdeaRepositoryInterface $repository,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
    ) {}

    public function handle(SubmitProjectIdeaCommand $command): ProjectIdea
    {
        return $this->transaction->transactional(function () use ($command) {
            $idea = ProjectIdea::submit(/* ... */);
            $this->repository->save($idea);
            $this->eventDispatcher->dispatch(new ProjectIdeaSubmitted(/* ... */));
            $this->logger->info('Project idea submitted', ['ideaId' => $idea->getId()]);
            return $idea;
        });
    }
}
```

### Controller pattern after implementation

```php
class ProjectIdeaApiController extends AbstractController
{
    public function __construct(
        private SubmitProjectIdeaPort $submitPort,
        private AuthInterface $auth,
    ) {}

    public function submit(Request $request): JsonResponse
    {
        // Permission check — AuthException handled globally
        if (!$this->auth->hasPermission('idea.submit')) {
            throw new AuthException('Permission denied', ['permission' => 'idea.submit']);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $idea = $this->submitPort->handle(new SubmitProjectIdeaCommand(/* ... */));

        return new JsonResponse(['id' => $idea->getId()], 201);
    }
}
```
