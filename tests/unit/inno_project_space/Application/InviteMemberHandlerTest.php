<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\InviteMember\InviteMemberCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\InviteMember\InviteMemberHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberInvited;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() adds member and dispatches MemberInvited event', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', '1');

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
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(MemberInvited::class), MemberInvited::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new InviteMemberHandler($repo, $auth, $dispatcher, $tx, $logger);
    $handler->handle(new InviteMemberCommand('space_1', 'user_2'));

    expect($space->getMemberIds())->toContain('user_2');
});
