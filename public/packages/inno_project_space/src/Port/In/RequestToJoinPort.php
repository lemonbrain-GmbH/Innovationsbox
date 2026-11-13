<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin\RequestToJoinCommand;
use Concrete\Package\InnoProjectSpace\Domain\Model\MembershipRequest;

interface RequestToJoinPort
{
    public function handle(RequestToJoinCommand $command): MembershipRequest;
}
