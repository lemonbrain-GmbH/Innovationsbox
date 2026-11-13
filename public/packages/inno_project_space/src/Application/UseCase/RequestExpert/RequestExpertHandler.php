<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\In\RequestExpertPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RequestExpertHandler implements RequestExpertPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(RequestExpertCommand $command): void
    {
        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $userId = (string) $this->auth->getUserId();
            if ($space->getInitiatorId() !== $userId && !$this->auth->hasPermission('space.admin')) {
                throw new AuthException('Only the initiator or admin can request experts', [], $this->auth->isAuthenticated());
            }

            $space->requestExpert(
                id: uniqid('ereq_', true),
                expertUserId: $command->expertUserId,
                requestedByUserId: $userId,
                message: $command->message,
            );
            $this->repository->save($space);

            $this->eventDispatcher->dispatch(
                new ExpertRequested($space->getId(), $command->expertUserId, $userId, $command->message),
                ExpertRequested::NAME,
            );
            $this->logger->info('Expert requested', ['spaceId' => $space->getId(), 'expertUserId' => $command->expertUserId]);
        });
    }
}
