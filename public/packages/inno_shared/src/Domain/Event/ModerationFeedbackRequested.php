<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a moderator requests feedback/changes from the idea submitter.
 * Consumer: project_idea (sets idea status to FeedbackRequested with notes/deadline).
 */
class ModerationFeedbackRequested extends Event
{
    public const NAME = 'innovationsbox.moderation.feedback_requested';

    public function __construct(
        private readonly string $ideaId,
        private readonly string $reviewerId,
        private readonly string $notes,
        private readonly ?string $deadline = null,
    ) {}

    public function getIdeaId(): string
    {
        return $this->ideaId;
    }
    public function getReviewerId(): string
    {
        return $this->reviewerId;
    }
    public function getNotes(): string
    {
        return $this->notes;
    }
    public function getDeadline(): ?string
    {
        return $this->deadline;
    }
}
