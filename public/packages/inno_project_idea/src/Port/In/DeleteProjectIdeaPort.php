<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Port\In;

use Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea\DeleteProjectIdeaCommand;

interface DeleteProjectIdeaPort
{
    public function handle(DeleteProjectIdeaCommand $command): void;
}
