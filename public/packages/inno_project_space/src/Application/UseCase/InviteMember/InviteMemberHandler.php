<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\InviteMember;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\In\InviteMemberPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberInvited;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class InviteMemberHandler implements InviteMemberPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(InviteMemberCommand $command): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthException('Authentication required', [], false);
        }

        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $this->requireInitiatorOrAdmin($space);

            $space->addMember($command->userId);
            $this->repository->save($space);

            $this->eventDispatcher->dispatch(
                new MemberInvited($space->getId(), $command->userId, $space->getInitiatorId()),
                MemberInvited::NAME,
            );

            $this->logger->info('Member invited', ['spaceId' => $command->spaceId, 'userId' => $command->userId]);
        });
    }

    private function requireInitiatorOrAdmin(\Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace $space): void
    {
        if ($space->getInitiatorId() !== (string) $this->auth->getUserId() && !$this->auth->hasPermission('space.admin')) {
            throw new AuthException('Only the space initiator can perform this action', ['spaceId' => $space->getId()], true);
        }
    }
}
