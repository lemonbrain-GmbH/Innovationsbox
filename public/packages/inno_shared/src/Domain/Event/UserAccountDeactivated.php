<?php

// public/packages/inno_shared/src/Domain/Event/UserAccountDeactivated.php
declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UserAccountDeactivated extends Event
{
    public const NAME = 'innovationsbox.user.account_deactivated';

    public function __construct(
        private readonly string $userId,
        private readonly string $userEmail,
        private readonly string $userName,
        private readonly string $reason,
        private readonly string $deactivatedByUserId,
    ) {}

    public function getUserId(): string
    {
        return $this->userId;
    }
    public function getUserEmail(): string
    {
        return $this->userEmail;
    }
    public function getUserName(): string
    {
        return $this->userName;
    }
    public function getReason(): string
    {
        return $this->reason;
    }
    public function getDeactivatedByUserId(): string
    {
        return $this->deactivatedByUserId;
    }
    public function isSelfDeletion(): bool
    {
        return $this->userId === $this->deactivatedByUserId;
    }
}
