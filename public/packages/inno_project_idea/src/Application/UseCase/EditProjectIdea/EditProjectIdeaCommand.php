<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea;

final class EditProjectIdeaCommand
{
    public function __construct(
        public readonly string $ideaId,
        public readonly string $title,
        public readonly string $description,
        public readonly string $category,
    ) {}
}
