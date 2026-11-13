<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveMember\RemoveMemberCommand;

interface RemoveMemberPort
{
    public function handle(RemoveMemberCommand $command): void;
}
