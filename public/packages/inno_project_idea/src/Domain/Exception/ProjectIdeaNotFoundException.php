<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;

class ProjectIdeaNotFoundException extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self("ProjectIdea '{$id}' not found.", ['ideaId' => $id]);
    }
}
