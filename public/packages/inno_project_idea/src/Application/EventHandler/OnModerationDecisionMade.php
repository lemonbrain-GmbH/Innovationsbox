<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\EventHandler;

use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OnModerationDecisionMade
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly TransactionInterface $transaction,
    ) {}

    public function handle(ModerationDecisionMade $event): void
    {
        $idea = $this->repository->findById($event->getIdeaId());
        if ($idea === null) {
            $this->logger->warning('Idea not found for moderation decision', [
                'ideaId' => $event->getIdeaId(),
            ]);

            return;
        }

        $this->transaction->transactional(function () use ($event, $idea) {
            if ($event->isApproved()) {
                // Apply moderator overrides before approving
                if ($event->getTitleOverride() !== null || $event->getDescriptionOverride() !== null || $event->getCategoryOverride() !== null) {
                    $idea->applyOverrides(
                        $event->getTitleOverride(),
                        $event->getDescriptionOverride(),
                        $event->getCategoryOverride(),
                    );
                }
                $idea->approve();
                $this->repository->save($idea);
                $this->eventDispatcher->dispatch(
                    new ProjectIdeaApproved(
                        $idea->getId(),
                        $idea->getSubmitterId(),
                        $idea->getCategory(),
                        $idea->getTitle(),
                        $idea->getDescription(),
                        $idea->getHeaderImageFileId(),
                    ),
                    ProjectIdeaApproved::NAME,
                );
                $this->logger->info('Project idea approved', ['ideaId' => $idea->getId()]);
            } else {
                $idea->reject($event->getReason());
                $this->repository->save($idea);
                $this->eventDispatcher->dispatch(
                    new ProjectIdeaRejected($idea->getId(), $idea->getSubmitterId(), $event->getReason()),
                    ProjectIdeaRejected::NAME,
                );
                $this->logger->info('Project idea rejected', ['ideaId' => $idea->getId()]);
            }
        });
    }
}
