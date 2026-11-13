<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\SetSpacePriority\SetSpacePriorityCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\SetSpacePriority\SetSpacePriorityHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

afterEach(fn() => Mockery::close());

function makeSetPriorityMocks(): array
{
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    return [$repo, $auth, $tx, $logger];
}

test('sets priority on a space and saves', function () {
    [$repo, $auth, $tx, $logger] = makeSetPriorityMocks();
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', '1');

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('countByPriority')->andReturn(0);
    $repo->shouldReceive('save')->once()->with($space);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(true);

    $handler = new SetSpacePriorityHandler($repo, $auth, $tx, $logger);
    $handler->handle(new SetSpacePriorityCommand(spaceId: 'space_1', priority: 1));

    expect($space->getPriority())->toBe(1);
});

test('clears priority (set to 0) without checking max constraint', function () {
    [$repo, $auth, $tx, $logger] = makeSetPriorityMocks();
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', '1');
    $space->setPriority(2);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once()->with($space);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(true);

    $handler = new SetSpacePriorityHandler($repo, $auth, $tx, $logger);
    $handler->handle(new SetSpacePriorityCommand(spaceId: 'space_1', priority: 0));

    expect($space->getPriority())->toBe(0);
});

test('rejects setting priority when 3 spaces already have priority', function () {
    [$repo, $auth, $tx, $logger] = makeSetPriorityMocks();
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', '1');

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('countByPriority')->andReturn(3);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(true);

    $handler = new SetSpacePriorityHandler($repo, $auth, $tx, $logger);
    $handler->handle(new SetSpacePriorityCommand(spaceId: 'space_1', priority: 1));
})->throws(\DomainException::class, 'Maximum of 3 priority spaces reached');

test('allows changing priority on already-prioritized space even at max', function () {
    [$repo, $auth, $tx, $logger] = makeSetPriorityMocks();
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', '1');
    $space->setPriority(1);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once()->with($space);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(true);

    $handler = new SetSpacePriorityHandler($repo, $auth, $tx, $logger);
    $handler->handle(new SetSpacePriorityCommand(spaceId: 'space_1', priority: 2));

    expect($space->getPriority())->toBe(2);
});

test('throws AuthException if not authenticated', function () {
    [$repo, $auth, $tx, $logger] = makeSetPriorityMocks();

    $auth->shouldReceive('isAuthenticated')->andReturn(false);

    $handler = new SetSpacePriorityHandler($repo, $auth, $tx, $logger);
    $handler->handle(new SetSpacePriorityCommand(spaceId: 'space_1', priority: 1));
})->throws(AuthException::class);

test('throws AuthException if user lacks space.admin permission', function () {
    [$repo, $auth, $tx, $logger] = makeSetPriorityMocks();

    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(false);

    $handler = new SetSpacePriorityHandler($repo, $auth, $tx, $logger);
    $handler->handle(new SetSpacePriorityCommand(spaceId: 'space_1', priority: 1));
})->throws(AuthException::class);

test('throws exception if space not found', function () {
    [$repo, $auth, $tx, $logger] = makeSetPriorityMocks();

    $repo->shouldReceive('findById')->with('space_1')->andReturn(null);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(true);

    $handler = new SetSpacePriorityHandler($repo, $auth, $tx, $logger);
    $handler->handle(new SetSpacePriorityCommand(spaceId: 'space_1', priority: 1));
})->throws(\Exception::class);
