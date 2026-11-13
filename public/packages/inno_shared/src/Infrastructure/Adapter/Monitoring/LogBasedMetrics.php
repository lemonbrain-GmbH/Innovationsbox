<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Monitoring;

use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring\MetricsInterface;

class LogBasedMetrics implements MetricsInterface
{
    public function __construct(private readonly LoggerInterface $logger) {}

    /**
     * @param array<string, mixed> $tags
     */
    public function increment(string $counter, int $value = 1, array $tags = []): void
    {
        $this->logger->info('metric.counter', array_merge(
            ['name' => $counter, 'value' => $value],
            $tags,
        ));
    }

    /**
     * @param array<string, mixed> $tags
     */
    public function gauge(string $name, float $value, array $tags = []): void
    {
        $this->logger->info('metric.gauge', array_merge(
            ['name' => $name, 'value' => $value],
            $tags,
        ));
    }

    /**
     * @param array<string, mixed> $tags
     */
    public function timing(string $name, float $milliseconds, array $tags = []): void
    {
        $this->logger->info('metric.timing', array_merge(
            ['name' => $name, 'ms' => $milliseconds],
            $tags,
        ));
    }

    /**
     * @param array<string, mixed> $tags
     */
    public function measure(string $name, callable $operation, array $tags = []): mixed
    {
        $start = hrtime(true);
        $result = $operation();
        $ms = (hrtime(true) - $start) / 1_000_000;

        $this->timing($name, $ms, $tags);

        return $result;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function event(string $name, array $context = []): void
    {
        $this->logger->info('metric.event', array_merge(
            ['name' => $name],
            $context,
        ));
    }
}
