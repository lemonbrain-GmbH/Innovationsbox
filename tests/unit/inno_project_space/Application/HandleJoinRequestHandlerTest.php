<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest\HandleJoinRequestCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest\HandleJoinRequestHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\RequestStatus;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestAccepted;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() accepts a join request and dispatches JoinRequestAccepted', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', '1');
    $space->requestToJoin('req_1', 'user_2');

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(false);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(JoinRequestAccepted::class), JoinRequestAccepted::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new HandleJoinRequestHandler($repo, $auth, $dispatcher, $tx, $logger);
    $handler->handle(new HandleJoinRequestCommand('space_1', 'req_1', true));

    expect($space->getMemberIds())->toContain('user_2');
});

test('handle() rejects a join request and dispatches JoinRequestRejected', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', '1');
    $space->requestToJoin('req_1', 'user_2');

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(false);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(JoinRequestRejected::class), JoinRequestRejected::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new HandleJoinRequestHandler($repo, $auth, $dispatcher, $tx, $logger);
    $handler->handle(new HandleJoinRequestCommand('space_1', 'req_1', false));

    expect($space->getMembershipRequests()[0]->getStatus())->toBe(RequestStatus::Rejected);
});
