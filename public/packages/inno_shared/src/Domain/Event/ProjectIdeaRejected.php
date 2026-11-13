<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a moderation decision rejects a project idea.
 * Consumers: notification (notifies submitter with reason).
 */
class ProjectIdeaRejected extends Event
{
    public const NAME = 'innovationsbox.project_idea.rejected';

    public function __construct(
        private readonly string $ideaId,
        private readonly string $submitterId,
        private readonly string $reason,
    ) {}

    public function getIdeaId(): string
    {
        return $this->ideaId;
    }
    public function getSubmitterId(): string
    {
        return $this->submitterId;
    }
    public function getReason(): string
    {
        return $this->reason;
    }
}
