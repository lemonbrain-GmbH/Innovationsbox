<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a new project idea is submitted.
 * Consumers: moderation (creates review), notification (notifies admins).
 */
class ProjectIdeaSubmitted extends Event
{
    public const NAME = 'innovationsbox.project_idea.submitted';

    public function __construct(
        private readonly string $ideaId,
        private readonly string $submitterId,
        private readonly string $title,
    ) {}

    public function getIdeaId(): string
    {
        return $this->ideaId;
    }
    public function getSubmitterId(): string
    {
        return $this->submitterId;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
}
