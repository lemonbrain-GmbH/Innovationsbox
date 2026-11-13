# Cross-Cutting Concerns Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement all 7 cross-cutting concerns (Config, Logging, Error Handling, Security/Auth, Caching, Transactions, Monitoring) as CMS-abstracted interfaces and Concrete CMS adapters in `inno_shared`, then integrate them into all domain packages.

**Architecture:** Every concern lives in `inno_shared/src/Infrastructure/` — interfaces under `Contract/`, adapters under `Adapter/`, shared exceptions under `Exception/`. Domain packages depend only on interfaces. DI bindings registered in `inno_shared/controller.php`. PSR-3 and PSR-16 extended where applicable.

**Tech Stack:** PHP 8.2+, Concrete CMS 9.4, PSR-3 (Logging), PSR-16 (SimpleCache), Symfony EventDispatcher, Doctrine DBAL, Pest PHP, Mockery

**Spec:** `docs/superpowers/specs/2026-04-17-cross-cutting-concerns-design.md`

---

## File Map

### New files in `inno_shared`

```
public/packages/inno_shared/src/
  Domain/
    Event/                              (5 files MOVED from src/Event/)
      ModerationDecisionMade.php
      ProjectIdeaApproved.php
      ProjectIdeaRejected.php
      ProjectIdeaSubmitted.php
      ProjectSpaceCreated.php
  Infrastructure/
    Contract/
      Config/ConfigInterface.php
      Logger/LoggerInterface.php
      Cache/CacheInterface.php
      Auth/AuthInterface.php
      Auth/SessionInterface.php
      Auth/UserContext.php
      Transaction/TransactionInterface.php
      Monitoring/MetricsInterface.php
      Monitoring/HealthCheckInterface.php
      Monitoring/HealthStatus.php
      ErrorHandling/ExceptionHandlerInterface.php
    Exception/
      BaseException.php
      ValidationException.php
      NotFoundException.php
      AuthException.php
      InfrastructureException.php
    Adapter/
      Config/ConcreteCmsConfig.php
      Logger/ConcreteCmsLogger.php
      Logger/SensitiveDataProcessor.php
      Cache/ConcreteCmsCache.php
      Cache/NullCache.php
      Auth/ConcreteCmsAuth.php
      Auth/ConcreteCmsSession.php
      Transaction/ConcreteCmsTransaction.php
      Monitoring/LogBasedMetrics.php
      Monitoring/ConcreteCmsHealthCheck.php
      ErrorHandling/ConcreteCmsExceptionHandler.php
```

### New test files

```
tests/unit/inno_shared/
  Infrastructure/
    Exception/BaseExceptionTest.php
    Adapter/
      Config/ConcreteCmsConfigTest.php
      Logger/ConcreteCmsLoggerTest.php
      Logger/SensitiveDataProcessorTest.php
      Cache/ConcreteCmsCacheTest.php
      Cache/NullCacheTest.php
      Auth/ConcreteCmsAuthTest.php
      Transaction/ConcreteCmsTransactionTest.php
      Monitoring/LogBasedMetricsTest.php
      Monitoring/ConcreteCmsHealthCheckTest.php
      ErrorHandling/ConcreteCmsExceptionHandlerTest.php
```

### New domain exception files

```
public/packages/inno_project_idea/src/Domain/Exception/
  ProjectIdeaNotFoundException.php
  InvalidProjectIdeaException.php

public/packages/inno_moderation/src/Domain/Exception/
  ModerationReviewNotFoundException.php
  InvalidModerationDecisionException.php

public/packages/inno_project_space/src/Domain/Exception/
  ProjectSpaceNotFoundException.php
```

### Modified files

```
public/packages/inno_shared/composer.json              (add psr/log, psr/simple-cache requires)
public/packages/inno_shared/controller.php             (add all DI bindings + boot validation + health route)
composer.json                                          (add psr/log, psr/simple-cache to root requires)

public/packages/inno_project_idea/controller.php       (update event namespace)
public/packages/inno_moderation/controller.php         (update event namespace)
public/packages/inno_notification/controller.php       (update event namespace)
public/packages/inno_project_space/controller.php      (update event namespace)

All handlers:                                          (add Logger, Transaction injection)
All controllers:                                       (add Auth injection, remove manual error handling)
All event handlers:                                    (add Logger injection)

All test files referencing Event namespace              (update imports)
```

---

## Task 1: Namespace Restructure — Move Events to `Domain/Event/`

**Files:**
- Move: `public/packages/inno_shared/src/Event/*.php` to `public/packages/inno_shared/src/Domain/Event/*.php`
- Modify: all 5 event files (namespace change)
- Modify: 10+ files across all packages (import updates)

- [ ] **Step 1: Create the target directory**

```bash
mkdir -p public/packages/inno_shared/src/Domain/Event
```

- [ ] **Step 2: Move and update `ProjectIdeaSubmitted.php`**

Move `public/packages/inno_shared/src/Event/ProjectIdeaSubmitted.php` to `public/packages/inno_shared/src/Domain/Event/ProjectIdeaSubmitted.php`.

Change the namespace from:

```php
namespace Concrete\Package\InnoShared\Event;
```

to:

```php
namespace Concrete\Package\InnoShared\Domain\Event;
```

- [ ] **Step 3: Move and update `ProjectIdeaApproved.php`**

Move `public/packages/inno_shared/src/Event/ProjectIdeaApproved.php` to `public/packages/inno_shared/src/Domain/Event/ProjectIdeaApproved.php`.

Change the namespace from:

```php
namespace Concrete\Package\InnoShared\Event;
```

to:

```php
namespace Concrete\Package\InnoShared\Domain\Event;
```

- [ ] **Step 4: Move and update `ProjectIdeaRejected.php`**

Move `public/packages/inno_shared/src/Event/ProjectIdeaRejected.php` to `public/packages/inno_shared/src/Domain/Event/ProjectIdeaRejected.php`.

Change the namespace from:

```php
namespace Concrete\Package\InnoShared\Event;
```

to:

```php
namespace Concrete\Package\InnoShared\Domain\Event;
```

- [ ] **Step 5: Move and update `ModerationDecisionMade.php`**

Move `public/packages/inno_shared/src/Event/ModerationDecisionMade.php` to `public/packages/inno_shared/src/Domain/Event/ModerationDecisionMade.php`.

Change the namespace from:

```php
namespace Concrete\Package\InnoShared\Event;
```

to:

```php
namespace Concrete\Package\InnoShared\Domain\Event;
```

- [ ] **Step 6: Move and update `ProjectSpaceCreated.php`**

Move `public/packages/inno_shared/src/Event/ProjectSpaceCreated.php` to `public/packages/inno_shared/src/Domain/Event/ProjectSpaceCreated.php`.

Change the namespace from:

```php
namespace Concrete\Package\InnoShared\Event;
```

to:

```php
namespace Concrete\Package\InnoShared\Domain\Event;
```

- [ ] **Step 7: Delete old directory**

```bash
rmdir public/packages/inno_shared/src/Event
```

- [ ] **Step 8: Update all imports across the codebase**

Replace `use Concrete\Package\InnoShared\Event\` with `use Concrete\Package\InnoShared\Domain\Event\` in every file. Here is the complete list:

**`public/packages/inno_project_idea/src/Application/UseCase/SubmitProjectIdea/SubmitProjectIdeaHandler.php`** (line 10):
```php
// Old:
use Concrete\Package\InnoShared\Event\ProjectIdeaSubmitted;
// New:
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
```

**`public/packages/inno_project_idea/src/Application/EventHandler/OnModerationDecisionMade.php`** (lines 8-10):
```php
// Old:
use Concrete\Package\InnoShared\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Event\ProjectIdeaRejected;
// New:
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaRejected;
```

**`public/packages/inno_moderation/src/Application/UseCase/ReviewProjectIdea/ReviewProjectIdeaHandler.php`** (line 9):
```php
// Old:
use Concrete\Package\InnoShared\Event\ModerationDecisionMade;
// New:
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
```

**`public/packages/inno_moderation/src/Application/EventHandler/OnProjectIdeaSubmitted.php`** (line 9):
```php
// Old:
use Concrete\Package\InnoShared\Event\ProjectIdeaSubmitted;
// New:
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
```

**`public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaSubmitted.php`** (line 8):
```php
// Old:
use Concrete\Package\InnoShared\Event\ProjectIdeaSubmitted;
// New:
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
```

**`public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaApproved.php`** (line 8):
```php
// Old:
use Concrete\Package\InnoShared\Event\ProjectIdeaApproved;
// New:
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
```

**`public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaRejected.php`** (line 8):
```php
// Old:
use Concrete\Package\InnoShared\Event\ProjectIdeaRejected;
// New:
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaRejected;
```

**`public/packages/inno_project_space/src/Application/EventHandler/OnProjectIdeaApproved.php`** (lines 9-10):
```php
// Old:
use Concrete\Package\InnoShared\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Event\ProjectSpaceCreated;
// New:
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCreated;
```

**Test files** — update the same imports in:
- `tests/unit/inno_project_idea/Application/SubmitProjectIdeaHandlerTest.php` (line 8)
- `tests/unit/inno_moderation/Application/ReviewProjectIdeaHandlerTest.php` (line 9)
- `tests/unit/inno_notification/Application/OnProjectIdeaSubmittedTest.php` (line 8)
- Any other test files importing events — check `tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php`

- [ ] **Step 9: Run tests to verify nothing broke**

```bash
composer test:unit
```

Expected: all existing tests pass (they should — only namespace changed, autoload resolves the new path).

- [ ] **Step 10: Commit**

```bash
git add -A public/packages/inno_shared/src/ tests/unit/ \
  public/packages/inno_project_idea/src/ \
  public/packages/inno_moderation/src/ \
  public/packages/inno_notification/src/ \
  public/packages/inno_project_space/src/
