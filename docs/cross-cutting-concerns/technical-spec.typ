#set document(
  title: "Innovationsbox – Cross-Cutting Concerns Technical Specification",
  author: "lemonbrain",
)
#set text(font: "New Computer Modern", size: 11pt)
#set heading(numbering: "1.1")
#set page(
  paper: "a4",
  margin: (top: 2.5cm, bottom: 2.5cm, left: 3cm, right: 2.5cm),
  numbering: "1",
  header: context [
    #set text(size: 9pt, fill: luma(120))
    Innovationsbox -- Cross-Cutting Concerns Technical Specification
    #h(1fr)
    #counter(page).display("1")
  ],
)
#set par(justify: true, leading: 0.65em)
#show heading.where(level: 1): it => {
  pagebreak(weak: true)
  v(0.5em)
  it
  v(0.3em)
}
#show raw: set text(font: "New Computer Modern Mono", size: 9.5pt)
#show raw.where(block: true): block.with(
  fill: luma(245),
  inset: (x: 10pt, y: 8pt),
  radius: 3pt,
  width: 100%,
)

// --------------------------------------------------------------------------
// Title Page
// --------------------------------------------------------------------------

#align(center)[
  #v(4cm)
  #text(size: 20pt, weight: "bold")[
    Innovationsbox
  ]
  #v(0.4em)
  #text(size: 16pt)[
    Cross-Cutting Concerns \
    Technical Specification
  ]
  #v(2cm)
  #line(length: 100%, stroke: 0.5pt)
  #v(0.5cm)
  #grid(
    columns: (auto, 1fr),
    gutter: 0.4em,
    [*Project:*],   [Innovationsbox (klimaglarus / Arento)],
    [*Package:*],   [`inno_shared`],
    [*Version:*],   [1.0],
    [*Status:*],    [Approved],
    [*Date:*],      [2026-04-17],
    [*Author:*],    [lemonbrain],
  )
  #v(0.5cm)
  #line(length: 100%, stroke: 0.5pt)
]

#pagebreak()

// --------------------------------------------------------------------------
// Table of Contents
// --------------------------------------------------------------------------

#outline(
  title: [Table of Contents],
  indent: auto,
  depth: 3,
)

// --------------------------------------------------------------------------
// 1. Document Information
// --------------------------------------------------------------------------

= Document Information

#table(
  columns: (auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Field*], [*Value*],
  [Document title], [Cross-Cutting Concerns Technical Specification],
  [Project], [Innovationsbox],
  [Owning package], [`public/packages/inno_shared`],
  [Version], [1.0],
  [Status], [Approved],
  [Date], [2026-04-17],
  [Related spec], [`docs/superpowers/specs/2026-04-17-cross-cutting-concerns-design.md`],
)

== Scope

This document specifies the cross-cutting concerns of the Innovationsbox platform: the technical capabilities that every domain package requires (configuration, logging, error handling, security, caching, transaction management, and monitoring) but which must not embed Concrete CMS dependencies into domain or application code.

The specification covers:
- The architecture principle governing all concerns.
- Full interface signatures with parameter types and return types.
- The complete exception hierarchy.
- The adapter mapping from each interface to its Concrete CMS implementation.
- Decisions made during design and their rationale.
- Security policy for authentication, authorisation, and data protection.

== Conventions Used

Code blocks show PHP 8.2+ syntax. Namespaces are abbreviated after the first appearance: `Concrete\Package\InnoShared\Infrastructure\Contract\` is written as `Contract\`. Interface method signatures are listed with their exact PHP types as declared in the source.

// --------------------------------------------------------------------------
// 2. Architecture Overview
// --------------------------------------------------------------------------

= Architecture Overview

== Principle

Every cross-cutting concern follows a single layering rule:

#align(center)[
  _Domain / Application Layer_
  #sym.arrow.b
  _Our Interface (inno\_shared Contract)_
  #sym.arrow.b
  _CMS Adapter (inno\_shared Adapter)_
  #sym.arrow.b
  _Concrete CMS Core_
]

Domain and application code depend only on interfaces. Adapters implement those interfaces using Concrete CMS APIs. The CMS is an implementation detail: replacing it requires writing new adapters and updating DI bindings. No other code changes.

All interfaces extend PSR standards where a suitable standard exists (PSR-3 for logging, PSR-16 for caching). Project-specific methods are added on top.

== Layer Diagram

```
+-------------------------------------------------------------+
|  Domain / Application Layer                                 |
|  (inno_project_idea, inno_moderation, etc.)                 |
|                                                             |
|  Type-hints only interfaces -- never CMS classes directly.  |
+-------------------------------------------------------------+
                           |
                           | (interfaces only)
                           v
