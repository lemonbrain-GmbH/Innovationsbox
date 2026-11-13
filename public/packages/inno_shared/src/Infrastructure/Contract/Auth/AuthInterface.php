<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Auth;

interface AuthInterface
{
    public function getCurrentUser(): ?UserContext;

    public function isAuthenticated(): bool;

    public function hasPermission(string $permission): bool;

    public function hasRole(string $role): bool;

    public function getUserId(): ?int;
}
