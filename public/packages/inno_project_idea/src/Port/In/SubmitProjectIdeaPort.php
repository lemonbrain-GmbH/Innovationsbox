<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Port\In;

use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;

interface SubmitProjectIdeaPort
{
    public function handle(SubmitProjectIdeaCommand $command): ProjectIdea;
}
