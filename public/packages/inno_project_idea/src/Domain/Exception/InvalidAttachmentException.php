<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InvalidAttachmentException extends ValidationException
{
    public static function invalidMimeType(string $mimeType): self
    {
        return new self("Unsupported file type: '{$mimeType}'.", ['field' => 'attachment', 'mimeType' => $mimeType]);
    }

    public static function fileTooLarge(int $size, int $maxSize): self
    {
        $maxMb = $maxSize / 1_000_000;

        return new self("File exceeds maximum size of {$maxMb}MB.", ['field' => 'attachment', 'size' => $size]);
    }

    public static function tooManyAttachments(int $max): self
    {
        return new self("Maximum of {$max} attachments allowed.", ['field' => 'attachment']);
    }

    public function getUserMessage(): string
    {
        return "Die Datei ist ungültig. Erlaubt sind Bilder (JPG, PNG, GIF, WebP), PDF und Office-Dokumente, maximal 10\u{00A0}MB.";
    }
}
