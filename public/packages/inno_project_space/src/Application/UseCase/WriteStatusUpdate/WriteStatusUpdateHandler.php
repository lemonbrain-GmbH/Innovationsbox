<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdate;
use Concrete\Package\InnoProjectSpace\Port\In\WriteStatusUpdatePort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\StatusUpdatePublished;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class WriteStatusUpdateHandler implements WriteStatusUpdatePort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(WriteStatusUpdateCommand $command): StatusUpdate
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthException('Authentication required', [], false);
        }

        return $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $this->requireInitiatorOrAdmin($space);

            $sanitizedBody = strip_tags($command->body, '<p><br><strong><em><ul><ol><li><a><h2><h3><h4>');
            $update = $space->addStatusUpdate(
                id: uniqid('upd_', true),
                title: $command->title,
                body: $sanitizedBody,
                authorId: (string) $this->auth->getUserId(),
            );

            $this->repository->save($space);

            $this->eventDispatcher->dispatch(
                new StatusUpdatePublished($space->getId(), $update->getId(), $update->getAuthorId()),
                StatusUpdatePublished::NAME,
            );

            $this->logger->info('Status update published', ['spaceId' => $command->spaceId, 'updateId' => $update->getId()]);

            return $update;
        });
    }

    private function requireInitiatorOrAdmin(\Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace $space): void
    {
        if ($space->getInitiatorId() !== (string) $this->auth->getUserId() && !$this->auth->hasPermission('space.admin')) {
            throw new AuthException('Only the space initiator can perform this action', ['spaceId' => $space->getId()], true);
        }
    }
}
