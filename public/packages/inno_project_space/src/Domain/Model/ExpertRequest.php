<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

class ExpertRequest
{
    private function __construct(
        private readonly string $id,
        private readonly string $expertUserId,
        private readonly string $requestedByUserId,
        private readonly ?string $message,
        private ExpertRequestStatus $status,
        private readonly \DateTimeImmutable $requestedAt,
        private ?\DateTimeImmutable $decidedAt,
    ) {}

    public static function create(string $id, string $expertUserId, string $requestedByUserId, ?string $message): self
    {
        return new self($id, $expertUserId, $requestedByUserId, $message, ExpertRequestStatus::Pending, new \DateTimeImmutable(), null);
    }

    public static function fromPersistence(
        string $id,
        string $expertUserId,
        string $requestedByUserId,
        ?string $message,
        ExpertRequestStatus $status,
        \DateTimeImmutable $requestedAt,
        ?\DateTimeImmutable $decidedAt,
    ): self {
        return new self($id, $expertUserId, $requestedByUserId, $message, $status, $requestedAt, $decidedAt);
    }

    public function accept(): void
    {
        $this->status = ExpertRequestStatus::Accepted;
        $this->decidedAt = new \DateTimeImmutable();
    }

    public function decline(): void
    {
        $this->status = ExpertRequestStatus::Declined;
        $this->decidedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getExpertUserId(): string
    {
        return $this->expertUserId;
    }
    public function getRequestedByUserId(): string
    {
        return $this->requestedByUserId;
    }
    public function getMessage(): ?string
    {
        return $this->message;
    }
    public function getStatus(): ExpertRequestStatus
    {
        return $this->status;
    }
    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }
    public function getDecidedAt(): ?\DateTimeImmutable
    {
        return $this->decidedAt;
    }
}
