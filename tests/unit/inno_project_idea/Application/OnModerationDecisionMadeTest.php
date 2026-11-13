<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Application\EventHandler\OnModerationDecisionMade;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

function makeIdea(string $id = 'idea_1', string $submitterId = 'user_1'): ProjectIdea
{
    return ProjectIdea::submit($id, 'Test Idea', 'Description', 'cat_1', $submitterId);
}

test('handle() approves idea, saves it, and dispatches ProjectIdeaApproved', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);

    $idea = makeIdea('idea_1', 'user_1');

    $repo->shouldReceive('findById')->once()->with('idea_1')->andReturn($idea);
    $repo->shouldReceive('save')->once()->with(Mockery::on(function (ProjectIdea $saved) {
        return $saved->getStatus() === IdeaStatus::Approved;
    }));
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(ProjectIdeaApproved::class), ProjectIdeaApproved::NAME);
    $logger->shouldReceive('info')->once();
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new OnModerationDecisionMade($repo, $dispatcher, $logger, $transaction);
    $handler->handle(new ModerationDecisionMade('idea_1', 'mod_1', true, ''));
});

test('handle() rejects idea, saves it, and dispatches ProjectIdeaRejected', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);

    $idea = makeIdea('idea_2', 'user_2');

    $repo->shouldReceive('findById')->once()->with('idea_2')->andReturn($idea);
    $repo->shouldReceive('save')->once()->with(Mockery::on(function (ProjectIdea $saved) {
        return $saved->getStatus() === IdeaStatus::Rejected;
    }));
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(ProjectIdeaRejected::class), ProjectIdeaRejected::NAME);
    $logger->shouldReceive('info')->once();
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new OnModerationDecisionMade($repo, $dispatcher, $logger, $transaction);
    $handler->handle(new ModerationDecisionMade('idea_2', 'mod_1', false, 'Not relevant'));
});

test('handle() logs warning and returns early when idea not found', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);

    $repo->shouldReceive('findById')->once()->with('missing_idea')->andReturn(null);
    $logger->shouldReceive('warning')
        ->once()
        ->with('Idea not found for moderation decision', Mockery::on(fn($c) => $c['ideaId'] === 'missing_idea'));
    $repo->shouldReceive('save')->never();
    $dispatcher->shouldReceive('dispatch')->never();

    $handler = new OnModerationDecisionMade($repo, $dispatcher, $logger, $transaction);
    $handler->handle(new ModerationDecisionMade('missing_idea', 'mod_1', true, ''));
});

test('handle() applies title and description overrides before approving', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
    $transaction = Mockery::mock(TransactionInterface::class);

    $idea = makeIdea('idea_3', 'user_3');

    $repo->shouldReceive('findById')->once()->andReturn($idea);
    $repo->shouldReceive('save')->once()->with(Mockery::on(function (ProjectIdea $saved) {
        return $saved->getTitle() === 'Overridden Title'
            && $saved->getDescription() === 'Overridden Description';
    }));
    $dispatcher->shouldReceive('dispatch')->once();
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new OnModerationDecisionMade($repo, $dispatcher, $logger, $transaction);
    $handler->handle(new ModerationDecisionMade(
        'idea_3',
        'mod_1',
        true,
        '',
        'Overridden Title',
        'Overridden Description',
        null,
    ));
});

test('handle() dispatches ProjectIdeaRejected with the rejection reason', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
    $transaction = Mockery::mock(TransactionInterface::class);

    $idea = makeIdea('idea_4', 'user_4');

    $repo->shouldReceive('findById')->once()->andReturn($idea);
    $repo->shouldReceive('save')->once();
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(
            Mockery::on(fn(ProjectIdeaRejected $e) => $e->getReason() === 'Too vague'),
            ProjectIdeaRejected::NAME,
        );
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new OnModerationDecisionMade($repo, $dispatcher, $logger, $transaction);
    $handler->handle(new ModerationDecisionMade('idea_4', 'mod_1', false, 'Too vague'));
});
