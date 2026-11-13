# Cross-Cutting Concerns — Developer Guide

This guide shows how to use the shared infrastructure concerns in application code. All concerns are abstracted behind interfaces that live in `inno_shared`. You never call Concrete CMS APIs directly from domain or application code.

---

## Getting Started

All interfaces are registered in `inno_shared/controller.php` inside `on_start()`. The CMS dependency container resolves each interface to its adapter automatically. You do not need to instantiate adapters or call `app()->make()` in your own code — just type-hint the interface in your constructor and the container injects it.

```php
use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;

class SubmitProjectIdeaHandler
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly LoggerInterface $logger,
        private readonly TransactionInterface $transaction,
    ) {}
}
```

The binding lifecycle per concern:

| Interface | Lifecycle | Reason |
|---|---|---|
| `ConfigInterface` | singleton | Config state is global |
| `LoggerInterface` | singleton | Logger is stateless; `withContext()` creates child instances |
| `ExceptionHandlerInterface` | singleton | Stateless handler |
| `AuthInterface` | per-request | Wraps the per-request `User` object |
| `SessionInterface` | per-request | Wraps the per-request `Session` object |
| `CacheInterface` | singleton | Pool is shared |
| `TransactionInterface` | per-request | Wraps the per-request DB connection |
| `MetricsInterface` | singleton | LogBasedMetrics is stateless |
| `HealthCheckInterface` | singleton | Stateless |

---

## Using Configuration

Inject `ConfigInterface`. All keys are automatically namespaced under `innovationsbox.` — you never include the prefix in your call.

```php
use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\InfrastructureException;

class SendAdminNotificationHandler
{
    public function __construct(private readonly ConfigInterface $config) {}

    public function handle(): void
    {
        // getRequired() throws InfrastructureException if the key is absent.
        // Use this for values that must be set before the system can operate.
        $adminEmail = $this->config->getRequired('notification.admin_email');

        // get() returns the second argument when the key is missing or null.
        // Use this for optional settings with a safe fallback.
        $ttl = $this->config->get('cache.default_ttl', 3600);

        // has() checks existence without reading the value.
        if ($this->config->has('notification.from_email')) {
            $fromEmail = $this->config->get('notification.from_email');
        }
    }
}
```

**Rule:** never use `\Config::get()`, `$app->make(Repository::class)`, or `$_ENV` directly in domain or application classes. Wrap all settings behind `ConfigInterface`.

**Sensitive values** (passwords, API tokens, SMTP credentials) must live in `.env` only — they must never appear in the CMS database config or in committed files.

---

## Using Logging

Inject `LoggerInterface`. It extends PSR-3, so all standard log-level methods are available. The project-specific addition is `withContext()`, which returns a new logger instance carrying a fixed set of context keys on every subsequent call.

