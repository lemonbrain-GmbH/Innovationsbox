<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Domain\Model;

class ModerationReview
{
    private function __construct(
        private readonly string $id,
        private readonly string $ideaId,
        private ModerationDecision $decision,
        private ?string $reviewerId,
        private string $notes,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $reviewedAt,
        private ?string $titleOverride = null,
        private ?string $descriptionOverride = null,
        private ?string $categoryOverride = null,
        private ?string $feedbackDeadline = null,
    ) {}

    public static function createPending(string $id, string $ideaId): self
    {
        return new self(
            id: $id,
            ideaId: $ideaId,
            decision: ModerationDecision::Pending,
            reviewerId: null,
            notes: '',
            createdAt: new \DateTimeImmutable(),
            reviewedAt: null,
        );
    }

    public static function reconstitute(
        string $id,
        string $ideaId,
        ModerationDecision $decision,
        ?string $reviewerId,
        string $notes,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $reviewedAt,
        ?string $titleOverride = null,
        ?string $descriptionOverride = null,
        ?string $categoryOverride = null,
        ?string $feedbackDeadline = null,
    ): self {
        return new self($id, $ideaId, $decision, $reviewerId, $notes, $createdAt, $reviewedAt, $titleOverride, $descriptionOverride, $categoryOverride, $feedbackDeadline);
    }

    public function approve(
        string $reviewerId,
        string $notes = '',
        ?string $titleOverride = null,
        ?string $descriptionOverride = null,
        ?string $categoryOverride = null,
    ): void {
        $this->decision = ModerationDecision::Approved;
        $this->reviewerId = $reviewerId;
        $this->notes = $notes;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->titleOverride = $titleOverride;
        $this->descriptionOverride = $descriptionOverride;
        $this->categoryOverride = $categoryOverride;
    }

    public function reject(string $reviewerId, string $notes): void
    {
        $this->decision = ModerationDecision::Rejected;
        $this->reviewerId = $reviewerId;
        $this->notes = $notes;
        $this->reviewedAt = new \DateTimeImmutable();
    }

    public function resetToPending(): void
    {
        $this->decision = ModerationDecision::Pending;
        $this->reviewerId = null;
        $this->notes = '';
        $this->reviewedAt = null;
        $this->feedbackDeadline = null;
    }

    public function requestFeedback(string $reviewerId, string $notes, ?string $deadline): void
    {
        $this->decision = ModerationDecision::FeedbackRequested;
        $this->reviewerId = $reviewerId;
        $this->notes = $notes;
        $this->feedbackDeadline = $deadline;
        $this->reviewedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getIdeaId(): string
    {
        return $this->ideaId;
    }
    public function getDecision(): ModerationDecision
    {
        return $this->decision;
    }
    public function getReviewerId(): ?string
    {
        return $this->reviewerId;
    }
    public function getNotes(): string
    {
        return $this->notes;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
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
    public function getFeedbackDeadline(): ?string
    {
        return $this->feedbackDeadline;
    }
}
