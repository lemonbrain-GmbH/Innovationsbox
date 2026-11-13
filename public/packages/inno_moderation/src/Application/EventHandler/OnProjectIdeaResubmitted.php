<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\EventHandler;

use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaResubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class OnProjectIdeaResubmitted
{
    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectIdeaResubmitted $event): void
    {
        $review = $this->repository->findByIdeaId($event->getIdeaId());
        if ($review === null) {
            return;
        }

        $review->resetToPending();
        $this->repository->save($review);

        $this->logger->info('Moderation review reset to pending after resubmission', [
            'reviewId' => $review->getId(),
            'ideaId' => $event->getIdeaId(),
        ]);
    }
}
