<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Port\In;

use Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea\EditProjectIdeaCommand;

interface EditProjectIdeaPort
{
    public function handle(EditProjectIdeaCommand $command): void;
}
