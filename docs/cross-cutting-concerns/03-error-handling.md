# Error Handling

## Overview

All application errors are represented as typed exceptions with HTTP status codes and safe user-facing messages. Controllers throw; a global handler catches. No domain or application code formats HTTP responses directly.

## Exception Hierarchy

```
BaseException (abstract)
  |
  +-- ValidationException      HTTP 400  — invalid input, constraint violations
  |
  +-- NotFoundException        HTTP 404  — requested resource does not exist
  |
  +-- AuthException            HTTP 401 / 403  — not authenticated or not authorised
  |
  +-- InfrastructureException  HTTP 500  — external service failure, config missing, DB error
```

All four concrete classes extend the abstract `BaseException`.

### BaseException

```php
namespace Concrete\Package\InnoShared\Infrastructure\Exception;

abstract class BaseException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly array $context = [],
        private readonly ?string $userMessage = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Structured data attached to the exception for logging.
     * Never exposed to end users.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * HTTP status code to send in the response.
     */
    abstract public function getHttpStatusCode(): int;

    /**
     * Safe message shown to the end user.
     * Falls back to a generic message if none was provided.
     */
    public function getUserMessage(): string
    {
        return $this->userMessage ?? $this->defaultUserMessage();
    }

    protected function defaultUserMessage(): string
    {
        return 'An unexpected error occurred.';
    }
}
```

### AuthException

`AuthException` toggles between 401 (not authenticated) and 403 (authenticated but not authorised) based on the `$authenticated` flag:

```php
namespace Concrete\Package\InnoShared\Infrastructure\Exception;

final class AuthException extends BaseException
{
    public function __construct(
        string $message,
        private readonly bool $authenticated = false,
        array $context = [],
        ?string $userMessage = null,
    ) {
        parent::__construct($message, $context, $userMessage);
    }

    public function getHttpStatusCode(): int
    {
        // 403 Forbidden if the user is known but lacks permission.
        // 401 Unauthorized if the user is not authenticated at all.
        return $this->authenticated ? 403 : 401;
    }

    protected function defaultUserMessage(): string
    {
        return $this->authenticated
            ? 'You do not have permission to perform this action.'
            : 'Authentication is required.';
    }
}
```

## Domain-Specific Subclasses

Domain packages extend the shared exceptions to add entity-specific factory methods:

```php
// In inno_project_idea:
namespace Concrete\Package\InnoProjectIdea\Infrastructure\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;

final class ProjectIdeaNotFoundException extends NotFoundException
{
    public static function withId(int $id): self
    {
        return new self(
            message: sprintf('ProjectIdea with id %d not found.', $id),
            context: ['idea_id' => $id],
            userMessage: 'The requested project idea could not be found.',
        );
    }
}

// In inno_moderation:
final class ModerationReviewNotFoundException extends NotFoundException
{
    public static function withId(int $id): self { ... }
}
```

Named constructors keep throw sites readable: `throw ProjectIdeaNotFoundException::withId($id);`

## ExceptionHandlerInterface

```php
namespace Concrete\Package\InnoShared\Infrastructure\Contract;

use Symfony\Component\HttpFoundation\JsonResponse;

interface ExceptionHandlerInterface
{
    /**
     * Convert any throwable into a JSON response.
     * Logs the exception and determines the appropriate HTTP status code.
     */
    public function toJsonResponse(\Throwable $e): JsonResponse;
}
```

## Adapter

`ConcreteCmsExceptionHandler` implements `ExceptionHandlerInterface`:

- **Known errors** (`BaseException`): logged at `warning` level with `getContext()` as structured context.
- **Unknown errors** (`\Throwable`): logged at `critical` level. HTTP 500 is returned.
- **Debug mode**: when `app.debug = true` (development environment), the response includes `exception_class`, `file`, `line`, and `trace`. In production these fields are omitted.

```php
namespace Concrete\Package\InnoShared\Infrastructure\Adapter;

use Concrete\Package\InnoShared\Infrastructure\Contract\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\BaseException;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ConcreteCmsExceptionHandler implements ExceptionHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debug,
    ) {}

    public function toJsonResponse(\Throwable $e): JsonResponse
    {
        if ($e instanceof BaseException) {
            $this->logger->warning($e->getMessage(), $e->getContext());
            $status = $e->getHttpStatusCode();
            $userMessage = $e->getUserMessage();
        } else {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $status = 500;
            $userMessage = 'An unexpected error occurred.';
        }

        $body = ['error' => ['message' => $userMessage]];

        if ($this->debug) {
            $body['debug'] = [
                'exception_class' => $e::class,
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
                'trace'           => $e->getTraceAsString(),
            ];
        }

        return new JsonResponse($body, $status);
    }
}
```

## JSON Error Response Format

**Production:**

```json
{
  "error": {
    "message": "The requested project idea could not be found."
  }
}
```

**Development (`app.debug = true`):**

```json
{
  "error": {
    "message": "The requested project idea could not be found."
  },
  "debug": {
    "exception_class": "Concrete\\Package\\InnoProjectIdea\\Infrastructure\\Exception\\ProjectIdeaNotFoundException",
    "file": "/var/www/public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaController.php",
    "line": 42,
    "trace": "..."
  }
}
```

## Global Handler Registration

The handler is registered in `inno_shared/controller.php` via Concrete CMS's `on_before_render` event, scoped to `/api/*` routes:

```php
// public/packages/inno_shared/controller.php

public function on_start(): void
{
    // ... DI bindings ...

    $app = $this->app;

    \Concrete\Core\Support\Facade\Events::addListener(
        'on_before_render',
        static function () use ($app): void {
            $request = $app->make(\Concrete\Core\Http\Request::class);

            if (!str_starts_with($request->getPathInfo(), '/api/')) {
                return;
            }

            set_exception_handler(
                static function (\Throwable $e) use ($app): void {
                    $handler  = $app->make(ExceptionHandlerInterface::class);
                    $response = $handler->toJsonResponse($e);
                    $response->send();
                    exit;
                }
            );
        }
    );
}
```

## Usage in Controllers

Controllers throw domain exceptions. They never catch or format responses themselves:

```php
namespace Concrete\Package\InnoProjectIdea\Adapter\In\Http;

use Concrete\Package\InnoProjectIdea\Infrastructure\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Port\In\GetProjectIdeaPort;

final class ProjectIdeaController extends AbstractController
{
    public function __construct(private readonly GetProjectIdeaPort $getProjectIdea) {}

    public function show(int $id): void
    {
        // Throws ProjectIdeaNotFoundException (-> NotFoundException -> BaseException)
        // if the idea does not exist. The global handler converts it to HTTP 404 JSON.
        $idea = $this->getProjectIdea->execute($id);

        $this->renderJson($idea->toArray());
    }
}
```

The global handler catches any uncaught `\Throwable` on `/api/*` routes and returns a well-formed JSON error response. Non-API routes fall through to Concrete CMS's default error pages.
