<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Application\EventHandler\OnModerationFeedbackRequested;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationFeedbackRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;

afterEach(fn() => Mockery::close());

test('handle() sets idea status to FeedbackRequested and saves', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);

    $idea = ProjectIdea::submit('idea_1', 'Title', 'Desc', 'cat_1', 'user_1');

    $repo->shouldReceive('findById')->once()->with('idea_1')->andReturn($idea);
    $repo->shouldReceive('save')->once()->with(Mockery::on(function (ProjectIdea $saved) {
        return $saved->getStatus() === IdeaStatus::FeedbackRequested;
    }));
    $logger->shouldReceive('info')->once();
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new OnModerationFeedbackRequested($repo, $logger, $transaction);
    $handler->handle(new ModerationFeedbackRequested('idea_1', 'mod_1', 'Please add details', null));
});

test('handle() stores feedback notes and deadline on the idea', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
    $transaction = Mockery::mock(TransactionInterface::class);

    $idea = ProjectIdea::submit('idea_2', 'Title', 'Desc', 'cat_1', 'user_1');

    $repo->shouldReceive('findById')->once()->andReturn($idea);
    $repo->shouldReceive('save')->once()->with(Mockery::on(function (ProjectIdea $saved) {
        return $saved->getFeedbackNotes() === 'Be more specific'
            && $saved->getFeedbackDeadline() === '2026-07-01';
    }));
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new OnModerationFeedbackRequested($repo, $logger, $transaction);
    $handler->handle(new ModerationFeedbackRequested('idea_2', 'mod_1', 'Be more specific', '2026-07-01'));
});

test('handle() logs warning and returns early when idea not found', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);

    $repo->shouldReceive('findById')->once()->with('missing')->andReturn(null);
    $logger->shouldReceive('warning')
        ->once()
        ->with('Idea not found for feedback request', Mockery::on(fn($c) => $c['ideaId'] === 'missing'));
    $repo->shouldReceive('save')->never();

    $handler = new OnModerationFeedbackRequested($repo, $logger, $transaction);
    $handler->handle(new ModerationFeedbackRequested('missing', 'mod_1', 'notes', null));
});

test('handle() logs info after saving the idea', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);

    $idea = ProjectIdea::submit('idea_3', 'Title', 'Desc', 'cat_1', 'user_1');

    $repo->shouldReceive('findById')->once()->andReturn($idea);
    $repo->shouldReceive('save')->once();
    $logger->shouldReceive('info')
        ->once()
        ->with('Project idea feedback requested', Mockery::on(fn($c) => $c['ideaId'] === 'idea_3'));
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new OnModerationFeedbackRequested($repo, $logger, $transaction);
    $handler->handle(new ModerationFeedbackRequested('idea_3', 'mod_1', 'notes', null));
});
