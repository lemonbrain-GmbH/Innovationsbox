<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\EventHandler\OnProjectIdeaApproved;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCreated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() creates a project space in Draft status and dispatches ProjectSpaceCreated', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);

    $repo->shouldReceive('save')->once()->with(Mockery::on(function (ProjectSpace $space) {
        return $space->getStatus() === SpaceStatus::Active
            && $space->getInitiatorId() === 'user_1'
            && $space->getMemberIds() === ['user_1'];
    }));
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(ProjectSpaceCreated::class), ProjectSpaceCreated::NAME);
    $logger->shouldReceive('info')->once();
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new OnProjectIdeaApproved($repo, $dispatcher, $logger, $transaction);
    $handler->handle(new ProjectIdeaApproved('idea_1', 'user_1'));
});

test('handle() creates a space with categoryId from event', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
    $transaction = Mockery::mock(TransactionInterface::class);

    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $repo->shouldReceive('save')->once()->with(Mockery::on(function (ProjectSpace $space) {
        return $space->getCategoryId() === 'cat_mobilitaet';
    }));
    $dispatcher->shouldReceive('dispatch')->once();

    $handler = new OnProjectIdeaApproved($repo, $dispatcher, $logger, $transaction);
    $handler->handle(new ProjectIdeaApproved('idea_1', 'user_1', 'cat_mobilitaet'));
});
