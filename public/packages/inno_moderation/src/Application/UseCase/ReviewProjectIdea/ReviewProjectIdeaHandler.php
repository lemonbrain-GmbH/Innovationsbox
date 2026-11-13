<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea;

use Concrete\Package\InnoModeration\Domain\Exception\ModerationReviewNotFoundException;
use Concrete\Package\InnoModeration\Port\In\ReviewProjectIdeaPort;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Domain\Event\ModerationFeedbackRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ReviewProjectIdeaHandler implements ReviewProjectIdeaPort
{
    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ReviewProjectIdeaCommand $command): void
    {
        $this->transaction->transactional(function () use ($command) {
            $review = $this->repository->findById($command->reviewId);
            if ($review === null) {
                throw ModerationReviewNotFoundException::withId($command->reviewId);
            }

            if ($command->feedbackRequested) {
                $review->requestFeedback($command->reviewerId, $command->notes, $command->feedbackDeadline);
                $this->repository->save($review);

                $this->eventDispatcher->dispatch(
                    new ModerationFeedbackRequested(
                        ideaId: $review->getIdeaId(),
                        reviewerId: $command->reviewerId,
                        notes: $command->notes,
                        deadline: $command->feedbackDeadline,
                    ),
                    ModerationFeedbackRequested::NAME,
                );

                $this->logger->info('Moderation feedback requested', [
                    'reviewId' => $command->reviewId,
                    'reviewerId' => $command->reviewerId,
                ]);
            } elseif ($command->approved) {
                $review->approve(
                    $command->reviewerId,
                    $command->notes,
                    $command->titleOverride,
                    $command->descriptionOverride,
                    $command->categoryOverride,
                );
                $this->repository->save($review);

                $this->eventDispatcher->dispatch(
                    new ModerationDecisionMade(
                        ideaId: $review->getIdeaId(),
                        reviewerId: $command->reviewerId,
                        approved: $command->approved,
                        reason: $command->notes,
                        titleOverride: $command->titleOverride,
                        descriptionOverride: $command->descriptionOverride,
                        categoryOverride: $command->categoryOverride,
                    ),
                    ModerationDecisionMade::NAME,
                );

                $this->logger->info('Moderation decision made', [
                    'reviewId' => $command->reviewId,
                    'approved' => $command->approved,
                    'reviewerId' => $command->reviewerId,
                ]);
            } else {
                $review->reject($command->reviewerId, $command->notes);
                $this->repository->save($review);

                $this->eventDispatcher->dispatch(
                    new ModerationDecisionMade(
                        ideaId: $review->getIdeaId(),
                        reviewerId: $command->reviewerId,
                        approved: $command->approved,
                        reason: $command->notes,
                    ),
                    ModerationDecisionMade::NAME,
                );

                $this->logger->info('Moderation decision made', [
                    'reviewId' => $command->reviewId,
                    'approved' => $command->approved,
                    'reviewerId' => $command->reviewerId,
                ]);
            }
        });
    }
}
