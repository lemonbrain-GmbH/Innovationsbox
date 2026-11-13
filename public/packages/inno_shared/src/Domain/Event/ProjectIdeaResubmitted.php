<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a submitter edits their idea after feedback was requested.
 * Consumer: moderation (resets review to Pending).
 */
class ProjectIdeaResubmitted extends Event
{
    public const NAME = 'innovationsbox.project_idea.resubmitted';

    public function __construct(
        private readonly string $ideaId,
    ) {}

    public function getIdeaId(): string
    {
        return $this->ideaId;
    }
}