+-------------------------------------------------------------+
|  inno_shared / Infrastructure / Contract /                  |
|                                                             |
|  Config/ConfigInterface      Logger/LoggerInterface         |
|  Auth/AuthInterface          Auth/SessionInterface          |
|  ErrorHandling/ExceptionHandlerInterface                    |
|  Cache/CacheInterface        Transaction/TransactionInterface|
|  Monitoring/MetricsInterface  Monitoring/HealthCheckInterface|
+-------------------------------------------------------------+
                           |
                           | (CMS implementations)
                           v
+-------------------------------------------------------------+
|  inno_shared / Infrastructure / Adapter /                   |
|                                                             |
|  Config/ConcreteCmsConfig    Logger/ConcreteCmsLogger       |
|  Auth/ConcreteCmsAuth        Auth/ConcreteCmsSession        |
|  ErrorHandling/ConcreteCmsExceptionHandler                  |
|  Cache/ConcreteCmsCache      Cache/NullCache                |
|  Transaction/ConcreteCmsTransaction                         |
|  Monitoring/LogBasedMetrics  Monitoring/ConcreteCmsHealthCheck|
+-------------------------------------------------------------+
                           |
                           v
+-------------------------------------------------------------+
|  Concrete CMS Core                                          |
|                                                             |
|  Config\Repository\Repository   Logging\Logger (Monolog)    |
|  User / Group system            Symfony Session             |
|  Database\Connection (DBAL)     Stash cache pool            |
+-------------------------------------------------------------+
```

== Dependency Rule

The allowed import directions are:

- `Adapter/In` (controllers) imports `Contract/` interfaces.
- `Application/` (handlers, event handlers) imports `Contract/` interfaces and `Domain/` types.
- `Adapter/Out` (repositories, email) imports `Contract/` and `Domain/` types.
- `Domain/` imports nothing from `inno_shared` infrastructure.
- No domain package imports from another domain package. Cross-package events are the only coupling and they go through `inno_shared/Domain/Event/`.

== Dependency Injection Bootstrap

All bindings are registered in `inno_shared/controller.php` inside `on_start()`. Because `inno_shared` is installed before all domain packages, its bindings are available when any other package's `on_start()` runs. Domain packages inject interfaces via constructor type-hints; the container resolves adapters transparently.

Binding lifecycle:

#table(
  columns: (auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Interface*], [*Lifecycle*], [*Reason*],
  [`ConfigInterface`], [singleton], [Global, shared state],
  [`LoggerInterface`], [singleton], [`withContext()` returns child instances; base is stateless],
  [`ExceptionHandlerInterface`], [singleton], [Stateless],
  [`AuthInterface`], [per-request (`bind`)], [Wraps per-request `User` object],
  [`SessionInterface`], [per-request (`bind`)], [Wraps per-request `Session` object],
  [`CacheInterface`], [singleton], [Pool is shared across requests],
  [`TransactionInterface`], [per-request (`bind`)], [Wraps per-request DB connection],
  [`MetricsInterface`], [singleton], [`LogBasedMetrics` is stateless],
  [`HealthCheckInterface`], [singleton], [Stateless],
)

// --------------------------------------------------------------------------
// 3. Interface Specifications
// --------------------------------------------------------------------------

= Interface Specifications

== ConfigInterface

*Namespace:* `Concrete\Package\InnoShared\Infrastructure\Contract\Config`

*Source:* `inno_shared/src/Infrastructure/Contract/Config/ConfigInterface.php`

```php
interface ConfigInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    /**
     * @throws InfrastructureException if the key is absent or null
     */
    public function getRequired(string $key): mixed;

    public function set(string $key, mixed $value): void;
}
```

#table(
  columns: (auto, auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Method*], [*Parameters*], [*Returns*], [*Notes*],
  [`get`], [`string $key`, `mixed $default = null`], [`mixed`], [Returns `$default` when key is absent. Auto-prefixes `innovationsbox.` before reading from the CMS repository.],
  [`has`], [`string $key`], [`bool`], [Returns `true` when the key exists and is not `null`.],
  [`getRequired`], [`string $key`], [`mixed`], [Returns the value; throws `InfrastructureException` when the key is absent or `null`.],
  [`set`], [`string $key`, `mixed $value`], [`void`], [Writes the value at runtime. Not persisted to disk.],
)

=== Known Configuration Keys

All keys are stored under the `innovationsbox.` namespace. Pass the short form (without prefix) to `ConfigInterface` methods.

#table(
  columns: (auto, auto, auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Key*], [*Type*], [*Required*], [*Default*], [*Purpose*],
  [`notification.admin_email`], [string], [yes], [--], [Admin notification recipient],
  [`notification.from_email`], [string], [yes], [--], [Sender address for outgoing email],
  [`logging.level`], [string], [no], [`warning`], [Minimum log level],
  [`logging.stdout_enabled`], [bool], [no], [`false`], [Also write logs to stdout],
  [`cache.default_ttl`], [int], [no], [`3600`], [Default cache TTL in seconds],
  [`monitoring.health_enabled`], [bool], [no], [`true`], [Enable the `/api/health` endpoint],
  [`app.debug`], [bool], [no], [`false`], [Include debug info in error responses],
  [`auth.session_timeout`], [int], [no], [`1800`], [Idle session timeout in seconds],
  [`auth.max_failed_logins`], [int], [no], [`5`], [Failed login attempts before lockout],
)

== LoggerInterface

*Namespace:* `Concrete\Package\InnoShared\Infrastructure\Contract\Logger`

*Source:* `inno_shared/src/Infrastructure/Contract/Logger/LoggerInterface.php`

```php
interface LoggerInterface extends \Psr\Log\LoggerInterface
{
    /**
     * Returns a new logger instance with the given context merged into
     * every subsequent log call. The original instance is not mutated.
     */
    public function withContext(array $context): static;
}
```

`LoggerInterface` extends `Psr\Log\LoggerInterface`, which provides the following methods (all signatures are identical; `$context` is always `array`):

```php
public function emergency(string|\Stringable $message, array $context = []): void;
public function alert(string|\Stringable $message, array $context = []): void;
public function critical(string|\Stringable $message, array $context = []): void;
public function error(string|\Stringable $message, array $context = []): void;
public function warning(string|\Stringable $message, array $context = []): void;
public function notice(string|\Stringable $message, array $context = []): void;
public function info(string|\Stringable $message, array $context = []): void;
public function debug(string|\Stringable $message, array $context = []): void;
public function log(mixed $level, string|\Stringable $message, array $context = []): void;
```

The project addition is `withContext(array $context): static`. It returns a new instance of the same concrete class carrying `$context` merged into every subsequent log call. The returned instance is immutable with respect to the call-site context; calling `withContext()` again on the returned instance merges further context on top.

=== SensitiveDataProcessor

`ConcreteCmsLogger` attaches `SensitiveDataProcessor` as a Monolog processor. It recursively inspects the `context` array of every log record and replaces the value of any key whose name contains one of the following patterns (case-insensitive substring match) with `***REDACTED***`:

`password`, `token`, `secret`, `authorization`, `cookie`, `credit_card`

== ExceptionHandlerInterface

*Namespace:* `Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling`

*Source:* `inno_shared/src/Infrastructure/Contract/ErrorHandling/ExceptionHandlerInterface.php`

```php
interface ExceptionHandlerInterface
{
    public function toJsonResponse(\Throwable $e): JsonResponse;
}
```

#table(
  columns: (auto, auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Method*], [*Parameters*], [*Returns*], [*Notes*],
  [`toJsonResponse`], [`\Throwable $e`], [`Symfony\Component\HttpFoundation\JsonResponse`], [Converts any throwable to an HTTP JSON response. `BaseException` subclasses are handled by status code; unknown throwables become 500.],
)

The handler is registered as a global listener for all `/api/*` routes. Controllers do not need try/catch blocks -- they throw domain exceptions and the handler converts them.

=== API Error Response Format

*Production:*
```json
{
    "error": {
        "message": "The requested resource was not found.",
        "code": 404
    }
}
```

*Development* (`app.debug = true` adds the `debug` field):
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

== Exception Hierarchy

All shared exception classes live in `inno_shared/src/Infrastructure/Exception/`.

```
\RuntimeException
  BaseException  (abstract)
    ValidationException       -- HTTP 400
    NotFoundException         -- HTTP 404
    AuthException             -- HTTP 401 or 403
    InfrastructureException   -- HTTP 500
```

=== BaseException

*Source:* `inno_shared/src/Infrastructure/Exception/BaseException.php`

```php
abstract class BaseException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null,
    );

    public function getContext(): array;

    abstract public function getHttpStatusCode(): int;

    abstract public function getUserMessage(): string;
}
```

`getContext()` returns structured metadata attached at throw time. `getUserMessage()` returns a safe, human-readable string for the JSON response body -- never the internal exception message.

=== ValidationException

HTTP 400. Used for invalid input, missing required fields, and domain constraint violations.

```php
class ValidationException extends BaseException
{
    public function getHttpStatusCode(): int  { return 400; }
    public function getUserMessage(): string  { return 'The provided data is invalid.'; }
}
```

=== NotFoundException

HTTP 404. Used when a requested entity does not exist.

```php
class NotFoundException extends BaseException
{
    public function getHttpStatusCode(): int  { return 404; }
    public function getUserMessage(): string  { return 'The requested resource was not found.'; }
}
```

=== AuthException

HTTP 401 (unauthenticated) or HTTP 403 (authenticated but unauthorised). The constructor parameter `$authenticated` controls which status code is returned.

```php
class AuthException extends BaseException
{
    public function __construct(
        string $message,
        array $context = [],
        bool $authenticated = true,  // false -> 401, true -> 403
        int $code = 0,
        ?\Throwable $previous = null,
    );

    public function getHttpStatusCode(): int;  // 401 or 403, set in constructor
    public function getUserMessage(): string   { return 'You do not have permission to perform this action.'; }
}
```

=== InfrastructureException

HTTP 500. Used for DB failures, cache failures, missing required configuration, and external service failures.

```php
class InfrastructureException extends BaseException
{
    public function getHttpStatusCode(): int  { return 500; }
    public function getUserMessage(): string  { return 'An internal error occurred. Please try again later.'; }
}
```

=== Domain-Specific Subclasses

Each domain package extends the shared base classes under its own `Domain/Exception/` namespace. Example from `inno_project_idea`:

```php
namespace Concrete\Package\InnoProjectIdea\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;

class ProjectIdeaNotFoundException extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self("ProjectIdea '{$id}' not found.", ['ideaId' => $id]);
    }
}
```

Named constructors are required for every distinct error case; they make call sites self-documenting.

== AuthInterface

*Namespace:* `Concrete\Package\InnoShared\Infrastructure\Contract\Auth`

*Source:* `inno_shared/src/Infrastructure/Contract/Auth/AuthInterface.php`

```php
interface AuthInterface
{
    public function getCurrentUser(): ?UserContext;

    public function isAuthenticated(): bool;

    public function hasPermission(string $permission): bool;

    public function hasRole(string $role): bool;

    public function getUserId(): ?int;
}
```

#table(
  columns: (auto, auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Method*], [*Parameters*], [*Returns*], [*Notes*],
  [`getCurrentUser`], [--], [`?UserContext`], [`null` when not authenticated.],
  [`isAuthenticated`], [--], [`bool`], [`true` when a registered user session is active.],
  [`hasPermission`], [`string $permission`], [`bool`], [Checks the permission map. `idea.view` returns `true` for unauthenticated users.],
  [`hasRole`], [`string $role`], [`bool`], [Accepts canonical English role names (see below). Case-insensitive.],
  [`getUserId`], [--], [`?int`], [`null` when not authenticated.],
)

=== UserContext Value Object

*Source:* `inno_shared/src/Infrastructure/Contract/Auth/UserContext.php`

```php
final readonly class UserContext
{
    public function __construct(
        public int    $id,
        public string $email,
        public string $name,
        public array  $roles,
    ) {}
}
```

`UserContext` decouples domain code from Concrete CMS's `User` and `UserInfo` classes. It is a plain value object with no behaviour.

=== Permission Map

#table(
  columns: (auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Permission string*], [*Roles that are granted it*],
  [`idea.submit`], [initiator, administrator],
  [`idea.view`], [all (including unauthenticated)],
  [`idea.vote`], [member, developer, initiator, moderator, administrator],
  [`moderation.review`], [moderator, administrator],
  [`space.join`], [developer, initiator, administrator],
  [`space.manage`], [administrator],
)

=== Role Mapping

`hasRole()` accepts canonical English role names. The adapter maps them to Concrete CMS group names:

#table(
  columns: (auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Canonical role*], [*CMS group name*], [*Capabilities*],
  [`member`], [Mitglied], [View public sector, vote on ideas],
  [`developer`], [Entwickler], [Join and collaborate in project spaces],
  [`initiator`], [Initiator], [Submit project ideas],
  [`moderator`], [Moderator], [Approve and reject ideas in moderation queue],
  [`administrator`], [Administrator], [Full system config and CMS admin panel],
)

== SessionInterface

*Namespace:* `Concrete\Package\InnoShared\Infrastructure\Contract\Auth`

*Source:* `inno_shared/src/Infrastructure/Contract/Auth/SessionInterface.php`

```php
interface SessionInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    public function invalidate(): void;
}
```

#table(
  columns: (auto, auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Method*], [*Parameters*], [*Returns*], [*Notes*],
  [`get`], [`string $key`, `mixed $default = null`], [`mixed`], [Returns `$default` when the key is not present in the session.],
  [`set`], [`string $key`, `mixed $value`], [`void`], [Writes a session value.],
  [`remove`], [`string $key`], [`void`], [Removes a single key from the session.],
  [`invalidate`], [--], [`void`], [Destroys the entire session. Use on logout.],
)

== CacheInterface

*Namespace:* `Concrete\Package\InnoShared\Infrastructure\Contract\Cache`

*Source:* `inno_shared/src/Infrastructure/Contract/Cache/CacheInterface.php`

```php
interface CacheInterface extends \Psr\SimpleCache\CacheInterface
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function getOrSet(string $key, int $ttl, callable $callback): mixed;

    public function setTagged(string $key, mixed $value, int $ttl, array $tags): void;

    public function flushTag(string $tag): void;
}
```

`CacheInterface` extends `Psr\SimpleCache\CacheInterface` (PSR-16), which provides:

```php
public function get(string $key, mixed $default = null): mixed;
public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool;
public function delete(string $key): bool;
public function clear(): bool;
public function getMultiple(iterable $keys, mixed $default = null): iterable;
public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool;
public function deleteMultiple(iterable $keys): bool;
public function has(string $key): bool;
```

Project additions:

#table(
  columns: (auto, auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Method*], [*Parameters*], [*Returns*], [*Notes*],
  [`getOrSet`], [`string $key`, `int $ttl`, `callable(): T $callback`], [`T`], [Returns the cached value, or calls `$callback`, stores the result for `$ttl` seconds, and returns it. The callback is invoked only on a cache miss.],
  [`setTagged`], [`string $key`, `mixed $value`, `int $ttl`, `array $tags`], [`void`], [Stores the value and associates it with each tag in `$tags` for grouped invalidation.],
  [`flushTag`], [`string $tag`], [`void`], [Deletes all entries associated with `$tag` and clears the tag index.],
)

=== Cache Key Convention

```
innovationsbox:{package}:{entity}:{id}:{variant}
```

Examples: `innovationsbox:project_idea:idea:abc-123:detail`, `innovationsbox:moderation:review:idea:abc-123`.

=== Tag Convention

```
{entity}:{id}
```

Examples: `project_idea:abc-123` (all cached views of one idea), `project_idea:list` (all list queries).

=== TTL Strategy

#table(
  columns: (auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Data type*], [*TTL*], [*Reason*],
  [Single entity by ID], [1800 s (30 min)], [Changes infrequently; invalidated on write],
  [List / collection queries], [600 s (10 min)], [More volatile; new entries appear],
  [Public page data], [3600 s (1 hour)], [Rarely changes],
  [User / auth context], [no cache], [Must always be fresh],
  [Config values], [3600 s (1 hour)], [Changes only on deploy],
)

== TransactionInterface

*Namespace:* `Concrete\Package\InnoShared\Infrastructure\Contract\Transaction`

*Source:* `inno_shared/src/Infrastructure/Contract/Transaction/TransactionInterface.php`

```php
interface TransactionInterface
{
    public function begin(): void;

    public function commit(): void;

    public function rollback(): void;

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed;

    public function getDepth(): int;
}
```

#table(
  columns: (auto, auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Method*], [*Parameters*], [*Returns*], [*Notes*],
  [`begin`], [--], [`void`], [Starts a transaction. Nested calls use DBAL savepoints.],
  [`commit`], [--], [`void`], [Commits the current transaction.],
  [`rollback`], [--], [`void`], [Rolls back the current transaction.],
  [`transactional`], [`callable(): T $operation`], [`T`], [Calls `begin()`, runs the operation, then `commit()` on success or `rollback()` on any `\Throwable` before rethrowing. The primary method for handlers.],
  [`getDepth`], [--], [`int`], [Current nesting level. `0` means no transaction is active.],
)

== MetricsInterface

*Namespace:* `Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring`

*Source:* `inno_shared/src/Infrastructure/Contract/Monitoring/MetricsInterface.php`

```php
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
```

#table(
  columns: (auto, auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Method*], [*Parameters*], [*Returns*], [*Notes*],
  [`increment`], [`string $counter`, `int $value = 1`, `array $tags = []`], [`void`], [Increments a named counter. `$tags` are key/value pairs for filtering in log aggregation.],
  [`gauge`], [`string $name`, `float $value`, `array $tags = []`], [`void`], [Records a point-in-time float value (e.g. queue depth, memory usage).],
  [`timing`], [`string $name`, `float $milliseconds`, `array $tags = []`], [`void`], [Records an elapsed time in milliseconds.],
  [`measure`], [`string $name`, `callable(): T $operation`, `array $tags = []`], [`T`], [Wraps a callable, measures its execution time with nanosecond precision, calls `timing()`, and returns the callable's result.],
  [`event`], [`string $name`, `array $context = []`], [`void`], [Records a named business event with structured context.],
)

=== Instrumented Metrics

#table(
  columns: (auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Metric name*], [*Type*], [*Where recorded*],
  [`handler.submit_project_idea`], [timing], [`SubmitProjectIdeaHandler`],
  [`handler.moderation_decision`], [timing], [`ReviewProjectIdeaHandler`],
  [`idea.submitted`], [counter], [`SubmitProjectIdeaHandler`],
  [`idea.approved`], [counter], [`OnModerationDecisionMade`],
  [`idea.rejected`], [counter], [`OnModerationDecisionMade`],
  [`auth.failure`], [counter], [`ConcreteCmsAuth` / controllers],
  [`email.sent`], [counter], [`ConcreteEmailService`],
  [`email.failed`], [counter], [`ConcreteEmailService`],
  [`cache.hit`], [counter], [Cache adapter],
  [`cache.miss`], [counter], [Cache adapter],
  [`moderation.queue_depth`], [gauge], [Monitoring / health routes],
)

== HealthCheckInterface

*Namespace:* `Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring`

*Source:* `inno_shared/src/Infrastructure/Contract/Monitoring/HealthCheckInterface.php`

```php
interface HealthCheckInterface
{
    /**
     * @return array<string, HealthStatus>
     */
    public function check(): array;
}
```

Returns an associative array mapping service names (e.g. `database`, `cms`) to `HealthStatus` enum values.

=== HealthStatus Enum

*Source:* `inno_shared/src/Infrastructure/Contract/Monitoring/HealthStatus.php`

```php
enum HealthStatus: string
{
    case Healthy   = 'healthy';
    case Degraded  = 'degraded';
    case Unhealthy = 'unhealthy';
}
```

The `/api/health` endpoint (registered by `inno_shared`) returns HTTP 200 when all services are `Healthy` and HTTP 503 when any service is `Degraded` or `Unhealthy`.

// --------------------------------------------------------------------------
// 4. Adapter Mapping
// --------------------------------------------------------------------------

= Adapter Mapping

#table(
  columns: (auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Interface*], [*Adapter*], [*Concrete CMS class(es) wrapped*],
  [`ConfigInterface`], [`ConcreteCmsConfig`], [`Concrete\Core\Config\Repository\Repository`],
  [`LoggerInterface`], [`ConcreteCmsLogger`], [`Concrete\Core\Logging\Logger` (Monolog-backed via `LoggerFactory`)],
  [`ExceptionHandlerInterface`], [`ConcreteCmsExceptionHandler`], [Registers via `Events::addListener('on_before_render', ...)`; wraps `ConfigInterface` and `LoggerInterface`],
  [`AuthInterface`], [`ConcreteCmsAuth`], [`Concrete\Core\User\User`],
  [`SessionInterface`], [`ConcreteCmsSession`], [`Symfony\Component\HttpFoundation\Session\Session`],
  [`CacheInterface`], [`ConcreteCmsCache`], [`Stash\Interfaces\PoolInterface` (accessed via `app('cache')->pool`)],
  [`CacheInterface`], [`NullCache`], [No-op; used in tests and dev],
  [`TransactionInterface`], [`ConcreteCmsTransaction`], [`Concrete\Core\Database\Connection\Connection` (Doctrine DBAL)],
  [`MetricsInterface`], [`LogBasedMetrics`], [`LoggerInterface` (all metrics written as structured log records)],
  [`HealthCheckInterface`], [`ConcreteCmsHealthCheck`], [`Connection` (SQL probe), `Concrete\Core\Config\Repository\Repository` (CMS version check)],
)

// --------------------------------------------------------------------------
// 5. Decision Log
// --------------------------------------------------------------------------

= Decision Log

== Why Interfaces Over Direct CMS Calls

Concrete CMS classes are concrete, not easily mockable, and their APIs change between major versions. By routing all CMS access through interfaces, domain and application code is:

- Testable without bootstrapping the CMS (unit tests mock the interfaces with Mockery).
- Portable to a different CMS or framework without touching domain logic.
- Readable: handler constructors declare their dependencies explicitly; the CMS is never imported in domain files.

The cost is one additional abstraction layer per concern. This is accepted because the benefit (testability, portability, clarity) outweighs the indirection.

== Why PSR Extensions

PSR-3 (`LoggerInterface`) and PSR-16 (`CacheInterface`) are stable, widely adopted contracts with existing mock implementations and test utilities. Extending rather than replacing them means:

- Standard tooling (e.g. `psr/log` test helpers, `cache/integration-tests`) works without adaptation.
- Future adapters for other frameworks only need to implement the PSR methods plus the project extensions (`withContext`, `getOrSet`, `setTagged`, `flushTag`).
- Packages that only need basic logging can type-hint `Psr\Log\LoggerInterface` directly if they choose not to use `withContext`.

== Why Events Are Dispatched Inside Transactions

Synchronous event listeners can write to the database (e.g. `OnProjectIdeaApproved` creates a project space). If the event is dispatched after the commit, and the listener fails, the aggregate is already persisted in an inconsistent state with no recovery path.

Dispatching inside the transaction means a listener failure triggers a full rollback, leaving the system consistent. The trade-off is that listeners must be fast and reliable. Slow or unreliable work (email, external API calls) must be queued and processed asynchronously; the email adapter in `inno_notification` is the designated boundary for this pattern.

== Why Log-Based Metrics Initially

Adding a dedicated metrics backend (StatsD, Prometheus, Datadog) requires infrastructure setup and operational overhead. For the initial production deployment, structured log records are sufficient: log aggregation tools (Loki, ELK, Grafana) can parse `metric.counter`, `metric.timing`, and `metric.event` records from the log stream.

`MetricsInterface` ensures that switching to a dedicated backend later requires only writing a new adapter and updating a single DI binding. No call sites change.

// --------------------------------------------------------------------------
// 6. Security Policy
// --------------------------------------------------------------------------

= Security Policy

== Authentication Flow

```
HTTP Request
  -> Concrete CMS session middleware
  -> Session validated; User object resolved
  -> ConcreteCmsAuth::isAuthenticated() returns true/false
  -> Controller calls AuthInterface::hasPermission($permission)
     -> hasPermission returns true  -> handler executes
     -> hasPermission returns false -> AuthException thrown
        -> ExceptionHandlerInterface::toJsonResponse()
           -> 401 (not authenticated) or 403 (no permission)
