<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\LeaveSpace;

class LeaveSpaceCommand
{
    public function __construct(public readonly string $spaceId) {}
}