git commit -m "refactor: move shared events to Domain/Event/ namespace"
```

---

## Task 2: Add PSR Dependencies

**Files:**
- Modify: `composer.json` (root)
- Modify: `public/packages/inno_shared/composer.json`

- [ ] **Step 1: Add PSR packages to root composer.json**

Add to the `"require"` section of `composer.json`:

```json
"psr/log": "^3.0",
"psr/simple-cache": "^3.0"
```

Note: PSR-3 and PSR-16 may already be installed as transitive dependencies of `concrete5/core`. Adding them explicitly ensures they are available.

- [ ] **Step 2: Add PSR packages to inno_shared composer.json**

Update `public/packages/inno_shared/composer.json` `"require"` to:

```json
"require": {
    "concrete5/core": "^9.4",
    "psr/log": "^3.0",
    "psr/simple-cache": "^3.0"
}
```

- [ ] **Step 3: Install**

```bash
composer update psr/log psr/simple-cache --no-interaction
```

Expected: packages install (or already satisfied). No errors.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock public/packages/inno_shared/composer.json
git commit -m "build: add psr/log and psr/simple-cache dependencies"
```

---

## Task 3: Exception Hierarchy

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/Exception/BaseException.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Exception/ValidationException.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Exception/NotFoundException.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Exception/AuthException.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Exception/InfrastructureException.php`
- Test: `tests/unit/inno_shared/Infrastructure/Exception/BaseExceptionTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/unit/inno_shared/Infrastructure/Exception/BaseExceptionTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Concrete\Package\InnoShared\Infrastructure\Exception\InfrastructureException;
use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;
use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

test('ValidationException has status 400 and safe user message', function () {
    $e = new ValidationException('Title is empty', ['field' => 'title']);
    expect($e->getHttpStatusCode())->toBe(400)
        ->and($e->getUserMessage())->toBe('The provided data is invalid.')
        ->and($e->getContext())->toBe(['field' => 'title'])
        ->and($e->getMessage())->toBe('Title is empty');
});

test('NotFoundException has status 404', function () {
    $e = new NotFoundException('Idea xyz not found', ['id' => 'xyz']);
    expect($e->getHttpStatusCode())->toBe(404)
        ->and($e->getUserMessage())->toBe('The requested resource was not found.')
        ->and($e->getContext())->toBe(['id' => 'xyz']);
});

test('AuthException defaults to 403 when authenticated', function () {
    $e = new AuthException('Not allowed', ['permission' => 'idea.submit']);
    expect($e->getHttpStatusCode())->toBe(403)
        ->and($e->getUserMessage())->toBe('You do not have permission to perform this action.');
});

test('AuthException returns 401 when not authenticated', function () {
    $e = new AuthException('Login required', authenticated: false);
    expect($e->getHttpStatusCode())->toBe(401);
});

test('InfrastructureException has status 500', function () {
    $e = new InfrastructureException('DB connection failed');
    expect($e->getHttpStatusCode())->toBe(500)
        ->and($e->getUserMessage())->toBe('An internal error occurred. Please try again later.');
});

test('exceptions carry previous throwable', function () {
    $prev = new \RuntimeException('root cause');
    $e = new InfrastructureException('Wrapper', previous: $prev);
    expect($e->getPrevious())->toBe($prev);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
composer test:unit -- --filter=BaseExceptionTest
```

Expected: FAIL — classes do not exist yet.

- [ ] **Step 3: Create `BaseException.php`**

Create `public/packages/inno_shared/src/Infrastructure/Exception/BaseException.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Exception;

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

    public function getContext(): array
    {
        return $this->context;
    }

    abstract public function getHttpStatusCode(): int;

    abstract public function getUserMessage(): string;
}
```

- [ ] **Step 4: Create `ValidationException.php`**

Create `public/packages/inno_shared/src/Infrastructure/Exception/ValidationException.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Exception;

class ValidationException extends BaseException
{
    public function getHttpStatusCode(): int
    {
        return 400;
    }

    public function getUserMessage(): string
    {
        return 'The provided data is invalid.';
    }
}
```

- [ ] **Step 5: Create `NotFoundException.php`**

Create `public/packages/inno_shared/src/Infrastructure/Exception/NotFoundException.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Exception;

class NotFoundException extends BaseException
{
    public function getHttpStatusCode(): int
    {
        return 404;
    }

    public function getUserMessage(): string
    {
        return 'The requested resource was not found.';
    }
}
```

- [ ] **Step 6: Create `AuthException.php`**

Create `public/packages/inno_shared/src/Infrastructure/Exception/AuthException.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Exception;

class AuthException extends BaseException
{
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

    public function getHttpStatusCode(): int
    {
        return $this->httpStatus;
    }

    public function getUserMessage(): string
    {
        return 'You do not have permission to perform this action.';
    }
}
```

- [ ] **Step 7: Create `InfrastructureException.php`**

Create `public/packages/inno_shared/src/Infrastructure/Exception/InfrastructureException.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Exception;

class InfrastructureException extends BaseException
{
    public function getHttpStatusCode(): int
    {
        return 500;
    }

    public function getUserMessage(): string
    {
        return 'An internal error occurred. Please try again later.';
    }
}
```

- [ ] **Step 8: Run tests to verify they pass**

```bash
composer test:unit -- --filter=BaseExceptionTest
```

Expected: all 6 tests pass.

- [ ] **Step 9: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Exception/ \
  tests/unit/inno_shared/Infrastructure/Exception/
git commit -m "feat(shared): add exception hierarchy — Base, Validation, NotFound, Auth, Infrastructure"
```

---

## Task 4: ConfigInterface & ConcreteCmsConfig Adapter

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/Contract/Config/ConfigInterface.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Config/ConcreteCmsConfig.php`
- Test: `tests/unit/inno_shared/Infrastructure/Adapter/Config/ConcreteCmsConfigTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/unit/inno_shared/Infrastructure/Adapter/Config/ConcreteCmsConfigTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Core\Config\Repository\Repository;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Config\ConcreteCmsConfig;
use Concrete\Package\InnoShared\Infrastructure\Exception\InfrastructureException;

afterEach(fn () => Mockery::close());

test('get() delegates to CMS config with prefix', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('get')
        ->with('innovationsbox.notification.admin_email', null)
        ->andReturn('admin@example.com');

    $config = new ConcreteCmsConfig($repo);
    expect($config->get('notification.admin_email'))->toBe('admin@example.com');
});

test('get() returns default when key missing', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('get')
        ->with('innovationsbox.missing.key', 'fallback')
        ->andReturn('fallback');

    $config = new ConcreteCmsConfig($repo);
    expect($config->get('missing.key', 'fallback'))->toBe('fallback');
});

test('has() checks prefixed key', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('has')
        ->with('innovationsbox.logging.level')
        ->andReturn(true);

    $config = new ConcreteCmsConfig($repo);
    expect($config->has('logging.level'))->toBeTrue();
});

test('getRequired() throws InfrastructureException when key missing', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('has')
        ->with('innovationsbox.notification.admin_email')
        ->andReturn(false);

    $config = new ConcreteCmsConfig($repo);
    $config->getRequired('notification.admin_email');
})->throws(InfrastructureException::class, "Required config key 'notification.admin_email' is missing.");

test('getRequired() returns value when key exists', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('has')
        ->with('innovationsbox.cache.default_ttl')
        ->andReturn(true);
    $repo->shouldReceive('get')
        ->with('innovationsbox.cache.default_ttl', null)
        ->andReturn(3600);

    $config = new ConcreteCmsConfig($repo);
    expect($config->getRequired('cache.default_ttl'))->toBe(3600);
});

