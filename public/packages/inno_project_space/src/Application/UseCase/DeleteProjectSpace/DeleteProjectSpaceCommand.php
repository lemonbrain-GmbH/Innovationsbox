<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\DeleteProjectSpace;

class DeleteProjectSpaceCommand
{
    public function __construct(
        public readonly string $spaceId,
    ) {}
}
