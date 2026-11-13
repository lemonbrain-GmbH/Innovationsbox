<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InvalidCollaboardUrlException extends ValidationException
{
    public static function withUrl(string $url): self
    {
        return new self(
            "URL must be a web.collaboard.app link, got: '{$url}'",
            ['url' => $url],
        );
    }

    public function getUserMessage(): string
    {
        return "Bitte gib eine gültige Collaboard-URL ein (z.\u{00A0}B. https://web.collaboard.app/share/...).";
    }
}
