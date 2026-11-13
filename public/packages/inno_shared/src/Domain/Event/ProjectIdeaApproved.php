<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a moderation decision approves a project idea.
 * Consumers: project_space (creates a space), notification (notifies submitter).
 */
class ProjectIdeaApproved extends Event
{
    public const NAME = 'innovationsbox.project_idea.approved';

    public function __construct(
        private readonly string $ideaId,
        private readonly string $submitterId,
        private readonly string $categoryId = '',
        private readonly string $title = '',
        private readonly string $description = '',
        private readonly ?int $headerImageFileId = null,
    ) {}

    public function getIdeaId(): string
    {
        return $this->ideaId;
    }
    public function getSubmitterId(): string
    {
        return $this->submitterId;
    }
    public function getCategoryId(): string
    {
        return $this->categoryId;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getDescription(): string
    {
        return $this->description;
    }

    public function getHeaderImageFileId(): ?int
    {
        return $this->headerImageFileId;
    }
}