```php
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class ReviewProjectIdeaHandler
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function handle(ReviewCommand $command): void
    {
        // Pin request-scoped identifiers once. The original $this->logger is
        // unchanged; $log is a new instance that merges these keys into every record.
        $log = $this->logger->withContext([
            'idea_id'     => $command->ideaId,
            'moderator'   => $command->moderatorId,
            'decision'    => $command->decision->value,
        ]);

        $log->info('Recording moderation decision.');

        try {
            // ... domain work ...
            $log->info('Moderation decision saved.');
        } catch (\Throwable $e) {
            // Pass only the new context at the call site.
            // The request-scoped context from withContext() is merged automatically.
            $log->error('Failed to save decision.', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

**Log level guide:**

| Level | When to use |
|---|---|
| `debug` | Variable state, SQL queries, intermediate values — development only |
| `info` | Successful use-case completion: idea submitted, decision recorded |
| `warning` | Handled domain errors: validation failure, not-found responses, auth rejection |
| `error` | Caught infrastructure failures: email delivery, external API timeout |
| `critical` | Unrecoverable: DB connection lost, required config missing, uncaught exception |

**Sensitive data** is scrubbed automatically by `SensitiveDataProcessor`. Any context key whose name contains `password`, `token`, `secret`, `authorization`, `cookie`, or `credit_card` (case-insensitive) is replaced with `***REDACTED***` before the record is written. You do not need to redact these manually, but do not rely on this as the primary protection — keep secrets out of context keys entirely where possible.

---

## Using Error Handling

Domain exceptions propagate up from handlers through controllers to the global exception handler, which converts them to JSON responses for all `/api/*` routes. Controllers do not need try/catch blocks.

**Throwing domain exceptions in application code:**

```php
use Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Domain\Exception\InvalidProjectIdeaException;

// In a repository or handler:
$idea = $this->repository->findById($id);
if ($idea === null) {
    throw ProjectIdeaNotFoundException::withId($id);
    // -> global handler catches this -> 404 JSON response automatically
}

// In a domain entity or handler, for validation:
if (trim($command->title) === '') {
    throw InvalidProjectIdeaException::emptyTitle();
    // -> 400 JSON response automatically
}
```

**What the global handler does:**

- `BaseException` subclass — logs at `warning`, returns JSON with `getUserMessage()` and `getHttpStatusCode()`.
- Any other `\Throwable` — logs at `critical` with full stack trace, returns generic 500 JSON.
- In development (`app.debug = true`), the response body includes `debug.exception`, `debug.detail`, and `debug.trace`.
- In production, none of those fields appear.

**Creating a new domain exception:**

Extend the appropriate base class from `inno_shared`. Provide a named constructor for every distinct error case so call sites are self-documenting.

```php
namespace Concrete\Package\InnoModeration\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InvalidModerationDecisionException extends ValidationException
{
    public static function unknownDecision(string $value): self
    {
        return new self(
            "'{$value}' is not a valid moderation decision.",
            ['provided_value' => $value],
        );
    }
}
```

The four base exception classes and their HTTP status codes:

| Class | HTTP status | When to use |
|---|---|---|
| `ValidationException` | 400 | Invalid input, missing required fields, constraint violations |
| `NotFoundException` | 404 | Entity does not exist |
| `AuthException` | 401 or 403 | Not authenticated (401) or authenticated but not permitted (403) |
| `InfrastructureException` | 500 | DB failure, cache failure, external service unreachable |

`AuthException` resolves to 401 when constructed with `authenticated: false`, and 403 otherwise.

---

## Using Auth

Inject `AuthInterface` in controllers. Check permissions before calling use cases. Do not check permissions inside handlers — handlers assume the controller has already gate-kept the request.

```php
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

class ProjectIdeaApiController extends AbstractController
{
    public function __construct(
        private readonly AuthInterface $auth,
        private readonly SubmitProjectIdeaPort $submitPort,
    ) {}

    public function store(Request $request): JsonResponse
    {
        if (!$this->auth->hasPermission('idea.submit')) {
            throw new AuthException(
                'Permission denied for idea.submit',
                ['permission' => 'idea.submit'],
                authenticated: $this->auth->isAuthenticated(),
            );
            // isAuthenticated() = false -> 401; true -> 403
        }

        $userId = $this->auth->getUserId(); // int|null
        $user   = $this->auth->getCurrentUser(); // UserContext|null

        // $user->id, $user->email, $user->name, $user->roles are available
    }
}
```

**Permission map** (defined in `ConcreteCmsAuth`):

| Permission | Granted to |
|---|---|
| `idea.submit` | initiator, administrator |
| `idea.view` | all (including unauthenticated) |
| `idea.vote` | member, developer, initiator, moderator, administrator |
| `moderation.review` | moderator, administrator |
| `space.join` | developer, initiator, administrator |
| `space.manage` | administrator |

`hasRole()` accepts the canonical English role names: `member`, `developer`, `initiator`, `moderator`, `administrator`. The adapter maps them to the German CMS group names (`Mitglied`, `Entwickler`, etc.) internally.

**Session** — inject `SessionInterface` for session-backed state. Prefer `AuthInterface` for identity checks; use `SessionInterface` only for transient, per-request data (e.g. flash messages, multi-step form state).

```php
$this->session->set('wizard.step', 2);
$step = $this->session->get('wizard.step', 1);
$this->session->remove('wizard.step');
$this->session->invalidate(); // destroys the session (use on logout)
```

---

## Using Cache

Inject `CacheInterface` into repositories. Use `getOrSet()` for reads and `setTagged()` + `flushTag()` for writes.

**Repository read with getOrSet():**

```php
use Concrete\Package\InnoShared\Infrastructure\Contract\Cache\CacheInterface;

class CachedProjectIdeaRepository implements ProjectIdeaRepositoryInterface
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $inner,
        private readonly CacheInterface $cache,
    ) {}

    public function findById(string $id): ?ProjectIdea
    {
        // Returns the cached value, or calls the callback, stores the result for
        // 1800 seconds, and returns it. The callback is only invoked on a miss.
        return $this->cache->getOrSet(
            "innovationsbox:project_idea:idea:{$id}:detail",
            1800,
            fn () => $this->inner->findById($id),
        );
    }

    public function findAll(): array
    {
        return $this->cache->getOrSet(
            'innovationsbox:project_idea:ideas:list',
            600,
            fn () => $this->inner->findAll(),
        );
    }

    public function save(ProjectIdea $idea): void
    {
        $this->inner->save($idea);

        // Invalidate all cache entries associated with these tags.
        $this->cache->flushTag("project_idea:{$idea->getId()}");
        $this->cache->flushTag('project_idea:list');
    }
}
```

**Tag-based invalidation in an event handler:**

```php
use Concrete\Package\InnoShared\Infrastructure\Contract\Cache\CacheInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;

class OnProjectIdeaApprovedCacheInvalidator
{
    public function __construct(private readonly CacheInterface $cache) {}

    public function handle(ProjectIdeaApproved $event): void
    {
        $id = $event->getIdeaId();
        $this->cache->flushTag("project_idea:{$id}");
        $this->cache->flushTag('project_idea:list');
        $this->cache->flushTag('moderation:reviews');
    }
}
```

**Key convention:** `innovationsbox:{package}:{entity}:{id}:{variant}`

**TTL guide:**

| Data type | TTL |
|---|---|
| Single entity by ID | 1800 s (30 min) |
| List/collection queries | 600 s (10 min) |
| Public page data | 3600 s (1 hour) |
| User/auth context | no cache |

In tests, the DI container binds `NullCache` instead of `ConcreteCmsCache`. You do not need to change any code — just override the binding in the test bootstrap.

---

## Using Transactions

Inject `TransactionInterface` into handlers. Wrap every operation that involves more than one write, or a write followed by an event dispatch, inside `transactional()`.

```php
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;

class SubmitProjectIdeaHandler implements SubmitProjectIdeaPort
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(SubmitProjectIdeaCommand $command): ProjectIdea
    {
        return $this->transaction->transactional(function () use ($command) {
            $idea = ProjectIdea::submit(
                id: uniqid('idea_', true),
                title: $command->title,
                description: $command->description,
                category: $command->category,
                submitterId: $command->submitterId,
            );

            $this->repository->save($idea);

            // The event is dispatched inside the transaction.
            // If a synchronous listener throws, the save is rolled back.
            $this->eventDispatcher->dispatch(
                new ProjectIdeaSubmitted($idea->getId(), $idea->getSubmitterId(), $idea->getTitle()),
                ProjectIdeaSubmitted::NAME,
            );

            $this->logger->info('Project idea submitted', [
                'ideaId'      => $idea->getId(),
                'submitterId' => $idea->getSubmitterId(),
            ]);

            return $idea;
        });
    }
}
```

`transactional()` calls `begin()`, runs the callback, then either `commit()` on success or `rollback()` on any `\Throwable` before rethrowing. You do not write try/catch/finally — the method handles it.

**Event listeners must be fast.** Because events are dispatched inside the transaction, a slow or failing listener causes the whole transaction to roll back. Work that is slow or unreliable (sending email, calling external APIs) must be queued and processed asynchronously — it must not run synchronously inside the listener.

`getDepth()` returns the current nesting level (`0` = no active transaction). Use it in assertions during tests or for diagnostic logging on rollback.

---

## Using Metrics

Inject `MetricsInterface`. The current adapter (`LogBasedMetrics`) writes all metrics to the structured log. When an APM tool is introduced, a new adapter implementing the same interface can be swapped in without changing any call site.

```php
use Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring\MetricsInterface;

class SubmitProjectIdeaHandler implements SubmitProjectIdeaPort
{
    public function __construct(
        private readonly MetricsInterface $metrics,
        // ... other dependencies
    ) {}

    public function handle(SubmitProjectIdeaCommand $command): ProjectIdea
    {
        // Wrap the entire operation — measure() records the elapsed time automatically.
        return $this->metrics->measure(
            'handler.submit_project_idea',
            function () use ($command) {
                $idea = /* ... */;

                // Increment a named counter. Tags are key/value pairs for filtering.
                $this->metrics->increment('idea.submitted', 1, ['category' => $command->category]);

                return $idea;
            },
        );
    }
}

