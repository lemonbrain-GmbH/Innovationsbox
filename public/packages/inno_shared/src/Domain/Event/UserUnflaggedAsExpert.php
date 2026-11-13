<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UserUnflaggedAsExpert extends Event
{
    public const NAME = 'innovationsbox.user.unflagged_as_expert';

    public function __construct(
        private readonly string $userId,
        private readonly string $unflaggedByUserId,
    ) {}

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getUnflaggedByUserId(): string
    {
        return $this->unflaggedByUserId;
    }
}
