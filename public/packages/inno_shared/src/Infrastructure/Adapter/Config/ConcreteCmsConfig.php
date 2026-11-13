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
