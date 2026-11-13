<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert\RequestExpertCommand;

interface RequestExpertPort
{
    public function handle(RequestExpertCommand $command): void;
}
