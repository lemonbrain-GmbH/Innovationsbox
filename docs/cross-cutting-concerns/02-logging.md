# Logging

## Overview

Structured, context-aware logging is provided through a project-defined `LoggerInterface` that extends PSR-3. Each domain package gets its own named channel. Sensitive data is scrubbed before any record leaves the application.

## Interface

```php
namespace Concrete\Package\InnoShared\Infrastructure\Contract;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

interface LoggerInterface extends PsrLoggerInterface
{
    /**
     * Return a new logger instance carrying the given context on every record.
     * The original logger is not mutated.
     */
    public function withContext(array $context): static;
}
```

The interface extends PSR-3 `LoggerInterface`, so all standard methods (`debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`, `log`) are available. `withContext()` is the project addition — it produces a child logger that merges its context into every subsequent log record, which is useful for attaching a request ID or user ID once at handler construction time.

## Adapter

`ConcreteCmsLogger` wraps `Concrete\Core\Logging\Logger` (a Monolog-backed service provided by the CMS).

```php
namespace Concrete\Package\InnoShared\Infrastructure\Adapter;

use Concrete\Core\Logging\LoggerFactory;
use Concrete\Package\InnoShared\Infrastructure\Contract\LoggerInterface;

final class ConcreteCmsLogger implements LoggerInterface
{
    private array $baseContext = [];

    public function __construct(
        private readonly LoggerFactory $factory,
        private readonly string $channel,
    ) {}

    private function logger(): \Monolog\Logger
    {
        return $this->factory->createLogger($this->channel);
    }

    public function withContext(array $context): static
    {
        $clone = clone $this;
        $clone->baseContext = array_merge($this->baseContext, $context);

        return $clone;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger()->log($level, $message, array_merge($this->baseContext, $context));
    }

    // PSR-3 shorthand methods delegate to log() above.
}
```

One channel is created per domain package. The channel name matches the package handle:

| Package | Channel name |
|---------|-------------|
| `inno_project_idea` | `inno_project_idea` |
| `inno_project_space` | `inno_project_space` |
| `inno_moderation` | `inno_moderation` |
| `inno_notification` | `inno_notification` |

## Sensitive Data Processor

`SensitiveDataProcessor` is a Monolog processor attached to every channel. It recursively inspects the `context` and `extra` arrays of each log record and replaces the value of any key whose name matches a sensitive pattern with `[REDACTED]`.

Scrubbed key patterns (case-insensitive substring match):

- `password`
- `token`
- `secret`
- `authorization`
- `cookie`
- `credit_card`

```php
namespace Concrete\Package\InnoShared\Infrastructure\Adapter;

use Monolog\Processor\ProcessorInterface;
use Monolog\LogRecord;

final class SensitiveDataProcessor implements ProcessorInterface
{
    private const SENSITIVE_PATTERNS = [
        'password', 'token', 'secret', 'authorization', 'cookie', 'credit_card',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->scrub($record->context),
            extra: $this->scrub($record->extra),
        );
    }

    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($this->isSensitive((string) $key)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->scrub($value);
            }
        }

        return $data;
    }

    private function isSensitive(string $key): bool
    {
        $lower = strtolower($key);

        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
```

## Log Levels per Environment

| Environment | Minimum level written |
|-------------|----------------------|
| `development` | `debug` |
| `staging` | `info` |
| `production` | `warning` |

Set `logging.level` in the CMS config (or `.env`) to override. Set `logging.stdout_enabled = true` to add a stdout handler (useful in containerised environments).

## When to Log What

| Level | When to use |
|-------|-------------|
| `debug` | Internal state, query parameters, intermediate values — development only |
| `info` | Successful use-case completion (idea submitted, moderation decision recorded) |
| `warning` | Expected failures that are handled: validation errors, not-found responses, auth rejections |
| `error` | Unexpected failures that are caught and recovered from: third-party service timeouts, email delivery failure |
| `critical` | Unrecoverable errors that require immediate attention: DB connection lost, uncaught exceptions reaching the global handler |

Do not log at `critical` for domain errors. Reserve `critical` for infrastructure failures.

## What the CMS Provides

- **LoggerFactory** — `Concrete\Core\Logging\LoggerFactory` creates named Monolog `Logger` instances, each writing to the CMS log store (`Logs` table) and optionally to files under `application/logs/`.
- **Monolog integration** — standard Monolog handlers, processors, and formatters can be attached. `SensitiveDataProcessor` is registered as a processor when the channel is created.

## Usage

Inject `LoggerInterface` via the constructor. Use `withContext()` to attach request-scoped identifiers once, then call log methods as needed.

```php
namespace Concrete\Package\InnoModeration\Application\UseCase;

use Concrete\Package\InnoShared\Infrastructure\Contract\LoggerInterface;

final class RecordModerationDecisionHandler
{
    public function __construct(
        private readonly ModerationRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(RecordModerationDecisionCommand $command): void
    {
        $log = $this->logger->withContext([
            'idea_id'     => $command->ideaId,
            'moderator'   => $command->moderatorId,
            'decision'    => $command->decision->value,
        ]);

        $log->info('Recording moderation decision.');

        try {
            $this->repository->save($command->toEntity());
            $log->info('Moderation decision saved successfully.');
        } catch (\Throwable $e) {
            $log->error('Failed to save moderation decision.', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

The `withContext()` call creates a new logger instance — the original `$this->logger` is unchanged. Structured context keys (snake_case strings) are preferred over embedding values in the message string.
