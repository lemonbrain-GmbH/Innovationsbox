<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;

class ModerationReviewNotFoundException extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self("ModerationReview '{$id}' not found.", ['reviewId' => $id]);
    }
}
