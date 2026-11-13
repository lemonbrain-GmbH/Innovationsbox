<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\SetSpacePriority\SetSpacePriorityCommand;

interface SetSpacePriorityPort
{
    public function handle(SetSpacePriorityCommand $command): void;
}
