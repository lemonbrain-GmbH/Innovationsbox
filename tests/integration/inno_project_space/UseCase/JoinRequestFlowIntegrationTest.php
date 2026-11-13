<?php

declare(strict_types=1);

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectSpace\Adapter\Out\Persistence\ConcreteProjectSpaceRepository;
use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest\HandleJoinRequestCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest\HandleJoinRequestHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin\RequestToJoinCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin\RequestToJoinHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\MembershipRequest;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\RequestStatus;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestAccepted;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestReceived;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

beforeEach(function (): void {
    $this->db = app(Connection::class);
    $this->db->beginTransaction();

    $this->repo = new ConcreteProjectSpaceRepository($this->db);

    // Initiator auth (userId = 1)
    $this->initiatorAuth = Mockery::mock(AuthInterface::class);
    $this->initiatorAuth->shouldReceive('isAuthenticated')->andReturn(true);
    $this->initiatorAuth->shouldReceive('getUserId')->andReturn(1);
    $this->initiatorAuth->shouldReceive('hasPermission')->andReturn(false);

    // Requester auth (userId = 42, a different user)
    $this->requesterAuth = Mockery::mock(AuthInterface::class);
    $this->requesterAuth->shouldReceive('isAuthenticated')->andReturn(true);
    $this->requesterAuth->shouldReceive('getUserId')->andReturn(42);
    $this->requesterAuth->shouldReceive('hasPermission')->andReturn(false);

    $this->transaction = Mockery::mock(TransactionInterface::class);
    $this->transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $this->logger = Mockery::mock(LoggerInterface::class);
    $this->logger->shouldReceive('info');

    $this->dispatcher = Mockery::mock(EventDispatcherInterface::class);
});

afterEach(function (): void {
    $this->db->rollBack();
    Mockery::close();
});

function seedActiveJoinSpace(ConcreteProjectSpaceRepository $repo): ProjectSpace
{
    $space = ProjectSpace::createFromApprovedIdea(
        id: 'join_test_' . substr(md5(uniqid('', true)), 0, 8),
        projectIdeaId: 'idea_join_' . substr(md5(uniqid('', true)), 0, 6),
        title: 'Join Request Test Space',
        description: '<p>A space for join request tests.</p>',
        initiatorId: '1',
    );
    $space->activate();
    $repo->save($space);

    return $space;
}

test('user requests to join: membership request is persisted in the DB', function (): void {
    $space = seedActiveJoinSpace($this->repo);

    $this->dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(fn($event, $name) => $event instanceof JoinRequestReceived && $name === JoinRequestReceived::NAME);

    $handler = new RequestToJoinHandler(
        $this->repo,
        $this->requesterAuth,
        $this->dispatcher,
        $this->transaction,
        $this->logger,
    );

    $request = $handler->handle(new RequestToJoinCommand(spaceId: $space->getId()));

    expect($request)->toBeInstanceOf(MembershipRequest::class)
        ->and($request->getUserId())->toBe('42')
        ->and($request->getStatus())->toBe(RequestStatus::Pending);

    $found = $this->repo->findById($space->getId());
    $requests = $found->getMembershipRequests();

    expect($requests)->toHaveCount(1)
        ->and($requests[0]->getId())->toBe($request->getId())
        ->and($requests[0]->getUserId())->toBe('42')
        ->and($requests[0]->getStatus())->toBe(RequestStatus::Pending);
});

test('initiator accepts join request: requester becomes a member', function (): void {
    $space = seedActiveJoinSpace($this->repo);

    // Seed a pending membership request directly on the domain model and persist it
    $requestId = 'req_accept_' . substr(md5(uniqid('', true)), 0, 8);
    $space = $this->repo->findById($space->getId());
    $space->requestToJoin(requestId: $requestId, userId: '42');
    $this->repo->save($space);

    $this->dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(fn($event, $name) => $event instanceof JoinRequestAccepted && $name === JoinRequestAccepted::NAME);

    $handler = new HandleJoinRequestHandler(
        $this->repo,
        $this->initiatorAuth,
        $this->dispatcher,
        $this->transaction,
        $this->logger,
    );

    $handler->handle(new HandleJoinRequestCommand(
        spaceId: $space->getId(),
        requestId: $requestId,
        accept: true,
    ));

    $found = $this->repo->findById($space->getId());

    // Accepted requests are promoted to members — check memberIds, not requests
    expect($found->getMemberIds())->toContain('42');
});

test('initiator rejects join request: requester does NOT become a member', function (): void {
    $space = seedActiveJoinSpace($this->repo);

    // Seed a pending membership request directly on the domain model and persist it
    $requestId = 'req_reject_' . substr(md5(uniqid('', true)), 0, 8);
    $space = $this->repo->findById($space->getId());
    $space->requestToJoin(requestId: $requestId, userId: '42');
    $this->repo->save($space);

    $this->dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(fn($event, $name) => $event instanceof JoinRequestRejected && $name === JoinRequestRejected::NAME);

    $handler = new HandleJoinRequestHandler(
        $this->repo,
        $this->initiatorAuth,
        $this->dispatcher,
        $this->transaction,
        $this->logger,
    );

    $handler->handle(new HandleJoinRequestCommand(
        spaceId: $space->getId(),
        requestId: $requestId,
        accept: false,
    ));

    $found = $this->repo->findById($space->getId());

    expect($found->getMemberIds())->not->toContain('42');

    $requests = $found->getMembershipRequests();
    $rejected = array_values(array_filter($requests, fn(MembershipRequest $r) => $r->getId() === $requestId));

    expect($rejected)->toHaveCount(1)
        ->and($rejected[0]->getStatus())->toBe(RequestStatus::Rejected);
});
