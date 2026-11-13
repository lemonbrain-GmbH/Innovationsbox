<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\InviteMember\InviteMemberCommand;

interface InviteMemberPort
{
    public function handle(InviteMemberCommand $command): void;
}
