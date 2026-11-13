<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\SetSpacePriority;

final class SetSpacePriorityCommand
{
    public function __construct(
        public readonly string $spaceId,
        public readonly int $priority,
    ) {}
}
