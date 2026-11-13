<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea;

use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\In\SubmitProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SubmitProjectIdeaHandler implements SubmitProjectIdeaPort
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(SubmitProjectIdeaCommand $command): ProjectIdea
    {
        return $this->transaction->transactional(function () use ($command) {
            $idea = ProjectIdea::submit(
                id: uniqid('idea_', true),
                title: $command->title,
                description: $command->description,
                category: $command->category,
                submitterId: $command->submitterId,
            );

            $this->repository->save($idea);

            $this->eventDispatcher->dispatch(
                new ProjectIdeaSubmitted($idea->getId(), $idea->getSubmitterId(), $idea->getTitle()),
                ProjectIdeaSubmitted::NAME,
            );

            $this->logger->info('Project idea submitted', [
                'ideaId' => $idea->getId(),
                'submitterId' => $idea->getSubmitterId(),
            ]);

            return $idea;
        });
    }
}
