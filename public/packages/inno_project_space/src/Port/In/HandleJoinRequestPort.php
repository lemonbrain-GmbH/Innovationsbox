<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest\HandleJoinRequestCommand;

interface HandleJoinRequestPort
{
    public function handle(HandleJoinRequestCommand $command): void;
}
