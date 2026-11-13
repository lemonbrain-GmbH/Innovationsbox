<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a project space is created for an approved idea.
 * Consumers: notification (notifies the submitter).
 */
class ProjectSpaceCreated extends Event
{
    public const NAME = 'innovationsbox.project_space.created';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $projectIdeaId,
        private readonly string $initiatorId,
    ) {}

    public function getSpaceId(): string
    {
        return $this->spaceId;
    }
    public function getProjectIdeaId(): string
    {
        return $this->projectIdeaId;
    }
    public function getInitiatorId(): string
    {
        return $this->initiatorId;
    }
}
