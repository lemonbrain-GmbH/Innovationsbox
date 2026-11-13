<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class IdeaNotEditableException extends ValidationException
{
    public static function withStatus(string $status): self
    {
        return new self("Idea cannot be edited in status '{$status}'.", ['status' => $status]);
    }

    public function getUserMessage(): string
    {
        return 'Diese Projektidee kann nicht mehr bearbeitet werden.';
    }
}
