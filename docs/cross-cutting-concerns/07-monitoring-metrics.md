# 07 Monitoring & Metrics

Monitoring is a cross-cutting concern. Domain handlers and adapters report performance counters, gauges, and events through `MetricsInterface`. System health is exposed through `HealthCheckInterface`. Both are decoupled from any concrete observability backend.

## Interfaces

```php
namespace Innovationsbox\Shared\Infrastructure\Contract\Monitoring;

interface MetricsInterface
{
    /** Increment a counter by $value. */
    public function increment(string $counter, int $value = 1, array $tags = []): void;

    /** Record a gauge (current value, not cumulative). */
    public function gauge(string $name, float $value, array $tags = []): void;

    /** Record a duration in milliseconds. */
    public function timing(string $name, float $milliseconds, array $tags = []): void;

    /**
     * Execute $operation, record its wall-clock time as a timing metric, and return the result.
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function measure(string $name, callable $operation, array $tags = []): mixed;

    /** Record a discrete event with optional structured context. */
    public function event(string $name, array $context = []): void;
}

interface HealthCheckInterface
{
    /** @return array<string, HealthStatus> */
    public function check(): array;
}

enum HealthStatus: string
{
    case Healthy   = 'healthy';
    case Degraded  = 'degraded';
    case Unhealthy = 'unhealthy';
}
```

## Adapter: LogBasedMetrics

`Innovationsbox\Shared\Infrastructure\Adapter\Monitoring\LogBasedMetrics` writes all metrics as structured log entries via `LoggerInterface`. No external infrastructure is required.

Log format (parseable by Loki, ELK, and similar aggregators):

```
[INFO] metric.counter | name=idea.submitted value=1 status=draft
[INFO] metric.gauge   | name=queue.depth value=14
[INFO] metric.timing  | name=handler.duration ms=142.5 handler=SubmitProjectIdea
[INFO] metric.event   | name=idea.submitted ideaId=abc-123 submitterId=42
```

Each call maps to an `info`-level log entry with a `metric.*` channel prefix and the tag array merged into the log context.

## Adapter: ConcreteCmsHealthCheck

`Innovationsbox\Shared\Infrastructure\Adapter\Monitoring\ConcreteCmsHealthCheck` checks each system dependency and returns a status map.

| Service | Healthy | Degraded | Unhealthy |
|---|---|---|---|
| Database | `SELECT 1` completes in < 100 ms | Completes in < 1000 ms | Times out or throws |
| CMS | `Config::get('concrete.version')` returns a value | — | Throws or returns null |

## DI Bindings

```php
// public/packages/inno_shared/controller.php

$this->app->bind(
    \Innovationsbox\Shared\Infrastructure\Contract\Monitoring\MetricsInterface::class,
    \Innovationsbox\Shared\Infrastructure\Adapter\Monitoring\LogBasedMetrics::class
);

$this->app->bind(
    \Innovationsbox\Shared\Infrastructure\Contract\Monitoring\HealthCheckInterface::class,
    \Innovationsbox\Shared\Infrastructure\Adapter\Monitoring\ConcreteCmsHealthCheck::class
);
```

## Health Endpoint

`GET /api/health` is registered by `inno_shared`. Enable or disable it via the `monitoring.health_enabled` config key.

**Response — all healthy (HTTP 200):**

```json
{
    "status": "healthy",
    "services": {
        "database": "healthy",
        "cms": "healthy"
    },
    "timestamp": "2026-04-17T10:30:00+00:00"
}
```

**Response — any service unhealthy (HTTP 503):**

```json
{
    "status": "unhealthy",
    "services": {
        "database": "unhealthy",
        "cms": "healthy"
    },
    "timestamp": "2026-04-17T10:30:00+00:00"
}
```

The overall `status` field is the worst status across all services: `unhealthy` > `degraded` > `healthy`.

## Instrumented Metrics

| Metric name | Type | Instrumented in |
|---|---|---|
| `request.duration` | timing | Exception handler middleware |
| `handler.duration` | timing | Each use case handler |
| `db.query.count` | counter | Transaction adapter |
| `cache.hit` | counter | Cache adapter (`getOrSet` hit) |
| `cache.miss` | counter | Cache adapter (`getOrSet` miss) |
| `idea.submitted` | counter | `SubmitProjectIdeaHandler` |
| `idea.approved` | counter | `OnModerationDecisionMade` |
| `idea.rejected` | counter | `OnModerationDecisionMade` |
| `auth.failure` | counter | Auth adapter |
| `email.sent` | counter | Email adapter |
| `email.failed` | counter | Email adapter |
| `event.dispatched` | counter | Event dispatcher wrapper |

## Future APM Integration

`LogBasedMetrics` is the current adapter. When a dedicated APM tool (Datadog, New Relic, OpenTelemetry) is adopted, write a new class implementing `MetricsInterface` and change one DI binding in `inno_shared/controller.php`. No other code changes.

## Usage Example

Inject `MetricsInterface` via constructor and use `measure()` to time the critical path, `increment()` for domain counters, and `event()` for audit-style records.

```php
namespace Innovationsbox\ProjectIdea\Application\UseCase;

use Innovationsbox\Shared\Infrastructure\Contract\Monitoring\MetricsInterface;
use Innovationsbox\Shared\Infrastructure\Contract\Transaction\TransactionInterface;
use Innovationsbox\Shared\Infrastructure\Contract\Logger\LoggerInterface;
use Innovationsbox\Shared\Domain\Event\ProjectIdeaSubmitted;

final class SubmitProjectIdeaHandler implements SubmitProjectIdeaPort
{
    public function __construct(
        private TransactionInterface $transaction,
        private ProjectIdeaRepositoryInterface $repository,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private MetricsInterface $metrics,
    ) {}

    public function handle(SubmitProjectIdeaCommand $command): ProjectIdea
    {
        return $this->metrics->measure('handler.duration', function () use ($command) {
            $idea = $this->transaction->transactional(function () use ($command) {
                $idea = ProjectIdea::submit(
                    $command->title,
                    $command->description,
                    $command->submitterId,
                );
                $this->repository->save($idea);
                $this->eventDispatcher->dispatch(
                    new ProjectIdeaSubmitted($idea->getId(), $idea->getSubmitterId())
                );
                return $idea;
            });

            $this->metrics->increment('idea.submitted', tags: ['status' => 'draft']);
            $this->metrics->event('idea.submitted', [
                'ideaId'      => $idea->getId(),
                'submitterId' => $idea->getSubmitterId(),
            ]);
            $this->logger->info('Project idea submitted', ['ideaId' => $idea->getId()]);

            return $idea;
        }, ['handler' => 'SubmitProjectIdea']);
    }
}
```
