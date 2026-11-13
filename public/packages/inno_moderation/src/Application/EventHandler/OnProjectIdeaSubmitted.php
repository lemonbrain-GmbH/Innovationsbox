<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\EventHandler;

use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class OnProjectIdeaSubmitted
{
    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectIdeaSubmitted $event): void
    {
        $review = ModerationReview::createPending(
            id: uniqid('review_', true),
            ideaId: $event->getIdeaId(),
        );
        $this->repository->save($review);

        $this->logger->info('Pending moderation review created', [
            'reviewId' => $review->getId(),
            'ideaId' => $event->getIdeaId(),
        ]);
    }
}
