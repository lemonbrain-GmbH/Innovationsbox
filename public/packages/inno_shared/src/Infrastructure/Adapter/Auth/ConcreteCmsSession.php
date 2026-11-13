<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Auth;

use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class ConcreteCmsSession implements SessionInterface
{
    public function __construct(private readonly Session $session) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->session->set($key, $value);
    }

    public function remove(string $key): void
    {
        $this->session->remove($key);
    }

    public function invalidate(): void
    {
        $this->session->invalidate();
    }
}
