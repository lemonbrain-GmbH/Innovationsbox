<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\LeaveSpace;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\In\LeaveSpacePort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberLeft;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LeaveSpaceHandler implements LeaveSpacePort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(LeaveSpaceCommand $command): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthException('Authentication required', [], false);
        }

        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $userId = (string) $this->auth->getUserId();
            $space->removeMember($userId);

            $this->eventDispatcher->dispatch(
                new MemberLeft($space->getId(), $userId),
                MemberLeft::NAME,
            );

            $this->repository->save($space);
            $this->logger->info('Member left space', ['spaceId' => $command->spaceId, 'userId' => $userId]);
        });
    }
}
