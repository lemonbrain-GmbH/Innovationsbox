<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\AddCollaboardLink\AddCollaboardLinkCommand;
use Concrete\Package\InnoProjectSpace\Domain\Model\CollaboardLink;

interface AddCollaboardLinkPort
{
    public function handle(AddCollaboardLinkCommand $command): CollaboardLink;
}
