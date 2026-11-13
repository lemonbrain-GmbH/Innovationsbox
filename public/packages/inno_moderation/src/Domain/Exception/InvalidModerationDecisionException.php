<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InvalidModerationDecisionException extends ValidationException
{
    public static function emptyNotes(): self
    {
        return new self('Moderation review notes cannot be empty.', ['field' => 'notes']);
    }
}
