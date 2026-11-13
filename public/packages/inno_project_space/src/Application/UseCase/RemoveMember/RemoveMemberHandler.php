<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveMember;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\In\RemoveMemberPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberRemoved;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RemoveMemberHandler implements RemoveMemberPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(RemoveMemberCommand $command): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthException('Authentication required', [], false);
        }

        if (!$this->auth->hasPermission('space.admin')) {
            throw new AuthException('Only admins can remove members from a space', [], true);
        }

        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $removedByUserId = (string) $this->auth->getUserId();
            $space->removeMember($command->userId);

            $this->eventDispatcher->dispatch(
                new MemberRemoved($space->getId(), $command->userId, $removedByUserId),
                MemberRemoved::NAME,
            );

            $this->repository->save($space);
            $this->logger->info('Member removed from space', [
                'spaceId' => $command->spaceId,
                'userId' => $command->userId,
                'removedByUserId' => $removedByUserId,
            ]);
        });
    }
}
