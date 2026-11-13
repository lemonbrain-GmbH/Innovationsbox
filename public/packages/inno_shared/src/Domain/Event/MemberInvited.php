<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a member is invited to a project space.
 */
class MemberInvited extends Event
{
    public const NAME = 'innovationsbox.project_space.member_invited';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $userId,
        private readonly string $initiatorId,
    ) {}

    public function getSpaceId(): string
    {
        return $this->spaceId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getInitiatorId(): string
    {
        return $this->initiatorId;
    }
}
