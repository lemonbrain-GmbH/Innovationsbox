<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin\RequestToJoinCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin\RequestToJoinHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\RequestStatus;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestReceived;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() creates a pending join request and dispatches event', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('getUserId')->andReturn(42);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(JoinRequestReceived::class), JoinRequestReceived::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new RequestToJoinHandler($repo, $auth, $dispatcher, $tx, $logger);
    $request = $handler->handle(new RequestToJoinCommand('space_1'));

    expect($request->getStatus())->toBe(RequestStatus::Pending)
        ->and($request->getUserId())->toBe('42');
});
