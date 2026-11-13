<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Port\Out;

interface UserResolverPort
{
    public function resolveEmail(string $userId): ?string;
    public function resolveName(string $userId): string;

    /** @return string[] Email addresses of all users in the given CMS group */
    public function resolveGroupEmails(string $groupName): array;
}
