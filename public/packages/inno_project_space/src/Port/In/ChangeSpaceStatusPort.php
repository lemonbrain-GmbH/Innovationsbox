<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\ChangeSpaceStatus\ChangeSpaceStatusCommand;

interface ChangeSpaceStatusPort
{
    public function handle(ChangeSpaceStatusCommand $command): void;
}
