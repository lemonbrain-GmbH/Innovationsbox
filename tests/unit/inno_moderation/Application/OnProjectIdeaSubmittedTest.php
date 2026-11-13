<?php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Application\EventHandler\OnProjectIdeaSubmitted;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

afterEach(fn() => Mockery::close());

test('handle() saves a new pending ModerationReview for the submitted idea', function () {
    $repository = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repository->shouldReceive('save')
        ->once()
        ->with(Mockery::on(function (ModerationReview $review) {
            return $review->getIdeaId() === 'idea_42';
        }));

    $logger->shouldReceive('info')->once();

    $handler = new OnProjectIdeaSubmitted($repository, $logger);
    $handler->handle(new ProjectIdeaSubmitted('idea_42', 'user_1', 'My Idea'));
});

test('handle() logs info with reviewId and ideaId after saving', function () {
    $repository = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repository->shouldReceive('save')->once();

    $logger->shouldReceive('info')
        ->once()
        ->with('Pending moderation review created', Mockery::on(function ($context) {
            return isset($context['reviewId']) && $context['ideaId'] === 'idea_1';
        }));

    $handler = new OnProjectIdeaSubmitted($repository, $logger);
    $handler->handle(new ProjectIdeaSubmitted('idea_1', 'user_1', 'Green Roof'));
});

test('handle() creates review with a non-empty review id', function () {
    $repository = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $repository->shouldReceive('save')
        ->once()
        ->with(Mockery::on(function (ModerationReview $review) {
            return $review->getId() !== '';
        }));

    $handler = new OnProjectIdeaSubmitted($repository, $logger);
    $handler->handle(new ProjectIdeaSubmitted('idea_2', 'user_2', 'Wind Farm'));
});
