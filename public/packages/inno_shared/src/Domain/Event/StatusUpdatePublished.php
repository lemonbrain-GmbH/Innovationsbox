<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a status update is published in a project space.
 */
class StatusUpdatePublished extends Event
{
    public const NAME = 'innovationsbox.project_space.status_update_published';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $updateId,
        private readonly string $authorId,
    ) {}

    public function getSpaceId(): string
    {
        return $this->spaceId;
    }

    public function getUpdateId(): string
    {
        return $this->updateId;
    }

    public function getAuthorId(): string
    {
        return $this->authorId;
    }
}
