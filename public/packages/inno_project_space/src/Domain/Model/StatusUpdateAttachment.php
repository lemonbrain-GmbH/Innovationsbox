<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

class StatusUpdateAttachment
{
    private const MAX_SIZE = 10_000_000; // 10MB

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
    ];

    private function __construct(
        private readonly string $id,
        private readonly string $filename,
        private readonly int $fileManagerId,
        private readonly string $mimeType,
        private readonly int $size,
        private readonly \DateTimeImmutable $addedAt,
    ) {}

    public static function create(string $id, string $filename, int $fileManagerId, string $mimeType, int $size): self
    {
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \DomainException("Invalid MIME type: {$mimeType}");
        }
        if ($size > self::MAX_SIZE) {
            throw new \DomainException('File exceeds maximum size of 10 MB.');
        }

        return new self($id, $filename, $fileManagerId, $mimeType, $size, new \DateTimeImmutable());
    }

    public static function fromPersistence(
        string $id,
        string $filename,
        int $fileManagerId,
        string $mimeType,
        int $size,
        \DateTimeImmutable $addedAt,
    ): self {
        return new self($id, $filename, $fileManagerId, $mimeType, $size, $addedAt);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getFileManagerId(): int
    {
        return $this->fileManagerId;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }
}
