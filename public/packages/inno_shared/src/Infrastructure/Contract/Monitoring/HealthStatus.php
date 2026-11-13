<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring;

enum HealthStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unhealthy = 'unhealthy';
}
