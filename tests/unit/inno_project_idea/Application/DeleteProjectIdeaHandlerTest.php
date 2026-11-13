<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea\DeleteProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea\DeleteProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Domain\Exception\IdeaNotEditableException;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;

afterEach(fn() => Mockery::close());

test('handle() deletes a submitted idea', function () {
    $idea = ProjectIdea::submit('idea_1', 'T', 'D', 'cat_1', '1');
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('idea_1')->andReturn($idea);
    $repo->shouldReceive('delete')->once()->with('idea_1');
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->andReturn(false);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new DeleteProjectIdeaHandler($repo, $auth, $transaction, $logger);
    $handler->handle(new DeleteProjectIdeaCommand('idea_1'));
});

test('handle() throws when idea is not submitted (approved)', function () {
    $idea = ProjectIdea::submit('idea_1', 'T', 'D', 'cat_1', '1');
    $idea->approve();
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('idea_1')->andReturn($idea);
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->andReturn(false);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new DeleteProjectIdeaHandler($repo, $auth, $transaction, $logger);
    $handler->handle(new DeleteProjectIdeaCommand('idea_1'));
})->throws(IdeaNotEditableException::class);
