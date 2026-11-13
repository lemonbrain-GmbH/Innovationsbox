<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\ChangeSpaceStatus;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
use Concrete\Package\InnoProjectSpace\Port\In\ChangeSpaceStatusPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceActivated;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCompleted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ChangeSpaceStatusHandler implements ChangeSpaceStatusPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ChangeSpaceStatusCommand $command): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthException('Authentication required', [], false);
        }

        $newStatus = SpaceStatus::from($command->newStatus);

        $this->transaction->transactional(function () use ($command, $newStatus) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            // Archive is admin-only; other transitions require initiator or admin
            if ($newStatus === SpaceStatus::Archived) {
                if (!$this->auth->hasPermission('space.admin')) {
                    throw new AuthException('Only administrators can archive spaces', ['spaceId' => $space->getId()], true);
                }
            } else {
                $this->requireInitiatorOrAdmin($space);
            }

            match ($newStatus) {
                SpaceStatus::Active => $space->getStatus() === SpaceStatus::Completed ? $space->reopen() : $space->activate(),
                SpaceStatus::Completed => $space->getStatus() === SpaceStatus::Archived ? $space->dearchive() : $space->complete(),
                SpaceStatus::Archived => $space->archive(),
                default => throw new \DomainException("Cannot transition to '{$newStatus->value}'."),
            };

            $this->repository->save($space);

            if ($newStatus === SpaceStatus::Active) {
                $this->eventDispatcher->dispatch(
                    new ProjectSpaceActivated($space->getId(), $space->getInitiatorId()),
                    ProjectSpaceActivated::NAME,
                );
            } elseif ($newStatus === SpaceStatus::Completed) {
                $this->eventDispatcher->dispatch(
                    new ProjectSpaceCompleted($space->getId(), $space->getInitiatorId()),
                    ProjectSpaceCompleted::NAME,
                );
            }

            $this->logger->info('Space status changed', ['spaceId' => $command->spaceId, 'newStatus' => $newStatus->value]);
        });
    }

    private function requireInitiatorOrAdmin(\Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace $space): void
    {
        if ($space->getInitiatorId() !== (string) $this->auth->getUserId() && !$this->auth->hasPermission('space.admin')) {
            throw new AuthException('Only the space initiator can perform this action', ['spaceId' => $space->getId()], true);
        }
    }
}
