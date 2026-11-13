<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a project space is activated.
 */
class ProjectSpaceActivated extends Event
{
    public const NAME = 'innovationsbox.project_space.activated';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $initiatorId,
    ) {}

    public function getSpaceId(): string
    {
        return $this->spaceId;
    }

    public function getInitiatorId(): string
    {
        return $this->initiatorId;
    }
}
