<?php

// tests/unit/inno_project_space/Application/OnUserAccountDeactivatedTest.php
declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\EventHandler\OnUserAccountDeactivated;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberRemoved;
use Concrete\Package\InnoShared\Domain\Event\UserAccountDeactivated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() removes user from all spaces and dispatches MemberRemoved events', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $space1 = ProjectSpace::createFromApprovedIdea('s1', 'i1', 'Space 1', 'Desc', 'initiator_1', 'cat_1');
    $space1->requestToJoin('req_1', 'user_42');
    $space1->acceptJoinRequest('req_1');

    $space2 = ProjectSpace::createFromApprovedIdea('s2', 'i2', 'Space 2', 'Desc', 'initiator_2', 'cat_1');
    $space2->requestToJoin('req_2', 'user_42');
    $space2->acceptJoinRequest('req_2');

    $repo->shouldReceive('findByMemberId')
        ->with('user_42')
        ->andReturn([$space1, $space2]);

    $repo->shouldReceive('save')->twice();

    $dispatcher->shouldReceive('dispatch')
        ->twice()
        ->with(Mockery::type(MemberRemoved::class), MemberRemoved::NAME);

    $logger->shouldReceive('info')->times(3); // 2 removals + 1 summary

    $handler = new OnUserAccountDeactivated($repo, $dispatcher, $logger);
    $handler->handle(new UserAccountDeactivated('user_42', 'u@test.ch', 'User', '', 'user_42'));
});

test('handle() does nothing when user has no space memberships', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findByMemberId')
        ->with('user_99')
        ->andReturn([]);

    $repo->shouldReceive('save')->never();
    $dispatcher->shouldReceive('dispatch')->never();
    $logger->shouldReceive('info')->once();

    $handler = new OnUserAccountDeactivated($repo, $dispatcher, $logger);
    $handler->handle(new UserAccountDeactivated('user_99', 'u@test.ch', 'User', '', 'user_99'));
});
