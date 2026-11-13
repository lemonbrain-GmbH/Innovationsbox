<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest\HandleExpertRequestCommand;

interface HandleExpertRequestPort
{
    public function handle(HandleExpertRequestCommand $command): void;
}
