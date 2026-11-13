<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\EventHandler;

use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationFeedbackRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;

class OnModerationFeedbackRequested
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
        private readonly TransactionInterface $transaction,
    ) {}

    public function handle(ModerationFeedbackRequested $event): void
    {
        $idea = $this->repository->findById($event->getIdeaId());
        if ($idea === null) {
            $this->logger->warning('Idea not found for feedback request', [
                'ideaId' => $event->getIdeaId(),
            ]);

            return;
        }

        $this->transaction->transactional(function () use ($event, $idea) {
            $idea->requestFeedback($event->getNotes(), $event->getDeadline());
            $this->repository->save($idea);
            $this->logger->info('Project idea feedback requested', ['ideaId' => $idea->getId()]);
        });
    }
}
