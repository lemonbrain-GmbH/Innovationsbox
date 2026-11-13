<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

use Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidStatusTransitionException;

class ProjectSpace
{
    /** @var string[] */
    private array $memberIds;
    /** @var CollaboardLink[] */
    private array $collaboardLinks;
    /** @var StatusUpdate[] */
    private array $statusUpdates;
    /** @var MembershipRequest[] */
    private array $membershipRequests;
    /** @var ExpertRequest[] */
    private array $expertRequests;

    private ?int $headerImageFileId = null;
    private int $priority = 0;

    private function __construct(
        private readonly string $id,
        private readonly string $projectIdeaId,
        private string $title,
        private string $description,
        private SpaceStatus $status,
        private readonly string $initiatorId,
        private readonly string $categoryId,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->memberIds = [];
        $this->collaboardLinks = [];
        $this->statusUpdates = [];
        $this->membershipRequests = [];
        $this->expertRequests = [];
    }

    public static function createFromApprovedIdea(
        string $id,
        string $projectIdeaId,
        string $title,
        string $description,
        string $initiatorId,
        string $categoryId = '',
        ?int $headerImageFileId = null,
    ): self {
        $now = new \DateTimeImmutable();
        $space = new self($id, $projectIdeaId, $title, $description, SpaceStatus::Active, $initiatorId, $categoryId, $now, $now);
        $space->memberIds[] = $initiatorId;
        $space->headerImageFileId = $headerImageFileId;

        return $space;
    }

    /**
     * Reconstruct from persistence — no validation, no side effects.
     *
     * @param string[] $memberIds
     * @param CollaboardLink[] $collaboardLinks
     * @param StatusUpdate[] $statusUpdates
     * @param MembershipRequest[] $membershipRequests
     * @param ExpertRequest[] $expertRequests
     */
    public static function fromPersistence(
        string $id,
        string $projectIdeaId,
        string $title,
        string $description,
        SpaceStatus $status,
        string $initiatorId,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        array $memberIds,
        array $collaboardLinks,
        array $statusUpdates,
        array $membershipRequests,
        string $categoryId = '',
        array $expertRequests = [],
        ?int $headerImageFileId = null,
        int $priority = 0,
    ): self {
        $space = new self($id, $projectIdeaId, $title, $description, $status, $initiatorId, $categoryId, $createdAt, $updatedAt);
        $space->memberIds = $memberIds;
        $space->collaboardLinks = $collaboardLinks;
        $space->statusUpdates = $statusUpdates;
        $space->membershipRequests = $membershipRequests;
        $space->expertRequests = $expertRequests;
        $space->headerImageFileId = $headerImageFileId;
        $space->priority = $priority;

        return $space;
    }

    // --- Collaboard links ---

    public function addCollaboardLink(string $id, string $title, string $url): CollaboardLink
    {
        $link = CollaboardLink::create($id, $title, $url);
        $this->collaboardLinks[] = $link;
        $this->updatedAt = new \DateTimeImmutable();

        return $link;
    }

