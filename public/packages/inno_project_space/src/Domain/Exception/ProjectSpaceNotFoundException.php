<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;

class ProjectSpaceNotFoundException extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self("ProjectSpace '{$id}' not found.", ['spaceId' => $id]);
    }
}
