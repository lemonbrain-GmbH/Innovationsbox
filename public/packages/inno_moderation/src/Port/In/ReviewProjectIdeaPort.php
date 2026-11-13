<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Port\In;

use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaCommand;

interface ReviewProjectIdeaPort
{
    public function handle(ReviewProjectIdeaCommand $command): void;
}
