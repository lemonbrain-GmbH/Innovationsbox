<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate\WriteStatusUpdateCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate\WriteStatusUpdateHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\StatusUpdatePublished;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() adds status update, saves, and dispatches event', function () {
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
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(StatusUpdatePublished::class), StatusUpdatePublished::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new WriteStatusUpdateHandler($repo, $auth, $dispatcher, $tx, $logger);
    $update = $handler->handle(new WriteStatusUpdateCommand('space_1', 'Kickoff', '<p>Done!</p>'));

    expect($update->getTitle())->toBe('Kickoff')
        ->and($space->getStatusUpdates())->toHaveCount(1);
});

test('handle() strips unsafe HTML from body', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', '1');
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->andReturn($space);
    $repo->shouldReceive('save');
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(false);
    $dispatcher->shouldReceive('dispatch');
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new WriteStatusUpdateHandler($repo, $auth, $dispatcher, $tx, $logger);
    $update = $handler->handle(new WriteStatusUpdateCommand('space_1', 'Test', '<p>Safe</p><script>alert("xss")</script>'));

    expect($update->getBody())->toBe('<p>Safe</p>alert("xss")');
});
