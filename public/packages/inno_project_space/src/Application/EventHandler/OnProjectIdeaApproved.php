<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\EventHandler;

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCreated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OnProjectIdeaApproved
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly TransactionInterface $transaction,
    ) {}

    public function handle(ProjectIdeaApproved $event): void
    {
        $this->transaction->transactional(function () use ($event) {
            $space = ProjectSpace::createFromApprovedIdea(
                id: uniqid('space_', true),
                projectIdeaId: $event->getIdeaId(),
                title: $event->getTitle() ?: "Innovation – {$event->getIdeaId()}",
                description: $event->getDescription(),
                initiatorId: $event->getSubmitterId(),
                categoryId: $event->getCategoryId(),
                headerImageFileId: $event->getHeaderImageFileId(),
            );

            $this->repository->save($space);

            $this->eventDispatcher->dispatch(
                new ProjectSpaceCreated($space->getId(), $event->getIdeaId(), $event->getSubmitterId()),
                ProjectSpaceCreated::NAME,
            );

            $this->logger->info('Project space created', [
                'spaceId' => $space->getId(),
                'ideaId' => $event->getIdeaId(),
            ]);
        });
    }
}
