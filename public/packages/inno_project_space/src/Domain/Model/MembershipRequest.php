<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

class MembershipRequest
{
    private function __construct(
        private readonly string $id,
        private readonly string $userId,
        private RequestStatus $status,
        private readonly \DateTimeImmutable $requestedAt,
        private ?\DateTimeImmutable $decidedAt,
    ) {}

    public static function create(string $id, string $userId): self
    {
        return new self($id, $userId, RequestStatus::Pending, new \DateTimeImmutable(), null);
    }

    public static function fromPersistence(
        string $id,
        string $userId,
        RequestStatus $status,
        \DateTimeImmutable $requestedAt,
        ?\DateTimeImmutable $decidedAt,
    ): self {
        return new self($id, $userId, $status, $requestedAt, $decidedAt);
    }

    public function accept(): void
    {
        $this->status = RequestStatus::Accepted;
        $this->decidedAt = new \DateTimeImmutable();
    }

    public function reject(): void
    {
        $this->status = RequestStatus::Rejected;
        $this->decidedAt = new \DateTimeImmutable();
    }

    public function resubmit(): void
    {
        $this->status = RequestStatus::Pending;
        $this->decidedAt = null;
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getUserId(): string
    {
        return $this->userId;
    }
    public function getStatus(): RequestStatus
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
