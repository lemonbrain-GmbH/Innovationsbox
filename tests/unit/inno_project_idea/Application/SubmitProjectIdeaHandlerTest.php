<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() saves the idea and dispatches ProjectIdeaSubmitted within transaction', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('save')->once()->with(Mockery::type(ProjectIdea::class));
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(ProjectIdeaSubmitted::class), ProjectIdeaSubmitted::NAME);
    $transaction->shouldReceive('transactional')
        ->once()
        ->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new SubmitProjectIdeaHandler($repo, $dispatcher, $transaction, $logger);
    $idea = $handler->handle(new SubmitProjectIdeaCommand(
        title: 'My Idea',
        description: 'Great description',
        category: 'tech',
        submitterId: 'user_1',
    ));
    expect($idea->getTitle())->toBe('My Idea')
        ->and($idea->getSubmitterId())->toBe('user_1');
});

test('handle() returns ProjectIdea with a non-empty id', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('save')->once();
    $dispatcher->shouldReceive('dispatch')->once();
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new SubmitProjectIdeaHandler($repo, $dispatcher, $transaction, $logger);
    $idea = $handler->handle(new SubmitProjectIdeaCommand('Title', 'Desc', 'cat', 'user_1'));
    expect($idea->getId())->not->toBeEmpty();
});