test('set() delegates to CMS config with prefix', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('set')
        ->once()
        ->with('innovationsbox.logging.level', 'debug');

    $config = new ConcreteCmsConfig($repo);
    $config->set('logging.level', 'debug');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
composer test:unit -- --filter=ConcreteCmsConfigTest
```

Expected: FAIL — classes do not exist.

- [ ] **Step 3: Create `ConfigInterface.php`**

Create `public/packages/inno_shared/src/Infrastructure/Contract/Config/ConfigInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Config;

use Concrete\Package\InnoShared\Infrastructure\Exception\InfrastructureException;

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

- [ ] **Step 4: Create `ConcreteCmsConfig.php`**

Create `public/packages/inno_shared/src/Infrastructure/Adapter/Config/ConcreteCmsConfig.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Config;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\InfrastructureException;

class ConcreteCmsConfig implements ConfigInterface
{
    private const PREFIX = 'innovationsbox.';

    public function __construct(private readonly Repository $repository) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->repository->get(self::PREFIX . $key, $default);
    }

    public function has(string $key): bool
    {
        return $this->repository->has(self::PREFIX . $key);
    }

    public function getRequired(string $key): mixed
    {
        if (!$this->has($key)) {
            throw new InfrastructureException("Required config key '{$key}' is missing.");
        }

        return $this->get($key);
    }

    public function set(string $key, mixed $value): void
    {
        $this->repository->set(self::PREFIX . $key, $value);
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
composer test:unit -- --filter=ConcreteCmsConfigTest
```

Expected: all 6 tests pass.

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Contract/Config/ \
  public/packages/inno_shared/src/Infrastructure/Adapter/Config/ \
  tests/unit/inno_shared/Infrastructure/Adapter/Config/
git commit -m "feat(shared): add ConfigInterface and ConcreteCmsConfig adapter"
```

---

## Task 5: LoggerInterface & ConcreteCmsLogger Adapter

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/Contract/Logger/LoggerInterface.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Logger/ConcreteCmsLogger.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Logger/SensitiveDataProcessor.php`
- Test: `tests/unit/inno_shared/Infrastructure/Adapter/Logger/ConcreteCmsLoggerTest.php`
- Test: `tests/unit/inno_shared/Infrastructure/Adapter/Logger/SensitiveDataProcessorTest.php`

- [ ] **Step 1: Write the failing tests for SensitiveDataProcessor**

Create `tests/unit/inno_shared/Infrastructure/Adapter/Logger/SensitiveDataProcessorTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Adapter\Logger\SensitiveDataProcessor;

test('scrubs sensitive keys from context', function () {
    $processor = new SensitiveDataProcessor();
    $record = [
        'context' => [
            'userId' => 42,
            'password' => 'secret123',
            'token' => 'abc-token',
            'authorization' => 'Bearer xyz',
            'cookie' => 'session=abc',
            'credit_card' => '4111-1111',
            'secret' => 'my-secret',
        ],
    ];

    $result = $processor($record);

    expect($result['context']['userId'])->toBe(42)
        ->and($result['context']['password'])->toBe('***REDACTED***')
        ->and($result['context']['token'])->toBe('***REDACTED***')
        ->and($result['context']['authorization'])->toBe('***REDACTED***')
        ->and($result['context']['cookie'])->toBe('***REDACTED***')
        ->and($result['context']['credit_card'])->toBe('***REDACTED***')
        ->and($result['context']['secret'])->toBe('***REDACTED***');
});

test('handles empty context', function () {
    $processor = new SensitiveDataProcessor();
    $record = ['context' => []];
    $result = $processor($record);
    expect($result['context'])->toBe([]);
});

test('handles nested arrays', function () {
    $processor = new SensitiveDataProcessor();
    $record = [
        'context' => [
            'user' => ['name' => 'John', 'password' => 'secret'],
        ],
    ];
    $result = $processor($record);
    expect($result['context']['user']['password'])->toBe('***REDACTED***')
        ->and($result['context']['user']['name'])->toBe('John');
});
```

- [ ] **Step 2: Write the failing tests for ConcreteCmsLogger**

Create `tests/unit/inno_shared/Infrastructure/Adapter/Logger/ConcreteCmsLoggerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Core\Logging\Logger;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Logger\ConcreteCmsLogger;

afterEach(fn () => Mockery::close());

test('info() delegates to CMS logger', function () {
    $cmsLogger = Mockery::mock(Logger::class);
    $cmsLogger->shouldReceive('info')
        ->once()
        ->with('Idea submitted', ['ideaId' => '123']);

    $logger = new ConcreteCmsLogger($cmsLogger);
    $logger->info('Idea submitted', ['ideaId' => '123']);
});

test('error() delegates to CMS logger', function () {
    $cmsLogger = Mockery::mock(Logger::class);
    $cmsLogger->shouldReceive('error')
        ->once()
        ->with('DB failed', ['table' => 'ideas']);

    $logger = new ConcreteCmsLogger($cmsLogger);
    $logger->error('DB failed', ['table' => 'ideas']);
});

test('withContext() merges default context into all subsequent calls', function () {
    $cmsLogger = Mockery::mock(Logger::class);
    $cmsLogger->shouldReceive('info')
        ->once()
        ->with('Test message', ['requestId' => 'req-1', 'extra' => 'data']);

    $logger = new ConcreteCmsLogger($cmsLogger);
    $contextLogger = $logger->withContext(['requestId' => 'req-1']);
    $contextLogger->info('Test message', ['extra' => 'data']);
});

test('withContext() does not mutate original logger', function () {
    $cmsLogger = Mockery::mock(Logger::class);
    $cmsLogger->shouldReceive('info')
        ->once()
        ->with('Original', []);

    $logger = new ConcreteCmsLogger($cmsLogger);
    $logger->withContext(['requestId' => 'req-1']);
    $logger->info('Original');
});
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
composer test:unit -- --filter="ConcreteCmsLoggerTest|SensitiveDataProcessorTest"
```

Expected: FAIL.

- [ ] **Step 4: Create `LoggerInterface.php`**

Create `public/packages/inno_shared/src/Infrastructure/Contract/Logger/LoggerInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Logger;

interface LoggerInterface extends \Psr\Log\LoggerInterface
{
    /**
     * Returns a new logger instance with the given context
     * merged into every subsequent log call.
     */
    public function withContext(array $context): static;
}
```

- [ ] **Step 5: Create `SensitiveDataProcessor.php`**

Create `public/packages/inno_shared/src/Infrastructure/Adapter/Logger/SensitiveDataProcessor.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Logger;

class SensitiveDataProcessor
{
    private const SENSITIVE_KEYS = [
        'password',
        'token',
        'secret',
        'authorization',
        'cookie',
        'credit_card',
    ];

    private const REDACTED = '***REDACTED***';

    public function __invoke(array $record): array
    {
        if (isset($record['context']) && is_array($record['context'])) {
            $record['context'] = $this->scrub($record['context']);
        }

        return $record;
    }

    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitive($key)) {
                $data[$key] = self::REDACTED;
            } elseif (is_array($value)) {
                $data[$key] = $this->scrub($value);
            }
        }

        return $data;
    }

    private function isSensitive(string $key): bool
    {
        $lower = strtolower($key);
        foreach (self::SENSITIVE_KEYS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 6: Create `ConcreteCmsLogger.php`**

Create `public/packages/inno_shared/src/Infrastructure/Adapter/Logger/ConcreteCmsLogger.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Logger;

use Concrete\Core\Logging\Logger;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class ConcreteCmsLogger implements LoggerInterface
{
    public function __construct(
        private readonly Logger $logger,
        private readonly array $defaultContext = [],
    ) {}

    public function withContext(array $context): static
    {
        return new static($this->logger, array_merge($this->defaultContext, $context));
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->logger->emergency($message, $this->mergeContext($context));
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->logger->alert($message, $this->mergeContext($context));
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->logger->critical($message, $this->mergeContext($context));
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->logger->error($message, $this->mergeContext($context));
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->logger->warning($message, $this->mergeContext($context));
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->logger->notice($message, $this->mergeContext($context));
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->logger->info($message, $this->mergeContext($context));
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->logger->debug($message, $this->mergeContext($context));
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $this->mergeContext($context));
    }

    private function mergeContext(array $context): array
    {
        return array_merge($this->defaultContext, $context);
    }
}
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
composer test:unit -- --filter="ConcreteCmsLoggerTest|SensitiveDataProcessorTest"
```

Expected: all 7 tests pass.

- [ ] **Step 8: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Contract/Logger/ \
  public/packages/inno_shared/src/Infrastructure/Adapter/Logger/ \
  tests/unit/inno_shared/Infrastructure/Adapter/Logger/
git commit -m "feat(shared): add LoggerInterface, ConcreteCmsLogger, and SensitiveDataProcessor"
```

---

## Task 6: ExceptionHandlerInterface & ConcreteCmsExceptionHandler

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/Contract/ErrorHandling/ExceptionHandlerInterface.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/ErrorHandling/ConcreteCmsExceptionHandler.php`
- Test: `tests/unit/inno_shared/Infrastructure/Adapter/ErrorHandling/ConcreteCmsExceptionHandlerTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/unit/inno_shared/Infrastructure/Adapter/ErrorHandling/ConcreteCmsExceptionHandlerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Adapter\ErrorHandling\ConcreteCmsExceptionHandler;
use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;
use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

afterEach(fn () => Mockery::close());

test('toJsonResponse() maps BaseException to correct HTTP status and user message', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('warning')->once();
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('get')->with('app.debug', false)->andReturn(false);

    $handler = new ConcreteCmsExceptionHandler($logger, $config);
    $e = new NotFoundException('Idea xyz not found', ['ideaId' => 'xyz']);

    $response = $handler->toJsonResponse($e);
    $body = json_decode($response->getContent(), true);

    expect($response->getStatusCode())->toBe(404)
        ->and($body['error']['message'])->toBe('The requested resource was not found.')
        ->and($body['error']['code'])->toBe(404)
        ->and($body)->not->toHaveKey('error.debug');
});

test('toJsonResponse() includes debug info when app.debug is true', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('warning')->once();
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('get')->with('app.debug', false)->andReturn(true);

    $handler = new ConcreteCmsExceptionHandler($logger, $config);
    $e = new ValidationException('Title is empty', ['field' => 'title']);

    $response = $handler->toJsonResponse($e);
    $body = json_decode($response->getContent(), true);

    expect($response->getStatusCode())->toBe(400)
        ->and($body['error']['debug']['exception'])->toBe('ValidationException')
        ->and($body['error']['debug']['detail'])->toBe('Title is empty');
});

test('toJsonResponse() handles unexpected exceptions as 500', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('critical')->once();
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('get')->with('app.debug', false)->andReturn(false);

    $handler = new ConcreteCmsExceptionHandler($logger, $config);
    $e = new \RuntimeException('Something broke');

    $response = $handler->toJsonResponse($e);
    $body = json_decode($response->getContent(), true);

    expect($response->getStatusCode())->toBe(500)
        ->and($body['error']['message'])->toBe('An internal error occurred. Please try again later.')
        ->and($body['error']['code'])->toBe(500);
});

test('logs BaseException at warning level', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('warning')
        ->once()
        ->with('Idea xyz not found', Mockery::hasKey('ideaId'));
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('get')->andReturn(false);

    $handler = new ConcreteCmsExceptionHandler($logger, $config);
    $handler->toJsonResponse(new NotFoundException('Idea xyz not found', ['ideaId' => 'xyz']));
});

