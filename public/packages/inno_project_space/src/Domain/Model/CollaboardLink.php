<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

use Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidCollaboardUrlException;

class CollaboardLink
{
    private function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $url,
        private readonly \DateTimeImmutable $addedAt,
    ) {}

    public static function create(string $id, string $title, string $url): self
    {
        if (!self::isValidCollaboardUrl($url)) {
            throw InvalidCollaboardUrlException::withUrl($url);
        }

        return new self($id, $title, $url, new \DateTimeImmutable());
    }

    public static function fromPersistence(
        string $id,
        string $title,
        string $url,
        \DateTimeImmutable $addedAt,
    ): self {
        return new self($id, $title, $url, $addedAt);
    }

    private static function isValidCollaboardUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null || $host === false) {
            return false;
        }

        return $host === 'web.collaboard.app';
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getUrl(): string
    {
        return $this->url;
    }
    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
