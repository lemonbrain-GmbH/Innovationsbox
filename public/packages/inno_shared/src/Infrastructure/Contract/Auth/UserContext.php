<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Auth;

final class UserContext
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $name,
        public readonly array $roles,
    ) {}
}
