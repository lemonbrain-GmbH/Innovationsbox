<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveMember\RemoveMemberCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveMember\RemoveMemberHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberRemoved;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() removes member and dispatches MemberRemoved when admin', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', '1');
    $space->addMember('2');

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(true);
    $auth->shouldReceive('getUserId')->andReturn(99); // admin user
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(MemberRemoved::class), MemberRemoved::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new RemoveMemberHandler($repo, $auth, $dispatcher, $tx, $logger);
    $handler->handle(new RemoveMemberCommand('space_1', '2'));

    expect($space->getMemberIds())->not->toContain('2');
});

test('handle() throws DomainException when admin tries to remove initiator', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', '1');

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(true);
    $auth->shouldReceive('getUserId')->andReturn(99);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new RemoveMemberHandler($repo, $auth, $dispatcher, $tx, $logger);

    expect(fn() => $handler->handle(new RemoveMemberCommand('space_1', '1')))
        ->toThrow(\DomainException::class, 'initiator cannot be removed');
});

test('handle() throws AuthException when not authenticated', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $auth->shouldReceive('isAuthenticated')->andReturn(false);

    $handler = new RemoveMemberHandler($repo, $auth, $dispatcher, $tx, $logger);

    expect(fn() => $handler->handle(new RemoveMemberCommand('space_1', '2')))
        ->toThrow(AuthException::class);
});

test('handle() throws AuthException when authenticated but not admin', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(false);

    $handler = new RemoveMemberHandler($repo, $auth, $dispatcher, $tx, $logger);

    expect(fn() => $handler->handle(new RemoveMemberCommand('space_1', '2')))
        ->toThrow(AuthException::class);
});
