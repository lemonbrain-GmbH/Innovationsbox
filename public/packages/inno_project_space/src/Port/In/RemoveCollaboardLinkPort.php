<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveCollaboardLink\RemoveCollaboardLinkCommand;

interface RemoveCollaboardLinkPort
{
    public function handle(RemoveCollaboardLinkCommand $command): void;
}
