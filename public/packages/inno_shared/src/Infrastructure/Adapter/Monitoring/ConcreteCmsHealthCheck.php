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