test('logs unexpected exception at critical level', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('critical')
        ->once()
        ->with('Something broke', Mockery::hasKey('exception'));
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('get')->andReturn(false);

    $handler = new ConcreteCmsExceptionHandler($logger, $config);
    $handler->toJsonResponse(new \RuntimeException('Something broke'));
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
composer test:unit -- --filter=ConcreteCmsExceptionHandlerTest
```

Expected: FAIL.

- [ ] **Step 3: Create `ExceptionHandlerInterface.php`**

Create `public/packages/inno_shared/src/Infrastructure/Contract/ErrorHandling/ExceptionHandlerInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling;

use Symfony\Component\HttpFoundation\JsonResponse;

interface ExceptionHandlerInterface
{
    public function toJsonResponse(\Throwable $e): JsonResponse;
}
```

- [ ] **Step 4: Create `ConcreteCmsExceptionHandler.php`**

Create `public/packages/inno_shared/src/Infrastructure/Adapter/ErrorHandling/ConcreteCmsExceptionHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\ErrorHandling;

use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\BaseException;
use Symfony\Component\HttpFoundation\JsonResponse;

class ConcreteCmsExceptionHandler implements ExceptionHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConfigInterface $config,
    ) {}

    public function toJsonResponse(\Throwable $e): JsonResponse
    {
        if ($e instanceof BaseException) {
            $this->logger->warning($e->getMessage(), $e->getContext());

            return $this->buildResponse(
                $e->getHttpStatusCode(),
                $e->getUserMessage(),
                $e,
            );
        }

        $this->logger->critical($e->getMessage(), [
            'exception' => get_class($e),
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->buildResponse(
            500,
            'An internal error occurred. Please try again later.',
            $e,
        );
    }

    private function buildResponse(int $status, string $userMessage, \Throwable $e): JsonResponse
    {
        $body = [
            'error' => [
                'message' => $userMessage,
                'code' => $status,
            ],
        ];

        if ($this->config->get('app.debug', false)) {
            $body['error']['debug'] = [
                'exception' => (new \ReflectionClass($e))->getShortName(),
                'detail' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        return new JsonResponse($body, $status);
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
composer test:unit -- --filter=ConcreteCmsExceptionHandlerTest
```

Expected: all 5 tests pass.

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Contract/ErrorHandling/ \
  public/packages/inno_shared/src/Infrastructure/Adapter/ErrorHandling/ \
  tests/unit/inno_shared/Infrastructure/Adapter/ErrorHandling/
git commit -m "feat(shared): add ExceptionHandlerInterface and ConcreteCmsExceptionHandler"
```

---

## Task 7: AuthInterface, SessionInterface & ConcreteCmsAuth Adapter

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/Contract/Auth/AuthInterface.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Contract/Auth/SessionInterface.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Contract/Auth/UserContext.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Auth/ConcreteCmsAuth.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Auth/ConcreteCmsSession.php`
- Test: `tests/unit/inno_shared/Infrastructure/Adapter/Auth/ConcreteCmsAuthTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/unit/inno_shared/Infrastructure/Adapter/Auth/ConcreteCmsAuthTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Core\User\User;
use Concrete\Core\User\UserInfo;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Auth\ConcreteCmsAuth;

afterEach(fn () => Mockery::close());

test('isAuthenticated() returns true when CMS user is registered', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->isAuthenticated())->toBeTrue();
});

test('isAuthenticated() returns false when CMS user is not registered', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(false);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->isAuthenticated())->toBeFalse();
});

test('getUserId() returns user ID when authenticated', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserID')->andReturn(42);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->getUserId())->toBe(42);
});

test('getUserId() returns null when not authenticated', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(false);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->getUserId())->toBeNull();
});

test('getCurrentUser() returns UserContext when authenticated', function () {
    $userInfo = Mockery::mock(UserInfo::class);
    $userInfo->shouldReceive('getUserEmail')->andReturn('john@example.com');
    $userInfo->shouldReceive('getUserName')->andReturn('John');

    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserID')->andReturn(42);
    $user->shouldReceive('getUserInfoObject')->andReturn($userInfo);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Initiator', 2 => 'Mitglied']);

    $auth = new ConcreteCmsAuth($user);
    $ctx = $auth->getCurrentUser();

    expect($ctx)->not->toBeNull()
        ->and($ctx->id)->toBe(42)
        ->and($ctx->email)->toBe('john@example.com')
        ->and($ctx->name)->toBe('John')
        ->and($ctx->roles)->toBe(['Initiator', 'Mitglied']);
});

test('getCurrentUser() returns null when not authenticated', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(false);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->getCurrentUser())->toBeNull();
});

test('hasRole() checks CMS user groups', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Moderator', 2 => 'Mitglied']);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->hasRole('moderator'))->toBeTrue()
        ->and($auth->hasRole('administrator'))->toBeFalse();
});

test('hasPermission() maps permissions to roles', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Initiator']);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->hasPermission('idea.submit'))->toBeTrue()
        ->and($auth->hasPermission('moderation.review'))->toBeFalse();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
composer test:unit -- --filter=ConcreteCmsAuthTest
```

Expected: FAIL.

- [ ] **Step 3: Create `UserContext.php`**

Create `public/packages/inno_shared/src/Infrastructure/Contract/Auth/UserContext.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Auth;

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

- [ ] **Step 4: Create `AuthInterface.php`**

Create `public/packages/inno_shared/src/Infrastructure/Contract/Auth/AuthInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Auth;

interface AuthInterface
{
    public function getCurrentUser(): ?UserContext;

    public function isAuthenticated(): bool;

    public function hasPermission(string $permission): bool;

    public function hasRole(string $role): bool;

    public function getUserId(): ?int;
}
```

- [ ] **Step 5: Create `SessionInterface.php`**

Create `public/packages/inno_shared/src/Infrastructure/Contract/Auth/SessionInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Auth;

interface SessionInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    public function invalidate(): void;
}
```

- [ ] **Step 6: Create `ConcreteCmsAuth.php`**

Create `public/packages/inno_shared/src/Infrastructure/Adapter/Auth/ConcreteCmsAuth.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Auth;

use Concrete\Core\User\User;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\UserContext;

class ConcreteCmsAuth implements AuthInterface
{
    /**
     * Maps permission strings to the roles that grant them.
     * Role names are lowercase for case-insensitive comparison.
     */
    private const PERMISSION_MAP = [
        'idea.submit' => ['initiator', 'administrator'],
        'idea.view' => ['member', 'developer', 'initiator', 'moderator', 'administrator'],
        'idea.vote' => ['member', 'developer', 'initiator', 'moderator', 'administrator'],
        'moderation.review' => ['moderator', 'administrator'],
        'space.join' => ['developer', 'initiator', 'administrator'],
        'space.manage' => ['administrator'],
    ];

    /**
     * Maps German CMS group names to English role constants.
     */
    private const GROUP_TO_ROLE = [
        'mitglied' => 'member',
        'entwickler' => 'developer',
        'initiator' => 'initiator',
        'moderator' => 'moderator',
        'administrator' => 'administrator',
        // English names map to themselves
        'member' => 'member',
        'developer' => 'developer',
    ];

    public function __construct(private readonly User $user) {}

    public function getCurrentUser(): ?UserContext
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        $info = $this->user->getUserInfoObject();

        return new UserContext(
            id: (int) $this->user->getUserID(),
            email: $info->getUserEmail(),
            name: $info->getUserName(),
            roles: $this->getGroupNames(),
        );
    }

    public function isAuthenticated(): bool
    {
        return $this->user->isRegistered();
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->isAuthenticated()) {
            // idea.view is public
            return $permission === 'idea.view';
        }

        $allowedRoles = self::PERMISSION_MAP[$permission] ?? [];
        $userRoles = $this->getNormalizedRoles();

        return !empty(array_intersect($allowedRoles, $userRoles));
    }

    public function hasRole(string $role): bool
    {
        return in_array(strtolower($role), $this->getNormalizedRoles(), true);
    }

    public function getUserId(): ?int
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return (int) $this->user->getUserID();
    }

    /**
     * @return string[] CMS group names
     */
    private function getGroupNames(): array
    {
        return array_values($this->user->getUserGroups());
    }

    /**
     * @return string[] Normalized role names (lowercase English)
     */
    private function getNormalizedRoles(): array
    {
        $roles = [];
        foreach ($this->getGroupNames() as $group) {
            $lower = strtolower($group);
            $roles[] = self::GROUP_TO_ROLE[$lower] ?? $lower;
        }

        return $roles;
    }
}
```

- [ ] **Step 7: Create `ConcreteCmsSession.php`**

Create `public/packages/inno_shared/src/Infrastructure/Adapter/Auth/ConcreteCmsSession.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Auth;

use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class ConcreteCmsSession implements SessionInterface
{
    public function __construct(private readonly Session $session) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->session->set($key, $value);
    }

    public function remove(string $key): void
    {
        $this->session->remove($key);
    }

    public function invalidate(): void
    {
        $this->session->invalidate();
    }
}
```

- [ ] **Step 8: Run tests to verify they pass**

```bash
composer test:unit -- --filter=ConcreteCmsAuthTest
```

Expected: all 8 tests pass.

- [ ] **Step 9: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Contract/Auth/ \
  public/packages/inno_shared/src/Infrastructure/Adapter/Auth/ \
  tests/unit/inno_shared/Infrastructure/Adapter/Auth/
git commit -m "feat(shared): add AuthInterface, SessionInterface, UserContext, and CMS adapters"
```

---

## Task 8: CacheInterface & ConcreteCmsCache Adapter

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/Contract/Cache/CacheInterface.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Cache/ConcreteCmsCache.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Cache/NullCache.php`
- Test: `tests/unit/inno_shared/Infrastructure/Adapter/Cache/ConcreteCmsCacheTest.php`
- Test: `tests/unit/inno_shared/Infrastructure/Adapter/Cache/NullCacheTest.php`

- [ ] **Step 1: Write the failing tests for NullCache**

Create `tests/unit/inno_shared/Infrastructure/Adapter/Cache/NullCacheTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Adapter\Cache\NullCache;

test('get() always returns default', function () {
    $cache = new NullCache();
    expect($cache->get('key', 'default'))->toBe('default');
});

test('set() does not store anything', function () {
    $cache = new NullCache();
    $cache->set('key', 'value', 3600);
    expect($cache->get('key'))->toBeNull();
});

test('has() always returns false', function () {
    $cache = new NullCache();
    expect($cache->has('key'))->toBeFalse();
});

test('getOrSet() always calls the callback', function () {
    $cache = new NullCache();
    $called = 0;
    $result = $cache->getOrSet('key', 3600, function () use (&$called) {
        $called++;
        return 'computed';
    });
    expect($result)->toBe('computed')
        ->and($called)->toBe(1);
});

test('delete() returns true', function () {
    $cache = new NullCache();
    expect($cache->delete('key'))->toBeTrue();
});

test('clear() returns true', function () {
    $cache = new NullCache();
    expect($cache->clear())->toBeTrue();
});

test('flushTag() does nothing without error', function () {
    $cache = new NullCache();
    $cache->flushTag('some_tag');
    expect(true)->toBeTrue(); // no exception
});
```

- [ ] **Step 2: Write the failing tests for ConcreteCmsCache**

Create `tests/unit/inno_shared/Infrastructure/Adapter/Cache/ConcreteCmsCacheTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Core\Cache\Cache;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Cache\ConcreteCmsCache;
use Stash\Interfaces\ItemInterface;
use Stash\Interfaces\PoolInterface;

afterEach(fn () => Mockery::close());

test('get() returns cached value', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('isMiss')->andReturn(false);
    $item->shouldReceive('get')->andReturn('cached_value');

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/test/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    expect($cache->get('test.key'))->toBe('cached_value');
});

test('get() returns default on cache miss', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('isMiss')->andReturn(true);

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/missing/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    expect($cache->get('missing.key', 'fallback'))->toBe('fallback');
});

test('set() stores value in pool', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('set')->once()->with('my_value');
    $item->shouldReceive('setTTL')->once()->with(3600);
    $item->shouldReceive('save')->once();

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/my/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    expect($cache->set('my.key', 'my_value', 3600))->toBeTrue();
});

test('getOrSet() returns cached value on hit', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('isMiss')->andReturn(false);
    $item->shouldReceive('get')->andReturn('cached');

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/my/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    $called = false;
    $result = $cache->getOrSet('my.key', 3600, function () use (&$called) {
        $called = true;
        return 'computed';
    });
    expect($result)->toBe('cached')
        ->and($called)->toBeFalse();
});

test('getOrSet() calls callback and caches on miss', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('isMiss')->andReturn(true);
    $item->shouldReceive('set')->once()->with('computed');
    $item->shouldReceive('setTTL')->once()->with(600);
    $item->shouldReceive('save')->once();

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/my/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    $result = $cache->getOrSet('my.key', 600, fn () => 'computed');
    expect($result)->toBe('computed');
});

test('has() checks pool', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('isMiss')->andReturn(false);

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/my/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    expect($cache->has('my.key'))->toBeTrue();
});

test('delete() clears item from pool', function () {
    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('deleteItem')->once()->with('innovationsbox/my/key')->andReturn(true);

    $cache = new ConcreteCmsCache($pool);
    expect($cache->delete('my.key'))->toBeTrue();
});
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
composer test:unit -- --filter="ConcreteCmsCacheTest|NullCacheTest"
```

Expected: FAIL.

- [ ] **Step 4: Create `CacheInterface.php`**

Create `public/packages/inno_shared/src/Infrastructure/Contract/Cache/CacheInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Cache;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

interface CacheInterface extends PsrCacheInterface
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

- [ ] **Step 5: Create `NullCache.php`**

Create `public/packages/inno_shared/src/Infrastructure/Adapter/Cache/NullCache.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Cache;

use Concrete\Package\InnoShared\Infrastructure\Contract\Cache\CacheInterface;

class NullCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return true;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $default;
        }
        return $result;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function getOrSet(string $key, int $ttl, callable $callback): mixed
    {
        return $callback();
    }

    public function setTagged(string $key, mixed $value, int $ttl, array $tags): void
    {
        // No-op
    }

    public function flushTag(string $tag): void
    {
        // No-op
    }
}
```

- [ ] **Step 6: Create `ConcreteCmsCache.php`**

Create `public/packages/inno_shared/src/Infrastructure/Adapter/Cache/ConcreteCmsCache.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Cache;

