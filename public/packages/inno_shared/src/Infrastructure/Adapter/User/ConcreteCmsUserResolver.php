<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\User;

use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class ConcreteCmsUserResolver implements UserResolverPort
{
    /** @var \Closure(int): ?\Concrete\Core\User\UserInfo */
    private \Closure $userInfoLoader;

    public function __construct(?\Closure $userInfoLoader = null)
    {
        $this->userInfoLoader = $userInfoLoader ?? static fn(int $id) => \Concrete\Core\User\UserInfo::getByID($id);
    }

    public function resolveEmail(string $userId): ?string
    {
        $ui = ($this->userInfoLoader)((int) $userId);
        return $ui?->getUserEmail() ?: null;
    }

    public function resolveName(string $userId): string
    {
        $ui = ($this->userInfoLoader)((int) $userId);
        if ($ui === null) {
            return 'Benutzer ' . $userId;
        }
        return $ui->getAttribute('display_name') ?: $ui->getUserName();
    }

    /** @return string[] */
    public function resolveGroupEmails(string $groupName): array
    {
        $group = \Concrete\Core\User\Group\Group::getByName($groupName);
        if ($group === null) {
            return [];
        }

        $emails = [];
        foreach ($group->getGroupMembers() as $member) {
            $email = $member->getUserEmail();
            if ($email !== '' && $email !== null) {
                $emails[] = $email;
            }
        }

        return $emails;
    }
}
