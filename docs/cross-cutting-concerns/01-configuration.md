# Configuration

## Overview

The configuration concern provides a single, typed access point for all project settings. Domain and application code never touch the Concrete CMS `Config` facade directly — they depend on `ConfigInterface`, which is implemented by `ConcreteCmsConfig`.

## Interface

```php
namespace Concrete\Package\InnoShared\Infrastructure\Contract;

interface ConfigInterface
{
    /**
     * Return the value for the given key, or $default if the key does not exist.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Return true if the key exists and is not null.
     */
    public function has(string $key): bool;

    /**
     * Return the value for the given key.
     * Throws InfrastructureException if the key is missing or null.
     */
    public function getRequired(string $key): mixed;

    /**
     * Write a value at runtime (not persisted to disk).
     */
    public function set(string $key, mixed $value): void;
}
```

## Adapter

`ConcreteCmsConfig` wraps `Concrete\Core\Config\Repository\Repository` and automatically prefixes every key with `innovationsbox.` so callers never need to repeat the namespace:

```php
namespace Concrete\Package\InnoShared\Infrastructure\Adapter;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Package\InnoShared\Infrastructure\Contract\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\InfrastructureException;

final class ConcreteCmsConfig implements ConfigInterface
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
        $value = $this->get($key);

        if ($value === null) {
            throw new InfrastructureException(
                sprintf('Required configuration key "%s" is missing.', $key)
            );
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $this->repository->set(self::PREFIX . $key, $value);
    }
}
```

## Project Configuration Keys

All keys are stored under the `innovationsbox.` namespace in the Concrete CMS config store. Use the short form (without prefix) when calling through `ConfigInterface`.

| Key | Type | Description | Sensitive |
|-----|------|-------------|-----------|
| `notification.admin_email` | string | Recipient address for admin notifications | No |
| `notification.from_email` | string | Sender address for outgoing emails | No |
| `logging.level` | string | Minimum log level (`debug`, `info`, `warning`, `error`) | No |
| `logging.stdout_enabled` | bool | Also write log output to stdout | No |
| `cache.default_ttl` | int | Default cache TTL in seconds | No |
| `monitoring.health_enabled` | bool | Enable the `/api/health` endpoint | No |
| `auth.session_timeout` | int | Idle session timeout in seconds | No |
| `auth.max_failed_logins` | int | Failed login attempts before lockout | No |

## What the CMS Provides

- **Config facade** — `Concrete\Core\Support\Facade\Config` backed by the `Repository` service; supports dot-notation keys, environment-specific overrides.
- **.env support** — `symfony/dotenv` is used to load `.env` into `$_ENV`; Concrete CMS picks up `DB_*` and `CONCRETE5_ENV` automatically. Custom application values should be read via `ConfigInterface`, not `$_ENV`, so they go through the prefix and type-safety layer.
- **Environment-based loading** — when `CONCRETE5_ENV=live`, `application/config/live.database.php` is merged on top of the base config. The same mechanism works for any named environment.

## Usage

Inject `ConfigInterface` via the constructor. Never use the CMS `Config` facade in domain or application classes.

```php
namespace Concrete\Package\InnoNotification\Application\UseCase;

use Concrete\Package\InnoShared\Infrastructure\Contract\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\InfrastructureException;

final class SendAdminNotificationHandler
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly MailerInterface $mailer,
    ) {}

    public function handle(SendAdminNotificationCommand $command): void
    {
        // Throws InfrastructureException if the key is absent:
        $adminEmail = $this->config->getRequired('notification.admin_email');

        // Falls back to a safe default if not set:
        $fromEmail = $this->config->get('notification.from_email', 'noreply@example.com');

        $this->mailer->send($adminEmail, $fromEmail, $command->subject, $command->body);
    }
}
```

## Sensitive Values Policy

- Secrets (passwords, API keys, tokens) must **only** be provided via `.env` — never stored in the CMS database config or committed to version control.
- `.env` is listed in `.gitignore`; copy `.env.dist` to create a local `.env`.
- Do not read `$_ENV` directly in application code — wrap sensitive values in a dedicated `ConfigInterface` key and load them through the adapter if they need to be surfaced as config.
- `detect-secrets` runs as a pre-commit hook and in CI Stage 4 to prevent accidental credential commits.