use Concrete\Package\InnoShared\Infrastructure\Contract\Cache\CacheInterface;
use Stash\Interfaces\PoolInterface;

class ConcreteCmsCache implements CacheInterface
{
    private const PREFIX = 'innovationsbox/';
    private const TAG_PREFIX = 'innovationsbox/_tags/';

    public function __construct(private readonly PoolInterface $pool) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->pool->getItem($this->normalizeKey($key));
        if ($item->isMiss()) {
            return $default;
        }

        return $item->get();
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $item = $this->pool->getItem($this->normalizeKey($key));
        $item->set($value);
        if ($ttl !== null) {
            $item->setTTL($ttl);
        }
        $item->save();

        return true;
    }

    public function delete(string $key): bool
    {
        return $this->pool->deleteItem($this->normalizeKey($key));
    }

    public function clear(): bool
    {
        return $this->pool->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $item = $this->pool->getItem($this->normalizeKey($key));

        return !$item->isMiss();
    }

    public function getOrSet(string $key, int $ttl, callable $callback): mixed
    {
        $item = $this->pool->getItem($this->normalizeKey($key));
        if (!$item->isMiss()) {
            return $item->get();
        }

        $value = $callback();
        $item->set($value);
        $item->setTTL($ttl);
        $item->save();

        return $value;
    }

    public function setTagged(string $key, mixed $value, int $ttl, array $tags): void
    {
        $this->set($key, $value, $ttl);

        foreach ($tags as $tag) {
            $tagKey = self::TAG_PREFIX . $tag;
            $tagItem = $this->pool->getItem($tagKey);
            $keys = $tagItem->isMiss() ? [] : ($tagItem->get() ?? []);
            $keys[] = $key;
            $keys = array_unique($keys);
            $tagItem->set($keys);
            $tagItem->save();
        }
    }

    public function flushTag(string $tag): void
    {
        $tagKey = self::TAG_PREFIX . $tag;
        $tagItem = $this->pool->getItem($tagKey);
        if ($tagItem->isMiss()) {
            return;
        }

        $keys = $tagItem->get() ?? [];
        foreach ($keys as $key) {
            $this->delete($key);
        }

        $this->pool->deleteItem($tagKey);
    }

    private function normalizeKey(string $key): string
    {
        return self::PREFIX . str_replace('.', '/', $key);
    }
}
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
composer test:unit -- --filter="ConcreteCmsCacheTest|NullCacheTest"
```

Expected: all 14 tests pass.

- [ ] **Step 8: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Contract/Cache/ \
  public/packages/inno_shared/src/Infrastructure/Adapter/Cache/ \
  tests/unit/inno_shared/Infrastructure/Adapter/Cache/
git commit -m "feat(shared): add CacheInterface, ConcreteCmsCache, and NullCache adapters"
```

---

## Task 9: TransactionInterface & ConcreteCmsTransaction Adapter

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/Contract/Transaction/TransactionInterface.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Transaction/ConcreteCmsTransaction.php`
- Test: `tests/unit/inno_shared/Infrastructure/Adapter/Transaction/ConcreteCmsTransactionTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/unit/inno_shared/Infrastructure/Adapter/Transaction/ConcreteCmsTransactionTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Transaction\ConcreteCmsTransaction;

afterEach(fn () => Mockery::close());

test('transactional() commits on success', function () {
    $conn = Mockery::mock(Connection::class);
    $conn->shouldReceive('beginTransaction')->once();
    $conn->shouldReceive('commit')->once();
    $conn->shouldNotReceive('rollBack');

    $tx = new ConcreteCmsTransaction($conn);
    $result = $tx->transactional(fn () => 'success');

    expect($result)->toBe('success');
});

test('transactional() rolls back and rethrows on exception', function () {
    $conn = Mockery::mock(Connection::class);
    $conn->shouldReceive('beginTransaction')->once();
    $conn->shouldReceive('rollBack')->once();
    $conn->shouldNotReceive('commit');

    $tx = new ConcreteCmsTransaction($conn);

    expect(fn () => $tx->transactional(function () {
        throw new \RuntimeException('DB error');
    }))->toThrow(\RuntimeException::class, 'DB error');
});

test('begin() and commit() work directly', function () {
    $conn = Mockery::mock(Connection::class);
    $conn->shouldReceive('beginTransaction')->once();
    $conn->shouldReceive('commit')->once();

    $tx = new ConcreteCmsTransaction($conn);
    $tx->begin();
    $tx->commit();
});

test('begin() and rollback() work directly', function () {
    $conn = Mockery::mock(Connection::class);
    $conn->shouldReceive('beginTransaction')->once();
    $conn->shouldReceive('rollBack')->once();

    $tx = new ConcreteCmsTransaction($conn);
    $tx->begin();
    $tx->rollback();
});

