<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Port\Out;

use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;

interface ModerationReviewRepositoryInterface
{
    public function findById(string $id): ?ModerationReview;
    public function findByIdeaId(string $ideaId): ?ModerationReview;
    /** @return ModerationReview[] */
    public function findByDecision(ModerationDecision $decision): array;
    public function save(ModerationReview $review): void;
}
