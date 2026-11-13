<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\DeleteProjectSpace;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
use Concrete\Package\InnoProjectSpace\Port\In\DeleteProjectSpacePort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

class DeleteProjectSpaceHandler implements DeleteProjectSpacePort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(DeleteProjectSpaceCommand $command): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthException('Authentication required', [], false);
        }

        if (!$this->auth->hasPermission('space.admin')) {
            throw new AuthException('Only administrators can delete spaces', [], true);
        }

        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            if ($space->getStatus() !== SpaceStatus::Archived) {
                throw new \DomainException('Only archived spaces can be deleted.');
            }

            $this->repository->delete($command->spaceId);

            $this->logger->info('Project space deleted', [
                'spaceId' => $command->spaceId,
                'deletedBy' => $this->auth->getUserId(),
            ]);
        });
    }
}
