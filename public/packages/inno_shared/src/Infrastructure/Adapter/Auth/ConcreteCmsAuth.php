<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Auth;

use Concrete\Core\User\User;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\UserContext;

class ConcreteCmsAuth implements AuthInterface
{
    private const PERMISSION_MAP = [
        'idea.submit' => ['*authenticated*'],
        'idea.view' => ['*any*'],
        'idea.vote' => ['*authenticated*'],
        'idea.admin' => ['moderator', 'administrator'],
        'moderation.review' => ['moderator', 'administrator'],
        'space.join' => ['*authenticated*'],
        'space.manage' => ['*authenticated*'],
        'space.admin' => ['moderator', 'administrator'],
        'space.moderate' => ['moderator', 'administrator'],
        'expert.manage' => ['moderator', 'administrator'],
    ];

    private const GROUP_TO_ROLE = [
        'mitglied' => 'member',
        'entwickler' => 'developer',
        'initiator' => 'initiator',
        'moderator' => 'moderator',
        'administrator' => 'administrator',
        'administrators' => 'administrator',
        'member' => 'member',
        'developer' => 'developer',
        'experte' => 'expert',
        'expert' => 'expert',
    ];

    public function __construct(private readonly User $user) {}

    public function getCurrentUser(): ?UserContext
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        $info = $this->user->getUserInfoObject();

        return new UserContext(
            id: (int) $this->user->getUserID(),
            email: $info->getUserEmail(),
            name: $info->getUserName(),
            roles: $this->getGroupNames(),
        );
    }

    public function isAuthenticated(): bool
    {
        return $this->user->isRegistered();
    }

    public function hasPermission(string $permission): bool
    {
        $allowedRoles = self::PERMISSION_MAP[$permission] ?? [];

        if (in_array('*any*', $allowedRoles, true)) {
            return true;
        }

        if (!$this->isAuthenticated()) {
            return false;
        }

        if (in_array('*authenticated*', $allowedRoles, true)) {
            return true;
        }

        $userRoles = $this->getNormalizedRoles();

        return !empty(array_intersect($allowedRoles, $userRoles));
    }

    public function hasRole(string $role): bool
    {
        return in_array(strtolower($role), $this->getNormalizedRoles(), true);
    }

    public function getUserId(): ?int
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return (int) $this->user->getUserID();
    }

    /**
     * getUserGroups() returns [gID => gID]. Resolve to group names.
     *
     * @return list<string>
     */
    private function getGroupNames(): array
    {
        $names = [];
        foreach ($this->user->getUserGroups() as $gID) {
            if (is_string($gID) && !is_numeric($gID)) {
                // Already a group name (e.g. in test mocks)
                $names[] = $gID;
                continue;
            }
            try {
                $group = \Concrete\Core\User\Group\Group::getByID((string) (int) $gID);
                $names[] = $group->getGroupName();
            } catch (\Throwable) {
                // Group lookup unavailable (e.g. unit tests without CMS bootstrap)
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function getNormalizedRoles(): array
    {
        $roles = [];
        foreach ($this->getGroupNames() as $group) {
            $lower = strtolower($group);
            $roles[] = self::GROUP_TO_ROLE[$lower] ?? $lower;
        }

        return $roles;
    }
}
