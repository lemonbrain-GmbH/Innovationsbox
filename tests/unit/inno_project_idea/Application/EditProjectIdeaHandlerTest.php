<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea\EditProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea\EditProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

afterEach(fn() => Mockery::close());

test('handle() edits a submitted idea', function () {
    $idea = ProjectIdea::submit('idea_1', 'Old', 'Old desc', 'cat_1', '1');
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('idea_1')->andReturn($idea);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->andReturn(false);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $dispatcher = Mockery::mock(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->zeroOrMoreTimes();
    $handler = new EditProjectIdeaHandler($repo, $auth, $transaction, $logger, $dispatcher);
    $handler->handle(new EditProjectIdeaCommand('idea_1', 'New Title', 'New desc', 'cat_2'));

    expect($idea->getTitle())->toBe('New Title');
});

test('handle() throws when idea not found', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('missing')->andReturn(null);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $dispatcher = Mockery::mock(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->zeroOrMoreTimes();
    $handler = new EditProjectIdeaHandler($repo, $auth, $transaction, $logger, $dispatcher);
    $handler->handle(new EditProjectIdeaCommand('missing', 'T', 'D', 'C'));
})->throws(ProjectIdeaNotFoundException::class);

test('handle() throws when user is not the owner', function () {
    $idea = ProjectIdea::submit('idea_1', 'T', 'D', 'cat_1', 'user_1');
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('idea_1')->andReturn($idea);
    $auth->shouldReceive('getUserId')->andReturn(999);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('idea.admin')->andReturn(false);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $dispatcher = Mockery::mock(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->zeroOrMoreTimes();
    $handler = new EditProjectIdeaHandler($repo, $auth, $transaction, $logger, $dispatcher);
    $handler->handle(new EditProjectIdeaCommand('idea_1', 'T', 'D', 'C'));
})->throws(AuthException::class);
