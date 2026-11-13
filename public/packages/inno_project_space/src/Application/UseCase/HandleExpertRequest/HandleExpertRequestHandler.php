<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\In\HandleExpertRequestPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequestAccepted;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequestDeclined;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class HandleExpertRequestHandler implements HandleExpertRequestPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(HandleExpertRequestCommand $command): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthException('Authentication required', [], false);
        }

        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $request = null;
            foreach ($space->getExpertRequests() as $req) {
                if ($req->getId() === $command->requestId) {
                    $request = $req;
                    break;
                }
            }
            if ($request === null) {
                throw new \DomainException("Expert request '{$command->requestId}' not found.");
            }

            $userId = (string) $this->auth->getUserId();
            if ($request->getExpertUserId() !== $userId) {
                throw new AuthException('Only the requested expert can respond', [], $this->auth->isAuthenticated());
            }

            if ($command->accept) {
                $space->acceptExpertRequest($command->requestId);
                $this->eventDispatcher->dispatch(
                    new ExpertRequestAccepted($space->getId(), $userId, $command->requestId),
                    ExpertRequestAccepted::NAME,
                );
            } else {
                $space->declineExpertRequest($command->requestId);
                $this->eventDispatcher->dispatch(
                    new ExpertRequestDeclined($space->getId(), $userId, $command->requestId),
                    ExpertRequestDeclined::NAME,
                );
            }

            $this->repository->save($space);
            $this->logger->info('Expert request handled', ['requestId' => $command->requestId, 'accepted' => $command->accept]);
        });
    }
}