test('getDepth() returns transaction nesting level', function () {
    $conn = Mockery::mock(Connection::class);
    $conn->shouldReceive('getTransactionNestingLevel')->andReturn(2);

    $tx = new ConcreteCmsTransaction($conn);
    expect($tx->getDepth())->toBe(2);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
composer test:unit -- --filter=ConcreteCmsTransactionTest
```

Expected: FAIL.

- [ ] **Step 3: Create `TransactionInterface.php`**

Create `public/packages/inno_shared/src/Infrastructure/Contract/Transaction/TransactionInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Transaction;

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

- [ ] **Step 4: Create `ConcreteCmsTransaction.php`**

Create `public/packages/inno_shared/src/Infrastructure/Adapter/Transaction/ConcreteCmsTransaction.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Transaction;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;

class ConcreteCmsTransaction implements TransactionInterface
{
    public function __construct(private readonly Connection $connection) {}

    public function begin(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }

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

    public function getDepth(): int
    {
        return $this->connection->getTransactionNestingLevel();
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
composer test:unit -- --filter=ConcreteCmsTransactionTest
```

Expected: all 5 tests pass.

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Contract/Transaction/ \
  public/packages/inno_shared/src/Infrastructure/Adapter/Transaction/ \
  tests/unit/inno_shared/Infrastructure/Adapter/Transaction/
git commit -m "feat(shared): add TransactionInterface and ConcreteCmsTransaction adapter"
```

---

## Task 10: MetricsInterface, HealthCheckInterface & Adapters

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/Contract/Monitoring/MetricsInterface.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Contract/Monitoring/HealthCheckInterface.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Contract/Monitoring/HealthStatus.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Monitoring/LogBasedMetrics.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Monitoring/ConcreteCmsHealthCheck.php`
- Test: `tests/unit/inno_shared/Infrastructure/Adapter/Monitoring/LogBasedMetricsTest.php`
- Test: `tests/unit/inno_shared/Infrastructure/Adapter/Monitoring/ConcreteCmsHealthCheckTest.php`

- [ ] **Step 1: Write the failing tests for LogBasedMetrics**

Create `tests/unit/inno_shared/Infrastructure/Adapter/Monitoring/LogBasedMetricsTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Adapter\Monitoring\LogBasedMetrics;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

afterEach(fn () => Mockery::close());

test('increment() logs counter metric', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')
        ->once()
        ->with('metric.counter', ['name' => 'idea.submitted', 'value' => 1, 'status' => 'draft']);

    $metrics = new LogBasedMetrics($logger);
    $metrics->increment('idea.submitted', 1, ['status' => 'draft']);
});

test('gauge() logs gauge metric', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')
        ->once()
        ->with('metric.gauge', ['name' => 'queue.size', 'value' => 42.0]);

    $metrics = new LogBasedMetrics($logger);
    $metrics->gauge('queue.size', 42.0);
});

test('timing() logs timing metric', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')
        ->once()
        ->with('metric.timing', ['name' => 'handler.duration', 'ms' => 142.5]);

    $metrics = new LogBasedMetrics($logger);
    $metrics->timing('handler.duration', 142.5);
});

test('measure() times the callable and logs it', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')
        ->once()
        ->with('metric.timing', Mockery::on(function ($ctx) {
            return $ctx['name'] === 'test.operation' && is_float($ctx['ms']);
        }));

    $metrics = new LogBasedMetrics($logger);
    $result = $metrics->measure('test.operation', fn () => 'done');
    expect($result)->toBe('done');
});

test('event() logs an event', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')
        ->once()
        ->with('metric.event', ['name' => 'deploy', 'version' => '1.0']);

    $metrics = new LogBasedMetrics($logger);
    $metrics->event('deploy', ['version' => '1.0']);
});
```

- [ ] **Step 2: Write the failing tests for ConcreteCmsHealthCheck**

Create `tests/unit/inno_shared/Infrastructure/Adapter/Monitoring/ConcreteCmsHealthCheckTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Monitoring\ConcreteCmsHealthCheck;
use Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring\HealthStatus;

afterEach(fn () => Mockery::close());

test('check() returns healthy when all services are up', function () {
    $db = Mockery::mock(Connection::class);
    $db->shouldReceive('fetchOne')->with('SELECT 1')->andReturn('1');

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('concrete.version')->andReturn('9.4.0');

    $check = new ConcreteCmsHealthCheck($db, $config);
    $result = $check->check();

    expect($result['database'])->toBe(HealthStatus::Healthy)
        ->and($result['cms'])->toBe(HealthStatus::Healthy);
});

test('check() returns unhealthy database when query fails', function () {
    $db = Mockery::mock(Connection::class);
    $db->shouldReceive('fetchOne')->andThrow(new \Exception('Connection refused'));

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('concrete.version')->andReturn('9.4.0');

    $check = new ConcreteCmsHealthCheck($db, $config);
    $result = $check->check();

    expect($result['database'])->toBe(HealthStatus::Unhealthy);
});

test('check() returns unhealthy CMS when config fails', function () {
    $db = Mockery::mock(Connection::class);
    $db->shouldReceive('fetchOne')->with('SELECT 1')->andReturn('1');

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('concrete.version')->andReturn(null);

    $check = new ConcreteCmsHealthCheck($db, $config);
    $result = $check->check();

    expect($result['cms'])->toBe(HealthStatus::Unhealthy);
});
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
composer test:unit -- --filter="LogBasedMetricsTest|ConcreteCmsHealthCheckTest"
```

Expected: FAIL.

- [ ] **Step 4: Create `HealthStatus.php`**

Create `public/packages/inno_shared/src/Infrastructure/Contract/Monitoring/HealthStatus.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring;

enum HealthStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unhealthy = 'unhealthy';
}
```

- [ ] **Step 5: Create `MetricsInterface.php`**

Create `public/packages/inno_shared/src/Infrastructure/Contract/Monitoring/MetricsInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring;

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

- [ ] **Step 6: Create `HealthCheckInterface.php`**

Create `public/packages/inno_shared/src/Infrastructure/Contract/Monitoring/HealthCheckInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring;

interface HealthCheckInterface
{
    /**
     * @return array<string, HealthStatus>
     */
    public function check(): array;
}
```

- [ ] **Step 7: Create `LogBasedMetrics.php`**

Create `public/packages/inno_shared/src/Infrastructure/Adapter/Monitoring/LogBasedMetrics.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Monitoring;

use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring\MetricsInterface;

class LogBasedMetrics implements MetricsInterface
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function increment(string $counter, int $value = 1, array $tags = []): void
    {
        $this->logger->info('metric.counter', array_merge(
            ['name' => $counter, 'value' => $value],
            $tags,
        ));
    }

    public function gauge(string $name, float $value, array $tags = []): void
    {
        $this->logger->info('metric.gauge', array_merge(
            ['name' => $name, 'value' => $value],
            $tags,
        ));
    }

    public function timing(string $name, float $milliseconds, array $tags = []): void
    {
        $this->logger->info('metric.timing', array_merge(
            ['name' => $name, 'ms' => $milliseconds],
            $tags,
        ));
    }

    public function measure(string $name, callable $operation, array $tags = []): mixed
    {
        $start = hrtime(true);
        $result = $operation();
        $ms = (hrtime(true) - $start) / 1_000_000;

        $this->timing($name, $ms, $tags);

        return $result;
    }

    public function event(string $name, array $context = []): void
    {
        $this->logger->info('metric.event', array_merge(
            ['name' => $name],
            $context,
        ));
    }
}
```

- [ ] **Step 8: Create `ConcreteCmsHealthCheck.php`**

Create `public/packages/inno_shared/src/Infrastructure/Adapter/Monitoring/ConcreteCmsHealthCheck.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Monitoring;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring\HealthCheckInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring\HealthStatus;

class ConcreteCmsHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Repository $config,
    ) {}

    public function check(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cms' => $this->checkCms(),
        ];
    }

    private function checkDatabase(): HealthStatus
    {
        try {
            $this->connection->fetchOne('SELECT 1');

            return HealthStatus::Healthy;
        } catch (\Throwable) {
            return HealthStatus::Unhealthy;
        }
    }

    private function checkCms(): HealthStatus
    {
        try {
            $version = $this->config->get('concrete.version');

            return $version !== null ? HealthStatus::Healthy : HealthStatus::Unhealthy;
        } catch (\Throwable) {
            return HealthStatus::Unhealthy;
        }
    }
}
```

- [ ] **Step 9: Run tests to verify they pass**

```bash
composer test:unit -- --filter="LogBasedMetricsTest|ConcreteCmsHealthCheckTest"
```

Expected: all 8 tests pass.

- [ ] **Step 10: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Contract/Monitoring/ \
  public/packages/inno_shared/src/Infrastructure/Adapter/Monitoring/ \
  tests/unit/inno_shared/Infrastructure/Adapter/Monitoring/
git commit -m "feat(shared): add MetricsInterface, HealthCheckInterface, LogBasedMetrics, and ConcreteCmsHealthCheck"
```

---

## Task 11: DI Bindings in `inno_shared/controller.php`

**Files:**
- Modify: `public/packages/inno_shared/controller.php`

- [ ] **Step 1: Rewrite `controller.php` with all DI bindings and health route**

Replace the full content of `public/packages/inno_shared/controller.php` with:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Logging\LoggerFactory;
use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Core\User\User;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Auth\ConcreteCmsAuth;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Auth\ConcreteCmsSession;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Cache\ConcreteCmsCache;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Config\ConcreteCmsConfig;
use Concrete\Package\InnoShared\Infrastructure\Adapter\ErrorHandling\ConcreteCmsExceptionHandler;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Logger\ConcreteCmsLogger;
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
    protected $appVersionRequired = '9.4.0';
    protected $pkgVersion = '0.2.0';

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

        // --- Transactions ---
        $this->app->bind(TransactionInterface::class, function ($app) {
            return new ConcreteCmsTransaction($app->make(Connection::class));
        });

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

        // --- Boot-time config validation ---
        $this->validateRequiredConfig();
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

    private function validateRequiredConfig(): void
    {
        // Defer validation to first request, not package load
        // Required keys are validated when accessed via getRequired()
    }
}
```

- [ ] **Step 2: Run all tests to verify nothing broke**

```bash
composer test:unit
```

Expected: all tests pass.

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_shared/controller.php
git commit -m "feat(shared): register all cross-cutting DI bindings and health endpoint in controller"
```

---

## Task 12: Domain-Specific Exceptions

**Files:**
- Create: `public/packages/inno_project_idea/src/Domain/Exception/ProjectIdeaNotFoundException.php`
- Create: `public/packages/inno_project_idea/src/Domain/Exception/InvalidProjectIdeaException.php`
- Create: `public/packages/inno_moderation/src/Domain/Exception/ModerationReviewNotFoundException.php`
- Create: `public/packages/inno_moderation/src/Domain/Exception/InvalidModerationDecisionException.php`
- Create: `public/packages/inno_project_space/src/Domain/Exception/ProjectSpaceNotFoundException.php`

- [ ] **Step 1: Create `ProjectIdeaNotFoundException.php`**

Create `public/packages/inno_project_idea/src/Domain/Exception/ProjectIdeaNotFoundException.php`:

