<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a moderator approves or rejects a project idea.
 * Consumer: project_idea (updates idea status and re-publishes Approved/Rejected event).
 */
class ModerationDecisionMade extends Event
{
    public const NAME = 'innovationsbox.moderation.decision_made';

    public function __construct(
        private readonly string $ideaId,
        private readonly string $reviewerId,
        private readonly bool $approved,
        private readonly string $reason,
        private readonly ?string $titleOverride = null,
        private readonly ?string $descriptionOverride = null,
        private readonly ?string $categoryOverride = null,
    ) {}

    public function getIdeaId(): string
    {
        return $this->ideaId;
    }
    public function getReviewerId(): string
    {
        return $this->reviewerId;
    }
    public function isApproved(): bool
    {
        return $this->approved;
    }
    public function getReason(): string
    {
        return $this->reason;
    }
    public function getTitleOverride(): ?string
    {
        return $this->titleOverride;
    }
    public function getDescriptionOverride(): ?string
    {
        return $this->descriptionOverride;
    }
    public function getCategoryOverride(): ?string
    {
        return $this->categoryOverride;
    }
}
