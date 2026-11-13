<?php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Application\EventHandler\OnProjectIdeaResubmitted;
use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaResubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

afterEach(fn() => Mockery::close());

test('handle() resets existing review to pending and saves it', function () {
    $repository = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $review = ModerationReview::reconstitute(
        id: 'review_1',
        ideaId: 'idea_10',
        decision: ModerationDecision::FeedbackRequested,
        reviewerId: 'mod_1',
        notes: 'Please clarify',
        createdAt: new DateTimeImmutable(),
        reviewedAt: new DateTimeImmutable(),
    );

    $repository->shouldReceive('findByIdeaId')
        ->once()
        ->with('idea_10')
        ->andReturn($review);

    $repository->shouldReceive('save')
        ->once()
        ->with(Mockery::on(function (ModerationReview $saved) {
            return $saved->getDecision() === ModerationDecision::Pending;
        }));

    $logger->shouldReceive('info')->once();

    $handler = new OnProjectIdeaResubmitted($repository, $logger);
    $handler->handle(new ProjectIdeaResubmitted('idea_10'));
});

test('handle() does nothing when no review exists for the idea', function () {
    $repository = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repository->shouldReceive('findByIdeaId')
        ->once()
        ->with('idea_unknown')
        ->andReturn(null);

    $repository->shouldReceive('save')->never();
    $logger->shouldReceive('info')->never();

    $handler = new OnProjectIdeaResubmitted($repository, $logger);
    $handler->handle(new ProjectIdeaResubmitted('idea_unknown'));
});

test('handle() logs info with reviewId and ideaId when review is reset', function () {
    $repository = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $review = ModerationReview::reconstitute(
        id: 'review_99',
        ideaId: 'idea_5',
        decision: ModerationDecision::FeedbackRequested,
        reviewerId: 'mod_2',
        notes: 'Add more detail',
        createdAt: new DateTimeImmutable(),
        reviewedAt: new DateTimeImmutable(),
    );

    $repository->shouldReceive('findByIdeaId')->once()->andReturn($review);
    $repository->shouldReceive('save')->once();

    $logger->shouldReceive('info')
        ->once()
        ->with(
            'Moderation review reset to pending after resubmission',
            Mockery::on(function ($context) {
                return $context['reviewId'] === 'review_99' && $context['ideaId'] === 'idea_5';
            }),
        );

    $handler = new OnProjectIdeaResubmitted($repository, $logger);
    $handler->handle(new ProjectIdeaResubmitted('idea_5'));
});
