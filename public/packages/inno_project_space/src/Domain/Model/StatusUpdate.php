<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

class StatusUpdate
{
    private const MAX_ATTACHMENTS = 5;

    /** @var StatusUpdateAttachment[] */
    private array $attachments;

    /**
     * @param StatusUpdateAttachment[] $attachments
     */
    private function __construct(
        private readonly string $id,
        private string $title,
        private string $body,
        private readonly string $authorId,
        private readonly \DateTimeImmutable $createdAt,
        array $attachments = [],
    ) {
        $this->attachments = $attachments;
    }

    public function edit(string $title, string $body): void
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function addAttachment(StatusUpdateAttachment $attachment): void
    {
        if (count($this->attachments) >= self::MAX_ATTACHMENTS) {
            throw new \DomainException('Maximum of ' . self::MAX_ATTACHMENTS . ' attachments per update.');
        }
        $this->attachments[] = $attachment;
    }

    public function removeAttachment(string $attachmentId): void
    {
        $this->attachments = array_values(array_filter(
            $this->attachments,
            fn(StatusUpdateAttachment $a) => $a->getId() !== $attachmentId,
        ));
    }

    public static function create(string $id, string $title, string $body, string $authorId): self
    {
        return new self($id, $title, $body, $authorId, new \DateTimeImmutable());
    }

    /**
     * @param StatusUpdateAttachment[] $attachments
     */
    public static function fromPersistence(
        string $id,
        string $title,
        string $body,
        string $authorId,
        \DateTimeImmutable $createdAt,
        array $attachments = [],
    ): self {
        return new self($id, $title, $body, $authorId, $createdAt, $attachments);
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getBody(): string
    {
        return $this->body;
    }
    public function getAuthorId(): string
    {
        return $this->authorId;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    /** @return StatusUpdateAttachment[] */
    public function getAttachments(): array
    {
        return $this->attachments;
    }
}
