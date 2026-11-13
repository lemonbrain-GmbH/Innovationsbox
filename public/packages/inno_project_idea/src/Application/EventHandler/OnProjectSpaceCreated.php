<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\EventHandler;

use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCreated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class OnProjectSpaceCreated
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectSpaceCreated $event): void
    {
        $idea = $this->repository->findById($event->getProjectIdeaId());
        if ($idea === null) {
            return;
        }

        $idea->setProjectSpaceId($event->getSpaceId());
        $this->repository->save($idea);

        $this->logger->info('Linked project space to idea', [
            'ideaId' => $event->getProjectIdeaId(),
            'spaceId' => $event->getSpaceId(),
        ]);
    }
}
