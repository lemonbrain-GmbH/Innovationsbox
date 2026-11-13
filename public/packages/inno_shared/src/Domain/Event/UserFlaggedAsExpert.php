<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UserFlaggedAsExpert extends Event
{
    public const NAME = 'innovationsbox.user.flagged_as_expert';

    public function __construct(
        private readonly string $userId,
        private readonly string $flaggedByUserId,
    ) {}

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getFlaggedByUserId(): string
    {
        return $this->flaggedByUserId;
    }
}
