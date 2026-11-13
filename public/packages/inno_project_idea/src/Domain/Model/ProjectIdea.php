<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Domain\Model;

use Concrete\Package\InnoProjectIdea\Domain\Exception\IdeaNotEditableException;
use Concrete\Package\InnoProjectIdea\Domain\Exception\InvalidAttachmentException;

class ProjectIdea
{
    private const MAX_ATTACHMENTS = 5;

    /** @var IdeaAttachment[] */
    private array $attachments;

    /**
     * @param IdeaAttachment[] $attachments
     */
    private function __construct(
        private readonly string $id,
        private string $title,
        private string $description,
        private string $category,
        private IdeaStatus $status,
        private readonly string $submitterId,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        private ?string $rejectionReason = null,
        private ?string $projectSpaceId = null,
        array $attachments = [],
        private ?string $feedbackNotes = null,
        private ?string $feedbackDeadline = null,
        private ?int $headerImageFileId = null,
    ) {
        $this->attachments = $attachments;
    }

    public static function submit(
        string $id,
        string $title,
        string $description,
        string $category,
        string $submitterId,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            category: $category,
            status: IdeaStatus::Submitted,
            submitterId: $submitterId,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function edit(string $title, string $description, string $category): void
    {
        $this->guardEditable();
        $this->title = $title;
        $this->description = $description;
        $this->category = $category;
        $this->updatedAt = new \DateTimeImmutable();

        if ($this->status === IdeaStatus::FeedbackRequested) {
            $this->resubmit();
        }
    }

    /**
     * Apply moderator overrides before approval (bypasses status guard).
     */
    public function applyOverrides(?string $title, ?string $description, ?string $category): void
    {
        if ($title !== null) {
            $this->title = $title;
        }
        if ($description !== null) {
            $this->description = $description;
        }
        if ($category !== null) {
            $this->category = $category;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function approve(): void
    {
        $this->status = IdeaStatus::Approved;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function reject(string $reason = ''): void
    {
        $this->status = IdeaStatus::Rejected;
        $this->rejectionReason = $reason;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addAttachment(IdeaAttachment $attachment): void
    {
        $this->guardEditable();
        if (count($this->attachments) >= self::MAX_ATTACHMENTS) {
            throw InvalidAttachmentException::tooManyAttachments(self::MAX_ATTACHMENTS);
        }
        $this->attachments[] = $attachment;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeAttachment(string $attachmentId): void
    {
        $this->guardEditable();
        $this->attachments = array_values(array_filter(
            $this->attachments,
            fn(IdeaAttachment $a) => $a->getId() !== $attachmentId,
        ));
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setProjectSpaceId(string $spaceId): void
    {
        $this->projectSpaceId = $spaceId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function guardEditable(): void
    {
        if ($this->status !== IdeaStatus::Submitted && $this->status !== IdeaStatus::FeedbackRequested) {
            throw IdeaNotEditableException::withStatus($this->status->value);
        }
    }

    public function markUnderReview(): void
    {
        $this->status = IdeaStatus::UnderReview;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function requestFeedback(string $notes, ?string $deadline): void
    {
        $this->status = IdeaStatus::FeedbackRequested;
        $this->feedbackNotes = $notes;
        $this->feedbackDeadline = $deadline;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function resubmit(): void
    {
        $this->status = IdeaStatus::Submitted;
        $this->feedbackNotes = null;
        $this->feedbackDeadline = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getDescription(): string
    {
        return $this->description;
    }
    public function getCategory(): string
    {
        return $this->category;
    }
    public function getStatus(): IdeaStatus
    {
        return $this->status;
    }
    public function getSubmitterId(): string
    {
        return $this->submitterId;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }
    public function getProjectSpaceId(): ?string
    {
        return $this->projectSpaceId;
    }
    public function getFeedbackNotes(): ?string
    {
        return $this->feedbackNotes;
    }
    public function getFeedbackDeadline(): ?string
    {
        return $this->feedbackDeadline;
    }
    public function getHeaderImageFileId(): ?int
    {
        return $this->headerImageFileId;
    }
    public function setHeaderImageFileId(?int $fileId): void
    {
        $this->headerImageFileId = $fileId;
        $this->updatedAt = new \DateTimeImmutable();
    }
    /** @return IdeaAttachment[] */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @param IdeaAttachment[] $attachments
     */
    public static function reconstitute(
        string $id,
        string $title,
        ?string $description,
        string $category,
        IdeaStatus $status,
        ?int $submitterId,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?string $rejectionReason = null,
        ?string $projectSpaceId = null,
        array $attachments = [],
        ?string $feedbackNotes = null,
        ?string $feedbackDeadline = null,
        ?int $headerImageFileId = null,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description ?? '',
            category: $category,
            status: $status,
            submitterId: $submitterId !== null ? (string) $submitterId : '',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            rejectionReason: $rejectionReason,
            projectSpaceId: $projectSpaceId,
            attachments: $attachments,
            feedbackNotes: $feedbackNotes,
            feedbackDeadline: $feedbackDeadline,
            headerImageFileId: $headerImageFileId,
        );
    }
}