```

The CMS handles session lifecycle, cookie security, and session storage. `AuthInterface` is read-only from the perspective of domain code -- it never creates or destroys sessions. Session management (logout, invalidation) is the responsibility of the CMS authentication routes.

== Authorisation Model

Authorisation is permission-based. Roles are a grouping mechanism that grants sets of permissions. The mapping from permission strings to role sets is defined in `ConcreteCmsAuth::PERMISSION_MAP`. Adding a new permission requires: adding an entry to the map, adding a `hasPermission()` call in the relevant controller, and updating this specification.

Domain code must never implement its own role checks. All authorisation goes through `AuthInterface::hasPermission()`.

== Data Protection

*Logging:* `SensitiveDataProcessor` scrubs context keys matching sensitive patterns before any log record is written. Context keys that contain user data (email, name, ID) are acceptable; credentials, tokens, and payment data must never appear in log context.

*Configuration:* database credentials, SMTP passwords, API keys, and any secret must live in `.env` only. `.env` is listed in `.gitignore`. `detect-secrets` runs as a pre-commit hook and in CI Stage 4 to prevent accidental commits of credential material.

*API responses:* `ExceptionHandlerInterface` returns only `getUserMessage()` in production responses. Internal exception messages, class names, and stack traces are never included unless `app.debug = true`, which must never be set in production.

*SQL injection:* all database access uses parameterised queries via Doctrine DBAL `Connection`. String concatenation into SQL is forbidden.

*Output escaping:* REST API responses are `JsonResponse` objects; PHP's `json_encode` handles escaping. CMS template output uses the `h()` helper.

*CSRF:* Concrete CMS token validation applies to all state-changing form submissions. REST API endpoints use token-based authentication where required.

*Secure headers:* CSP, `X-Frame-Options`, `X-Content-Type-Options`, and HSTS are configured at the web server level (`.htaccess` / nginx config), not in application code.

== Logging Policy for Sensitive Data

#table(
  columns: (auto, auto, 1fr),
  stroke: 0.5pt,
  inset: 6pt,
  [*Data type*], [*May appear in logs*], [*Notes*],
  [User ID (integer)], [yes], [Safe identifier],
  [Username], [yes], [Non-sensitive display name],
  [Email address], [yes, at `info` level], [Acceptable for audit trail],
  [IP address], [yes, at `warning`/`error`], [Acceptable for security events],
  [Password], [never], [Scrubbed by `SensitiveDataProcessor`],
  [Session token], [never], [Scrubbed by `SensitiveDataProcessor`],
  [API key / secret], [never], [Scrubbed by `SensitiveDataProcessor`],
  [Payment data], [never], [Never collected; not in scope],
  [Stack traces], [yes, at `critical`], [Internal channels only; not in API responses],
)
