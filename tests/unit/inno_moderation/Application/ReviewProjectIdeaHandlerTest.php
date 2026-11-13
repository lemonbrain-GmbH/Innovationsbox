<?php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaHandler;
use Concrete\Package\InnoModeration\Domain\Exception\ModerationReviewNotFoundException;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() dispatches ModerationDecisionMade with approved=true', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $repo = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('review_1')->andReturn($review);
    $repo->shouldReceive('save')->once();
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(
            Mockery::on(fn($e) => $e instanceof ModerationDecisionMade && $e->isApproved()),
            ModerationDecisionMade::NAME
        );
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new ReviewProjectIdeaHandler($repo, $dispatcher, $transaction, $logger);
    $handler->handle(new ReviewProjectIdeaCommand('review_1', 'mod_1', true, 'Looks good'));
});

test('handle() throws ModerationReviewNotFoundException when review not found', function () {
    $repo = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('missing_id')->andReturn(null);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new ReviewProjectIdeaHandler($repo, $dispatcher, $transaction, $logger);
    $handler->handle(new ReviewProjectIdeaCommand('missing_id', 'mod_1', true, ''));
})->throws(ModerationReviewNotFoundException::class);

test('handle() passes overrides through ModerationDecisionMade event on approval', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $repo = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('review_1')->andReturn($review);
    $repo->shouldReceive('save')->once();
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(
            Mockery::on(fn($e) => $e instanceof ModerationDecisionMade
                && $e->isApproved()
                && $e->getTitleOverride() === 'Better Title'
                && $e->getDescriptionOverride() === 'Better Desc'
                && $e->getCategoryOverride() === 'cat_2'),
            ModerationDecisionMade::NAME
        );
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new ReviewProjectIdeaHandler($repo, $dispatcher, $transaction, $logger);
    $handler->handle(new ReviewProjectIdeaCommand(
        reviewId: 'review_1',
        reviewerId: 'mod_1',
        approved: true,
        notes: 'Approved with changes',
        titleOverride: 'Better Title',
        descriptionOverride: 'Better Desc',
        categoryOverride: 'cat_2',
    ));
});
