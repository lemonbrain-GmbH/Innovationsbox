<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Auth;

interface SessionInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    public function invalidate(): void;
}
