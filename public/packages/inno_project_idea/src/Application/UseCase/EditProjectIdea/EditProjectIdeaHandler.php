<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea;

use Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Port\In\EditProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaResubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EditProjectIdeaHandler implements EditProjectIdeaPort
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handle(EditProjectIdeaCommand $command): void
    {
        $this->transaction->transactional(function () use ($command) {
            $idea = $this->repository->findById($command->ideaId);
            if ($idea === null) {
                throw ProjectIdeaNotFoundException::withId($command->ideaId);
            }

            if ($idea->getSubmitterId() !== (string) $this->auth->getUserId() && !$this->auth->hasPermission('idea.admin')) {
                throw new AuthException('Only the idea owner can edit', [], $this->auth->isAuthenticated());
            }

            $wasFeedbackRequested = $idea->getStatus() === IdeaStatus::FeedbackRequested;

            $idea->edit($command->title, $command->description, $command->category);
            $this->repository->save($idea);

            // If the idea was in FeedbackRequested, editing resubmits it — notify moderation to re-queue
            if ($wasFeedbackRequested) {
                $this->eventDispatcher->dispatch(
                    new ProjectIdeaResubmitted($idea->getId()),
                    ProjectIdeaResubmitted::NAME,
                );
                $this->logger->info('Project idea resubmitted after feedback', ['ideaId' => $command->ideaId]);
            }

            $this->logger->info('Project idea edited', ['ideaId' => $command->ideaId]);
        });
    }
}
