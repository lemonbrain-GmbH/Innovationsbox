<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest\HandleExpertRequestCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest\HandleExpertRequestHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequestAccepted;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequestDeclined;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() accept adds expert to members', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', '1', 'cat_1');
    $space->requestExpert('req_1', '5', '1', null);

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('s_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('getUserId')->andReturn(5);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(ExpertRequestAccepted::class), ExpertRequestAccepted::NAME);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new HandleExpertRequestHandler($repo, $auth, $dispatcher, $transaction, $logger);
    $handler->handle(new HandleExpertRequestCommand('s_1', 'req_1', true));

    expect($space->getMemberIds())->toContain('5');
});

test('handle() decline does not add expert to members', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', '1', 'cat_1');
    $space->requestExpert('req_1', '5', '1', null);

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('s_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('getUserId')->andReturn(5);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(ExpertRequestDeclined::class), ExpertRequestDeclined::NAME);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new HandleExpertRequestHandler($repo, $auth, $dispatcher, $transaction, $logger);
    $handler->handle(new HandleExpertRequestCommand('s_1', 'req_1', false));

    expect($space->getMemberIds())->not->toContain('5');
});

test('handle() throws if user is not the requested expert', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', '1', 'cat_1');
    $space->requestExpert('req_1', '5', '1', null);

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('s_1')->andReturn($space);
    $auth->shouldReceive('getUserId')->andReturn(999);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new HandleExpertRequestHandler($repo, $auth, $dispatcher, $transaction, $logger);
    $handler->handle(new HandleExpertRequestCommand('s_1', 'req_1', true));
})->throws(AuthException::class);
