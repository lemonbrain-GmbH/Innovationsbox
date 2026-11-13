<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\LeaveSpace\LeaveSpaceCommand;

interface LeaveSpacePort
{
    public function handle(LeaveSpaceCommand $command): void;
}
