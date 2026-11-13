<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\AddCollaboardLink;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Domain\Model\CollaboardLink;
use Concrete\Package\InnoProjectSpace\Port\In\AddCollaboardLinkPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

class AddCollaboardLinkHandler implements AddCollaboardLinkPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(AddCollaboardLinkCommand $command): CollaboardLink
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

            $link = $space->addCollaboardLink(
                id: uniqid('link_', true),
                title: $command->title,
                url: $command->url,
            );

            $this->repository->save($space);

            $this->logger->info('Collaboard link added', [
                'spaceId' => $command->spaceId,
                'linkId' => $link->getId(),
            ]);

            return $link;
        });
    }

    private function requireInitiatorOrAdmin(\Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace $space): void
    {
        if ($space->getInitiatorId() !== (string) $this->auth->getUserId() && !$this->auth->hasPermission('space.admin')) {
            throw new AuthException('Only the space initiator can perform this action', ['spaceId' => $space->getId()], true);
        }
    }
}
