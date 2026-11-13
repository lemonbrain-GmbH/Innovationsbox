<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Config;

use Concrete\Package\InnoShared\Infrastructure\Exception\InfrastructureException;

interface ConfigInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    /**
     * @throws InfrastructureException if key is missing
     */
    public function getRequired(string $key): mixed;

    public function set(string $key, mixed $value): void;
}
