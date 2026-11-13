<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert\RequestExpertCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert\RequestExpertHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() creates expert request and dispatches event', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', '1', 'cat_1');
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('s_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(false);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(ExpertRequested::class), ExpertRequested::NAME);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new RequestExpertHandler($repo, $auth, $dispatcher, $transaction, $logger);
    $handler->handle(new RequestExpertCommand('s_1', 'expert_5', 'Need solar help'));

    expect($space->getExpertRequests())->toHaveCount(1);
});