// In an event handler, record a business event:
$this->metrics->event('moderation.decision_recorded', [
    'decision' => $decision->value,
    'idea_id'  => $ideaId,
]);

// Record a gauge (point-in-time value):
$this->metrics->gauge('moderation.queue_depth', (float) $this->repository->countPending());

// Record a timing value explicitly (milliseconds):
$this->metrics->timing('db.query', 42.7, ['query' => 'findById']);
```

---

## Adding a New Cross-Cutting Concern

Follow these steps to introduce a new shared concern (e.g. a queue, a feature-flag service, a mailer abstraction):

1. **Define the interface** in `public/packages/inno_shared/src/Infrastructure/Contract/{Concern}/YourInterface.php`. Extend a PSR standard if one exists. Keep the interface minimal — only what callers actually need.

2. **Write the adapter** in `public/packages/inno_shared/src/Infrastructure/Adapter/{Concern}/ConcreteCmsYour.php`. Implement the interface; inject only CMS classes. Do not let any CMS type leak through the interface boundary.

3. **Register the DI binding** in `public/packages/inno_shared/controller.php` inside `on_start()`:

   ```php
   $this->app->singleton(YourInterface::class, function ($app) {
       return new ConcreteCmsYour($app->make(SomeCmsClass::class));
   });
   ```

   Use `singleton` for stateless or shared-state adapters. Use `bind` (per-request) for adapters that wrap a per-request CMS object (like `User` or `Session`).

4. **Write unit tests** under `tests/unit/inno_shared/`. Mock the CMS class the adapter wraps with Mockery. Test every method and every edge case (missing key, empty value, exception from CMS, etc.).

5. **Inject the interface** by type-hint in any handler, controller, or repository that needs it. The container resolves it automatically — no service-locator calls needed.

---

## Swapping the CMS

The entire point of these abstractions is that Concrete CMS is an implementation detail. If the platform changes:

1. Write a new adapter for each interface in a new `Adapter/` namespace (e.g. `WordPressCmsLogger`, `LaravelConfig`).
2. Update the DI bindings in the new platform's equivalent of `controller.php on_start()` to point each interface at its new adapter.
3. Delete the `inno_shared/Infrastructure/Adapter/` folder that contained the Concrete CMS adapters.

Nothing in `Domain/`, `Application/`, `Port/`, or `Adapter/In/Http/` changes. All use cases, event handlers, repositories, and controllers continue to work without modification because they depend only on the interfaces.
