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