    public function removeCollaboardLink(string $linkId): void
    {
        $this->collaboardLinks = array_values(array_filter(
            $this->collaboardLinks,
            fn(CollaboardLink $l) => $l->getId() !== $linkId,
        ));
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Status updates ---

    public function addStatusUpdate(string $id, string $title, string $body, string $authorId): StatusUpdate
    {
        $update = StatusUpdate::create($id, $title, $body, $authorId);
        $this->statusUpdates[] = $update;
        $this->updatedAt = new \DateTimeImmutable();

        return $update;
    }

    public function editStatusUpdate(string $updateId, string $title, string $body): StatusUpdate
    {
        foreach ($this->statusUpdates as $update) {
            if ($update->getId() === $updateId) {
                $update->edit($title, $body);
                $this->updatedAt = new \DateTimeImmutable();

                return $update;
            }
        }
        throw new \DomainException("StatusUpdate '{$updateId}' not found.");
    }

    public function deleteStatusUpdate(string $updateId): void
    {
        $found = false;
        $this->statusUpdates = array_values(array_filter(
            $this->statusUpdates,
            function (StatusUpdate $u) use ($updateId, &$found) {
                if ($u->getId() === $updateId) {
                    $found = true;

                    return false;
                }

                return true;
            },
        ));
        if (!$found) {
            throw new \DomainException("StatusUpdate '{$updateId}' not found.");
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Membership ---

    public function addMember(string $userId): void
    {
        if (!in_array($userId, $this->memberIds, true)) {
            $this->memberIds[] = $userId;
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function removeMember(string $userId): void
    {
        if ($userId === $this->initiatorId) {
            throw new \DomainException('The initiator cannot be removed from the space.');
        }
        $index = array_search($userId, $this->memberIds, true);
        if ($index === false) {
            throw new \DomainException("User '{$userId}' is not a member of this space.");
        }
        array_splice($this->memberIds, $index, 1);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function requestToJoin(string $requestId, string $userId): MembershipRequest
    {
        if ($this->status !== SpaceStatus::Active && $this->status !== SpaceStatus::Completed) {
            throw new \DomainException('Space is not accepting join requests.');
        }
        if (in_array($userId, $this->memberIds, true)) {
            throw new \DomainException('User is already a member.');
        }

        // Check for existing request from this user
        foreach ($this->membershipRequests as $existing) {
            if ($existing->getUserId() === $userId) {
                if ($existing->getStatus() === RequestStatus::Pending) {
                    throw new \DomainException('User already has a pending join request.');
                }
                // Re-request after rejection: reset the existing request to pending
                $existing->resubmit();

                return $existing;
            }
        }

        $request = MembershipRequest::create($requestId, $userId);
        $this->membershipRequests[] = $request;

        return $request;
    }

    public function acceptJoinRequest(string $requestId): MembershipRequest
    {
        $request = $this->findRequest($requestId);
        $request->accept();
        $this->addMember($request->getUserId());

        return $request;
    }

    public function rejectJoinRequest(string $requestId): MembershipRequest
    {
        $request = $this->findRequest($requestId);
        $request->reject();

        return $request;
    }

    private function findRequest(string $requestId): MembershipRequest
    {
        foreach ($this->membershipRequests as $request) {
            if ($request->getId() === $requestId) {
                return $request;
            }
        }
        throw new \DomainException("MembershipRequest '{$requestId}' not found.");
    }

    // --- Expert requests ---

    public function requestExpert(string $id, string $expertUserId, string $requestedByUserId, ?string $message): ExpertRequest
    {
        if (in_array($expertUserId, $this->memberIds, true)) {
            throw new \DomainException("User '{$expertUserId}' is already a member of this space.");
        }
        foreach ($this->expertRequests as $req) {
            if ($req->getExpertUserId() === $expertUserId && $req->getStatus() === ExpertRequestStatus::Pending) {
                throw new \DomainException("Expert '{$expertUserId}' already has a pending request.");
            }
        }
        $request = ExpertRequest::create($id, $expertUserId, $requestedByUserId, $message);
        $this->expertRequests[] = $request;
        $this->updatedAt = new \DateTimeImmutable();

        return $request;
    }

    public function acceptExpertRequest(string $requestId): ExpertRequest
    {
        $request = $this->findExpertRequest($requestId);
        $request->accept();
        $this->addMember($request->getExpertUserId());

        return $request;
    }

    public function declineExpertRequest(string $requestId): ExpertRequest
    {
        $request = $this->findExpertRequest($requestId);
        $request->decline();

        return $request;
    }

    /** @return ExpertRequest[] */
    public function getExpertRequests(): array
    {
        return $this->expertRequests;
    }

    private function findExpertRequest(string $requestId): ExpertRequest
    {
        foreach ($this->expertRequests as $req) {
            if ($req->getId() === $requestId) {
                return $req;
            }
        }
        throw new \DomainException("Expert request '{$requestId}' not found.");
    }

    // --- Status transitions ---

    public function activate(): void
    {
        if ($this->status !== SpaceStatus::Draft) {
            throw InvalidStatusTransitionException::fromTo($this->status, SpaceStatus::Active);
        }
        $this->status = SpaceStatus::Active;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function complete(): void
    {
        if ($this->status !== SpaceStatus::Active) {
            throw InvalidStatusTransitionException::fromTo($this->status, SpaceStatus::Completed);
        }
        $this->status = SpaceStatus::Completed;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function reopen(): void
    {
        if ($this->status !== SpaceStatus::Completed) {
            throw InvalidStatusTransitionException::fromTo($this->status, SpaceStatus::Active);
        }
        $this->status = SpaceStatus::Active;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function archive(): void
    {
        if ($this->status !== SpaceStatus::Completed) {
            throw InvalidStatusTransitionException::fromTo($this->status, SpaceStatus::Archived);
        }
        $this->status = SpaceStatus::Archived;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function dearchive(): void
    {
        if ($this->status !== SpaceStatus::Archived) {
            throw InvalidStatusTransitionException::fromTo($this->status, SpaceStatus::Completed);
        }
        $this->status = SpaceStatus::Completed;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Getters ---

    public function getId(): string
    {
        return $this->id;
    }
    public function getProjectIdeaId(): string
    {
        return $this->projectIdeaId;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getDescription(): string
    {
        return $this->description;
    }
    public function getStatus(): SpaceStatus
    {
        return $this->status;
    }
    public function getInitiatorId(): string
    {
        return $this->initiatorId;
    }
    public function getCategoryId(): string
    {
        return $this->categoryId;
    }
    /** @return list<string> */
    public function getMemberIds(): array
    {
        return $this->memberIds;
    }
    /** @return CollaboardLink[] */
    public function getCollaboardLinks(): array
    {
        return $this->collaboardLinks;
    }
    /** @return StatusUpdate[] */
    public function getStatusUpdates(): array
    {
        return $this->statusUpdates;
    }
    /** @return MembershipRequest[] */
    public function getMembershipRequests(): array
    {
        return $this->membershipRequests;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getHeaderImageFileId(): ?int
    {
        return $this->headerImageFileId;
    }

    public function setHeaderImage(?int $fileId): void
    {
        $this->headerImageFileId = $fileId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        if ($priority < 0 || $priority > 3) {
            throw new \DomainException('Priority must be between 0 and 3.');
        }
        $this->priority = $priority;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
