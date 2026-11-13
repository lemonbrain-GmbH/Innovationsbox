<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea;

use Concrete\Package\InnoProjectIdea\Domain\Exception\IdeaNotEditableException;
use Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Port\In\DeleteProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

class DeleteProjectIdeaHandler implements DeleteProjectIdeaPort
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(DeleteProjectIdeaCommand $command): void
    {
        $this->transaction->transactional(function () use ($command) {
            $idea = $this->repository->findById($command->ideaId);
            if ($idea === null) {
                throw ProjectIdeaNotFoundException::withId($command->ideaId);
            }

            if ($idea->getSubmitterId() !== (string) $this->auth->getUserId() && !$this->auth->hasPermission('idea.admin')) {
                throw new AuthException('Only the idea owner can delete', [], $this->auth->isAuthenticated());
            }

            if ($idea->getStatus() !== IdeaStatus::Submitted) {
                throw IdeaNotEditableException::withStatus($idea->getStatus()->value);
            }

            $this->repository->delete($command->ideaId);
            $this->logger->info('Project idea deleted', ['ideaId' => $command->ideaId]);
        });
    }
}