```php
<?php

declare(strict_types=1);

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

- [ ] **Step 2: Create `InvalidProjectIdeaException.php`**

Create `public/packages/inno_project_idea/src/Domain/Exception/InvalidProjectIdeaException.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InvalidProjectIdeaException extends ValidationException
{
    public static function emptyTitle(): self
    {
        return new self('Project idea title cannot be empty.', ['field' => 'title']);
    }

    public static function emptyDescription(): self
    {
        return new self('Project idea description cannot be empty.', ['field' => 'description']);
    }
}
```

- [ ] **Step 3: Create `ModerationReviewNotFoundException.php`**

Create `public/packages/inno_moderation/src/Domain/Exception/ModerationReviewNotFoundException.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;

class ModerationReviewNotFoundException extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self("ModerationReview '{$id}' not found.", ['reviewId' => $id]);
    }
}
```

- [ ] **Step 4: Create `InvalidModerationDecisionException.php`**

Create `public/packages/inno_moderation/src/Domain/Exception/InvalidModerationDecisionException.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InvalidModerationDecisionException extends ValidationException
{
    public static function emptyNotes(): self
    {
        return new self('Moderation review notes cannot be empty.', ['field' => 'notes']);
    }
}
```

- [ ] **Step 5: Create `ProjectSpaceNotFoundException.php`**

Create `public/packages/inno_project_space/src/Domain/Exception/ProjectSpaceNotFoundException.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;

class ProjectSpaceNotFoundException extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self("ProjectSpace '{$id}' not found.", ['spaceId' => $id]);
    }
}
```

- [ ] **Step 6: Run all tests**

```bash
composer test:unit
```

Expected: all pass (new classes have no tests yet — they are simple subclasses).

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_project_idea/src/Domain/Exception/ \
  public/packages/inno_moderation/src/Domain/Exception/ \
  public/packages/inno_project_space/src/Domain/Exception/
git commit -m "feat: add domain-specific exception subclasses per package"
```

---

## Task 13: Integrate Cross-Cutting Concerns into Handlers

**Files:**
- Modify: `public/packages/inno_project_idea/src/Application/UseCase/SubmitProjectIdea/SubmitProjectIdeaHandler.php`
- Modify: `public/packages/inno_project_idea/src/Application/EventHandler/OnModerationDecisionMade.php`
- Modify: `public/packages/inno_moderation/src/Application/UseCase/ReviewProjectIdea/ReviewProjectIdeaHandler.php`
- Modify: `public/packages/inno_moderation/src/Application/EventHandler/OnProjectIdeaSubmitted.php`
- Modify: `public/packages/inno_project_space/src/Application/EventHandler/OnProjectIdeaApproved.php`
- Modify: `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaSubmitted.php`
- Modify: `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaApproved.php`
- Modify: `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaRejected.php`
- Modify: all corresponding test files

- [ ] **Step 1: Update `SubmitProjectIdeaHandler.php`**

Replace full content of `public/packages/inno_project_idea/src/Application/UseCase/SubmitProjectIdea/SubmitProjectIdeaHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea;

use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\In\SubmitProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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

            $this->eventDispatcher->dispatch(
                new ProjectIdeaSubmitted($idea->getId(), $idea->getSubmitterId(), $idea->getTitle()),
                ProjectIdeaSubmitted::NAME,
            );

            $this->logger->info('Project idea submitted', [
                'ideaId' => $idea->getId(),
                'submitterId' => $idea->getSubmitterId(),
            ]);

            return $idea;
        });
    }
}
```

- [ ] **Step 2: Update `ReviewProjectIdeaHandler.php`**

Replace full content of `public/packages/inno_moderation/src/Application/UseCase/ReviewProjectIdea/ReviewProjectIdeaHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea;

use Concrete\Package\InnoModeration\Domain\Exception\ModerationReviewNotFoundException;
use Concrete\Package\InnoModeration\Port\In\ReviewProjectIdeaPort;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ReviewProjectIdeaHandler implements ReviewProjectIdeaPort
{
    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ReviewProjectIdeaCommand $command): void
    {
        $this->transaction->transactional(function () use ($command) {
            $review = $this->repository->findById($command->reviewId);
            if ($review === null) {
                throw ModerationReviewNotFoundException::withId($command->reviewId);
            }

            if ($command->approved) {
                $review->approve($command->reviewerId, $command->notes);
            } else {
                $review->reject($command->reviewerId, $command->notes);
            }

            $this->repository->save($review);

            $this->eventDispatcher->dispatch(
                new ModerationDecisionMade(
                    ideaId: $review->getIdeaId(),
                    reviewerId: $command->reviewerId,
                    approved: $command->approved,
                    reason: $command->notes,
                ),
                ModerationDecisionMade::NAME,
            );

            $this->logger->info('Moderation decision made', [
                'reviewId' => $command->reviewId,
                'approved' => $command->approved,
                'reviewerId' => $command->reviewerId,
            ]);
        });
    }
}
```

- [ ] **Step 3: Update `OnModerationDecisionMade.php`**

Replace full content of `public/packages/inno_project_idea/src/Application/EventHandler/OnModerationDecisionMade.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\EventHandler;

use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OnModerationDecisionMade
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ModerationDecisionMade $event): void
    {
        $idea = $this->repository->findById($event->getIdeaId());
        if ($idea === null) {
            $this->logger->warning('Idea not found for moderation decision', [
                'ideaId' => $event->getIdeaId(),
            ]);

            return;
        }

        if ($event->isApproved()) {
            $idea->approve();
            $this->repository->save($idea);
            $this->eventDispatcher->dispatch(
                new ProjectIdeaApproved($idea->getId(), $idea->getSubmitterId()),
                ProjectIdeaApproved::NAME,
            );
            $this->logger->info('Project idea approved', ['ideaId' => $idea->getId()]);
        } else {
            $idea->reject();
            $this->repository->save($idea);
            $this->eventDispatcher->dispatch(
                new ProjectIdeaRejected($idea->getId(), $idea->getSubmitterId(), $event->getReason()),
                ProjectIdeaRejected::NAME,
            );
            $this->logger->info('Project idea rejected', ['ideaId' => $idea->getId()]);
        }
    }
}
```

- [ ] **Step 4: Update `inno_moderation/EventHandler/OnProjectIdeaSubmitted.php`**

Replace full content of `public/packages/inno_moderation/src/Application/EventHandler/OnProjectIdeaSubmitted.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\EventHandler;

use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class OnProjectIdeaSubmitted
{
    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectIdeaSubmitted $event): void
    {
        $review = ModerationReview::createPending(
            id: uniqid('review_', true),
            ideaId: $event->getIdeaId(),
        );
        $this->repository->save($review);

        $this->logger->info('Pending moderation review created', [
            'reviewId' => $review->getId(),
            'ideaId' => $event->getIdeaId(),
        ]);
    }
}
```

- [ ] **Step 5: Update `inno_project_space/EventHandler/OnProjectIdeaApproved.php`**

Replace full content of `public/packages/inno_project_space/src/Application/EventHandler/OnProjectIdeaApproved.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\EventHandler;

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCreated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OnProjectIdeaApproved
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectIdeaApproved $event): void
    {
        $space = ProjectSpace::createFromApprovedIdea(
            id: uniqid('space_', true),
            projectIdeaId: $event->getIdeaId(),
            title: "Project Space – {$event->getIdeaId()}",
            initiatorId: $event->getSubmitterId(),
        );

        $this->repository->save($space);

        $this->eventDispatcher->dispatch(
            new ProjectSpaceCreated($space->getId(), $event->getIdeaId(), $event->getSubmitterId()),
            ProjectSpaceCreated::NAME,
        );

        $this->logger->info('Project space created', [
            'spaceId' => $space->getId(),
            'ideaId' => $event->getIdeaId(),
        ]);
    }
}
```

- [ ] **Step 6: Update `inno_notification/EventHandler/OnProjectIdeaSubmitted.php`**

Replace full content of `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaSubmitted.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class OnProjectIdeaSubmitted
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly ConfigInterface $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectIdeaSubmitted $event): void
    {
        $adminEmail = $this->config->getRequired('notification.admin_email');

        $this->emailService->send(
            to: $adminEmail,
            subject: 'Neue Projektidee eingereicht: ' . $event->getTitle(),
            body: "Eine neue Projektidee wurde eingereicht und wartet auf Moderation.\n\nID: {$event->getIdeaId()}",
        );

        $this->logger->info('Admin notified about new idea', [
            'ideaId' => $event->getIdeaId(),
            'to' => $adminEmail,
        ]);
    }
}
```

- [ ] **Step 7: Update `inno_notification/EventHandler/OnProjectIdeaApproved.php`**

Replace full content of `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaApproved.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class OnProjectIdeaApproved
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectIdeaApproved $event): void
    {
        // TODO: resolve submitter email via AuthInterface or UserInfo service
        $submitterEmail = "user-{$event->getSubmitterId()}@example.com";

        $this->emailService->send(
            to: $submitterEmail,
            subject: 'Deine Projektidee wurde freigegeben!',
            body: "Gratulation! Deine Projektidee wurde freigegeben. Ein Projektraum wurde für dich erstellt.\n\nIdeenID: {$event->getIdeaId()}",
        );

        $this->logger->info('Submitter notified about approval', [
            'ideaId' => $event->getIdeaId(),
            'submitterId' => $event->getSubmitterId(),
        ]);
    }
}
```

- [ ] **Step 8: Update `inno_notification/EventHandler/OnProjectIdeaRejected.php`**

Replace full content of `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaRejected.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class OnProjectIdeaRejected
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectIdeaRejected $event): void
    {
        // TODO: resolve submitter email via AuthInterface or UserInfo service
        $submitterEmail = "user-{$event->getSubmitterId()}@example.com";

        $this->emailService->send(
            to: $submitterEmail,
            subject: 'Projektidee konnte nicht freigegeben werden',
            body: "Leider wurde deine Projektidee nicht freigegeben.\n\nGrund: {$event->getReason()}\n\nIdeenID: {$event->getIdeaId()}",
        );

        $this->logger->info('Submitter notified about rejection', [
            'ideaId' => $event->getIdeaId(),
            'submitterId' => $event->getSubmitterId(),
        ]);
    }
}
```

