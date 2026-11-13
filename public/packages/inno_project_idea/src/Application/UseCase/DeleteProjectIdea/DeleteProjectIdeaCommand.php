<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea;

final class DeleteProjectIdeaCommand
{
    public function __construct(
        public readonly string $ideaId,
    ) {}
}
