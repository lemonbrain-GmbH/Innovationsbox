<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea;

final class ReviewProjectIdeaCommand
{
    public function __construct(
        public readonly string $reviewId,
        public readonly string $reviewerId,
        public readonly bool $approved,
        public readonly string $notes,
        public readonly ?string $titleOverride = null,
        public readonly ?string $descriptionOverride = null,
        public readonly ?string $categoryOverride = null,
        public readonly bool $feedbackRequested = false,
        public readonly ?string $feedbackDeadline = null,
    ) {}
}
