<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a user submits a join request to a project space.
 */
class JoinRequestReceived extends Event
{
    public const NAME = 'innovationsbox.project_space.join_request_received';

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
