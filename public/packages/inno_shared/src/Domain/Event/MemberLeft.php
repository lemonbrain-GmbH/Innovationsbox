<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MemberLeft extends Event
{
    public const NAME = 'innovationsbox.project_space.member_left';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $userId,
    ) {}

    public function getSpaceId(): string
    {
        return $this->spaceId;
    }
    public function getUserId(): string
    {
        return $this->userId;
    }
}
