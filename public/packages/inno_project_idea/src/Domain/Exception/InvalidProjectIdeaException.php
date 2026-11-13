<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InvalidProjectIdeaException extends ValidationException
{
    public static function emptyTitle(): self
    {
        return new self('Project idea title cannot be empty.', ['field' => 'title']);
    }

    public static function emptyDescription(): self
    {
        return new self('Project idea description cannot be empty.', ['field' => 'description']);
    }
}
