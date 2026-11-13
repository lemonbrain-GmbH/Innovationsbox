<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate\WriteStatusUpdateCommand;
use Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdate;

interface WriteStatusUpdatePort
{
    public function handle(WriteStatusUpdateCommand $command): StatusUpdate;
}
