<?php

// public/packages/inno_project_space/src/Application/EventHandler/OnUserAccountDeactivated.php
declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\EventHandler;

use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberRemoved;
use Concrete\Package\InnoShared\Domain\Event\UserAccountDeactivated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OnUserAccountDeactivated
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(UserAccountDeactivated $event): void
    {
        $userId = $event->getUserId();
        $spaces = $this->repository->findByMemberId($userId);

        foreach ($spaces as $space) {
            $space->removeMember($userId);
            $this->repository->save($space);

            $this->eventDispatcher->dispatch(
                new MemberRemoved($space->getId(), $userId, $event->getDeactivatedByUserId()),
                MemberRemoved::NAME,
            );

            $this->logger->info('Removed deactivated user from space', [
                'userId' => $userId,
                'spaceId' => $space->getId(),
            ]);
        }

        $this->logger->info('User account deactivation cleanup complete', [
            'userId' => $userId,
            'spacesRemoved' => count($spaces),
        ]);
    }
}
