<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Domain\Model\MembershipRequest;
use Concrete\Package\InnoProjectSpace\Port\In\RequestToJoinPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestReceived;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RequestToJoinHandler implements RequestToJoinPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(RequestToJoinCommand $command): MembershipRequest
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthException('Authentication required', [], false);
        }

        return $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $request = $space->requestToJoin(
                requestId: uniqid('req_', true),
                userId: (string) $this->auth->getUserId(),
            );

            $this->repository->save($space);

            $this->eventDispatcher->dispatch(
                new JoinRequestReceived($space->getId(), $request->getId(), $request->getUserId()),
                JoinRequestReceived::NAME,
            );

            $this->logger->info('Join request received', ['spaceId' => $command->spaceId, 'requestId' => $request->getId()]);

            return $request;
        });
    }
}