- [ ] **Step 9: Update `SubmitProjectIdeaHandlerTest.php`**

Replace full content of `tests/unit/inno_project_idea/Application/SubmitProjectIdeaHandlerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn () => Mockery::close());

test('handle() saves the idea and dispatches ProjectIdeaSubmitted within transaction', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('save')->once()->with(Mockery::type(ProjectIdea::class));
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(ProjectIdeaSubmitted::class), ProjectIdeaSubmitted::NAME);
    $transaction->shouldReceive('transactional')
        ->once()
        ->andReturnUsing(fn (callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new SubmitProjectIdeaHandler($repo, $dispatcher, $transaction, $logger);
    $idea = $handler->handle(new SubmitProjectIdeaCommand(
        title: 'My Idea', description: 'Great description', category: 'tech', submitterId: 'user_1',
    ));
    expect($idea->getTitle())->toBe('My Idea')
        ->and($idea->getSubmitterId())->toBe('user_1');
});

test('handle() returns ProjectIdea with a non-empty id', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('save')->once();
    $dispatcher->shouldReceive('dispatch')->once();
    $transaction->shouldReceive('transactional')->andReturnUsing(fn (callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new SubmitProjectIdeaHandler($repo, $dispatcher, $transaction, $logger);
    $idea = $handler->handle(new SubmitProjectIdeaCommand('Title', 'Desc', 'cat', 'user_1'));
    expect($idea->getId())->not->toBeEmpty();
});
```

- [ ] **Step 10: Update `ReviewProjectIdeaHandlerTest.php`**

Replace full content of `tests/unit/inno_moderation/Application/ReviewProjectIdeaHandlerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaHandler;
use Concrete\Package\InnoModeration\Domain\Exception\ModerationReviewNotFoundException;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn () => Mockery::close());

test('handle() dispatches ModerationDecisionMade with approved=true', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $repo = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('review_1')->andReturn($review);
    $repo->shouldReceive('save')->once();
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(
            Mockery::on(fn ($e) => $e instanceof ModerationDecisionMade && $e->isApproved()),
            ModerationDecisionMade::NAME
        );
    $transaction->shouldReceive('transactional')->andReturnUsing(fn (callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new ReviewProjectIdeaHandler($repo, $dispatcher, $transaction, $logger);
    $handler->handle(new ReviewProjectIdeaCommand('review_1', 'mod_1', true, 'Looks good'));
});

test('handle() throws ModerationReviewNotFoundException when review not found', function () {
    $repo = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('missing_id')->andReturn(null);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn (callable $fn) => $fn());

    $handler = new ReviewProjectIdeaHandler($repo, $dispatcher, $transaction, $logger);
    $handler->handle(new ReviewProjectIdeaCommand('missing_id', 'mod_1', true, ''));
})->throws(ModerationReviewNotFoundException::class);
```

- [ ] **Step 11: Update `OnProjectIdeaSubmittedTest.php` (notification)**

Replace full content of `tests/unit/inno_notification/Application/OnProjectIdeaSubmittedTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaSubmitted;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

afterEach(fn () => Mockery::close());

test('handle() sends email to admin from config', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $config = Mockery::mock(ConfigInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $config->shouldReceive('getRequired')
        ->with('notification.admin_email')
        ->andReturn('admin@innovationsbox.ch');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'admin@innovationsbox.ch',
            Mockery::on(fn ($s) => str_contains($s, 'Solar Panels')),
            Mockery::type('string'),
        );

    $logger->shouldReceive('info')->once();

    $handler = new OnProjectIdeaSubmitted($emailService, $config, $logger);
    $handler->handle(new ProjectIdeaSubmitted('idea_1', 'user_1', 'Solar Panels'));
});
```

- [ ] **Step 12: Run all tests**

```bash
composer test:unit
```

Expected: all tests pass.

- [ ] **Step 13: Commit**

```bash
git add public/packages/inno_project_idea/src/Application/ \
  public/packages/inno_moderation/src/Application/ \
  public/packages/inno_notification/src/Application/ \
  public/packages/inno_project_space/src/Application/ \
  tests/unit/
git commit -m "feat: integrate logging, transactions, config, and typed exceptions into all handlers"
```

---

## Task 14: Integrate Auth into Controllers

**Files:**
- Modify: `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php`
- Modify: `public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php`
- Modify: `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`

- [ ] **Step 1: Update `ProjectIdeaApiController.php`**

Replace full content of `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Port\In\SubmitProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProjectIdeaApiController extends AbstractController
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly SubmitProjectIdeaPort $submitPort,
        private readonly AuthInterface $auth,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ideas = $this->repository->findAll();

        return new JsonResponse(['data' => array_map(
            fn ($idea) => [
                'id' => $idea->getId(),
                'title' => $idea->getTitle(),
                'status' => $idea->getStatus()->value,
            ],
            $ideas,
        )]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->auth->hasPermission('idea.submit')) {
            throw new AuthException(
                'Permission denied for idea.submit',
                ['permission' => 'idea.submit'],
                $this->auth->isAuthenticated(),
            );
        }

        $body = json_decode($request->getContent(), true) ?? [];

        $idea = $this->submitPort->handle(new SubmitProjectIdeaCommand(
            title: $body['title'] ?? '',
            description: $body['description'] ?? '',
            category: $body['category'] ?? '',
            submitterId: (string) $this->auth->getUserId(),
        ));

        return new JsonResponse(['data' => ['id' => $idea->getId()]], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $idea = $this->repository->findById($id);
        if ($idea === null) {
            throw ProjectIdeaNotFoundException::withId($id);
        }

        return new JsonResponse(['data' => [
            'id' => $idea->getId(),
            'title' => $idea->getTitle(),
            'description' => $idea->getDescription(),
            'category' => $idea->getCategory(),
            'status' => $idea->getStatus()->value,
            'submitterId' => $idea->getSubmitterId(),
        ]]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        // TODO: implement update logic
        return new JsonResponse(['error' => 'Not implemented'], 501);
    }
}
```

- [ ] **Step 2: Update `ModerationApiController.php`**

Replace full content of `public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaCommand;
use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Port\In\ReviewProjectIdeaPort;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ModerationApiController extends AbstractController
{
    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
        private readonly ReviewProjectIdeaPort $reviewPort,
        private readonly AuthInterface $auth,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->requirePermission('moderation.review');

        $reviews = $this->repository->findByDecision(ModerationDecision::Pending);

        return new JsonResponse(['data' => array_map(
            fn ($r) => ['id' => $r->getId(), 'ideaId' => $r->getIdeaId()],
            $reviews,
        )]);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $this->requirePermission('moderation.review');

        $body = json_decode($request->getContent(), true) ?? [];
        $this->reviewPort->handle(new ReviewProjectIdeaCommand(
            reviewId: $id,
            reviewerId: (string) $this->auth->getUserId(),
            approved: true,
            notes: $body['notes'] ?? '',
        ));

        return new JsonResponse(['status' => 'approved']);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $this->requirePermission('moderation.review');

        $body = json_decode($request->getContent(), true) ?? [];
        $this->reviewPort->handle(new ReviewProjectIdeaCommand(
            reviewId: $id,
            reviewerId: (string) $this->auth->getUserId(),
            approved: false,
            notes: $body['notes'] ?? '',
        ));

        return new JsonResponse(['status' => 'rejected']);
    }

    private function requirePermission(string $permission): void
    {
        if (!$this->auth->hasPermission($permission)) {
            throw new AuthException(
                "Permission denied for {$permission}",
                ['permission' => $permission],
                $this->auth->isAuthenticated(),
            );
        }
    }
}
```

- [ ] **Step 3: Update `ProjectSpaceApiController.php`**

Replace full content of `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProjectSpaceApiController extends AbstractController
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => []]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $space = $this->repository->findById($id);
        if ($space === null) {
            throw ProjectSpaceNotFoundException::withId($id);
        }

        return new JsonResponse(['data' => [
            'id' => $space->getId(),
            'projectIdeaId' => $space->getProjectIdeaId(),
            'members' => $space->getMemberIds(),
        ]]);
    }

    public function join(Request $request, string $id): JsonResponse
    {
        if (!$this->auth->hasPermission('space.join')) {
            throw new AuthException(
                'Permission denied for space.join',
                ['permission' => 'space.join'],
                $this->auth->isAuthenticated(),
            );
        }

        $space = $this->repository->findById($id);
        if ($space === null) {
            throw ProjectSpaceNotFoundException::withId($id);
        }

        $space->addMember((string) $this->auth->getUserId());
        $this->repository->save($space);

        return new JsonResponse(['status' => 'joined']);
    }

    public function invite(Request $request, string $id): JsonResponse
    {
        // TODO: implement invite flow
        return new JsonResponse(['error' => 'Not implemented'], 501);
    }
}
```

- [ ] **Step 4: Run all tests**

```bash
composer test:unit
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_project_idea/src/Adapter/In/Http/ \
  public/packages/inno_moderation/src/Adapter/In/Http/ \
  public/packages/inno_project_space/src/Adapter/In/Http/
git commit -m "feat: add auth permission checks to all API controllers, use typed exceptions"
```

---

## Task 15: Run Full Test Suite & Final Verification

**Files:** None — verification only.

- [ ] **Step 1: Run full unit test suite**

```bash
composer test:unit
```

Expected: all tests pass.

- [ ] **Step 2: Run linting**

```bash
composer lint:php
```

Expected: no errors. If PHP CS Fixer reports issues, fix with:

```bash
composer lint:cs:fix
```

- [ ] **Step 3: Verify coverage**

```bash
composer test:coverage
```

Expected: >= 80% coverage on all `inno_*/src/` packages.

- [ ] **Step 4: Final commit if any lint fixes**

```bash
git add -A
git commit -m "style: apply code formatting fixes"
```

- [ ] **Step 5: Run all tests one final time**

```bash
composer test:unit
```

Expected: all tests pass, clean working tree.
