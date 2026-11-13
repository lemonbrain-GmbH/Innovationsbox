<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a join request is accepted.
 */
class JoinRequestAccepted extends Event
{
    public const NAME = 'innovationsbox.project_space.join_request_accepted';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $requestId,
        private readonly string $userId,
    ) {}

    public function getSpaceId(): string
    {
        return $this->spaceId;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
