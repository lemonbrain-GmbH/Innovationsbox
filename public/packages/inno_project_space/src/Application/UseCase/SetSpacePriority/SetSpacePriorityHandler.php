<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\SetSpacePriority;

use Concrete\Package\InnoProjectSpace\Port\In\SetSpacePriorityPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

class SetSpacePriorityHandler implements SetSpacePriorityPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(SetSpacePriorityCommand $command): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthException('Authentication required', [], false);
        }

        if (!$this->auth->hasPermission('space.admin')) {
            throw new AuthException('Only administrators can set space priority', [], true);
        }

        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw new \RuntimeException("Project space not found: {$command->spaceId}");
            }

            if ($command->priority > 0 && $space->getPriority() === 0) {
                $count = $this->repository->countByPriority();
                if ($count >= 3) {
                    throw new \DomainException('Maximum of 3 priority spaces reached');
                }
            }

            $space->setPriority($command->priority);
            $this->repository->save($space);

            $this->logger->info('Space priority updated', [
                'spaceId' => $command->spaceId,
                'priority' => $command->priority,
            ]);
        });
    }
}
