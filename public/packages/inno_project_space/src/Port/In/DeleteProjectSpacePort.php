<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\DeleteProjectSpace\DeleteProjectSpaceCommand;

interface DeleteProjectSpacePort
{
    public function handle(DeleteProjectSpaceCommand $command): void;
}
