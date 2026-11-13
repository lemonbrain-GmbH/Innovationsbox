<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea;

final class SubmitProjectIdeaCommand
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $category,
        public readonly string $submitterId,
    ) {}
}
