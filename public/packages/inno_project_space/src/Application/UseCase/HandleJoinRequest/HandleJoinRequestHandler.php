<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\In\HandleJoinRequestPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestAccepted;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class HandleJoinRequestHandler implements HandleJoinRequestPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(HandleJoinRequestCommand $command): void
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

            if ($command->accept) {
                $request = $space->acceptJoinRequest($command->requestId);
                $this->eventDispatcher->dispatch(
                    new JoinRequestAccepted($space->getId(), $request->getId(), $request->getUserId()),
                    JoinRequestAccepted::NAME,
                );
            } else {
                $request = $space->rejectJoinRequest($command->requestId);
                $this->eventDispatcher->dispatch(
                    new JoinRequestRejected($space->getId(), $request->getId(), $request->getUserId()),
                    JoinRequestRejected::NAME,
                );
            }

            $this->repository->save($space);
            $this->logger->info('Join request handled', ['spaceId' => $command->spaceId, 'requestId' => $command->requestId, 'accepted' => $command->accept]);
        });
    }

    private function requireInitiatorOrAdmin(\Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace $space): void
    {
        if ($space->getInitiatorId() !== (string) $this->auth->getUserId() && !$this->auth->hasPermission('space.admin')) {
            throw new AuthException('Only the space initiator can perform this action', ['spaceId' => $space->getId()], true);
        }
    }
}
