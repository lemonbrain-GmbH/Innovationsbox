<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Domain\Model;

use DateTimeImmutable;

final class Category
{
    private function __construct(
        private readonly string $id,
        private string $name,
        private string $slug,
        private int $sortOrder,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(string $id, string $name, ?string $slug = null, int $sortOrder = 0): self
    {
        $now = new DateTimeImmutable();

        return new self($id, $name, $slug ?? self::generateSlug($name), $sortOrder, $now, $now);
    }

    public static function fromPersistence(
        string $id,
        string $name,
        string $slug,
        int $sortOrder,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $slug, $sortOrder, $createdAt, $updatedAt);
    }

    public function rename(string $name, ?string $slug = null): void
    {
        $this->name = $name;
        $this->slug = $slug ?? self::generateSlug($name);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function reorder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getSlug(): string
    {
        return $this->slug;
    }
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function generateSlug(string $name): string
    {
        $slug = mb_strtolower($name);
        $slug = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $slug);
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }
}
