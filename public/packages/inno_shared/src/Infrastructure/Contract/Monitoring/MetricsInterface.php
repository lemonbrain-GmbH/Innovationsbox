<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring;

interface MetricsInterface
{
    /**
     * @param array<string, mixed> $tags
     */
    public function increment(string $counter, int $value = 1, array $tags = []): void;

    /**
     * @param array<string, mixed> $tags
     */
    public function gauge(string $name, float $value, array $tags = []): void;

    /**
     * @param array<string, mixed> $tags
     */
    public function timing(string $name, float $milliseconds, array $tags = []): void;

    /**
     * @template T
     * @param callable(): T $operation
     * @param array<string, mixed> $tags
     * @return T
     */
    public function measure(string $name, callable $operation, array $tags = []): mixed;

    /**
     * @param array<string, mixed> $context
     */
    public function event(string $name, array $context = []): void;
}
