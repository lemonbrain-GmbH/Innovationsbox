<?php

declare(strict_types=1);

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoModeration\Adapter\Out\Persistence\ConcreteModerationReviewRepository;
use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaHandler;
use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Domain\Event\ModerationFeedbackRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

beforeEach(function (): void {
    $this->db = app(Connection::class);
    $this->db->beginTransaction();

    $this->reviewRepo = new ConcreteModerationReviewRepository($this->db);

    $this->dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $this->transaction = Mockery::mock(TransactionInterface::class);
    $this->transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $this->logger = Mockery::mock(LoggerInterface::class);
    $this->logger->shouldReceive('info');

    $this->handler = new ReviewProjectIdeaHandler(
        $this->reviewRepo,
        $this->dispatcher,
        $this->transaction,
        $this->logger,
    );
});

afterEach(function (): void {
    $this->db->rollBack();
    Mockery::close();
});

function seedReviewForUseCase(ConcreteModerationReviewRepository $repo, string $suffix): ModerationReview
{
    $review = ModerationReview::createPending(
        id: 'rev_test_' . $suffix . '_' . substr(md5(uniqid('', true)), 0, 6),
        ideaId: 'idea_' . $suffix,
    );
    $repo->save($review);
    return $review;
}

test('approving a review sets decision to Approved and dispatches ModerationDecisionMade', function (): void {
    $review = seedReviewForUseCase($this->reviewRepo, 'approve');

    $capturedEvent = null;
    $this->dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(function ($event, $name) use (&$capturedEvent) {
            $capturedEvent = $event;
            return $event instanceof ModerationDecisionMade
                && $name === ModerationDecisionMade::NAME;
        });

    $this->handler->handle(new ReviewProjectIdeaCommand(
        reviewId: $review->getId(),
        reviewerId: '10',
        approved: true,
        notes: 'Looks great',
    ));

    $found = $this->reviewRepo->findById($review->getId());

    expect($found->getDecision())->toBe(ModerationDecision::Approved)
        ->and($found->getReviewerId())->toBe('10')
        ->and($found->getNotes())->toBe('Looks great')
        ->and($capturedEvent)->toBeInstanceOf(ModerationDecisionMade::class)
        ->and($capturedEvent->isApproved())->toBeTrue();
});

test('rejecting a review sets decision to Rejected', function (): void {
    $review = seedReviewForUseCase($this->reviewRepo, 'reject');

    $this->dispatcher->shouldReceive('dispatch')->once();

    $this->handler->handle(new ReviewProjectIdeaCommand(
        reviewId: $review->getId(),
        reviewerId: '10',
        approved: false,
        notes: 'Not relevant',
    ));

    $found = $this->reviewRepo->findById($review->getId());

    expect($found->getDecision())->toBe(ModerationDecision::Rejected)
        ->and($found->getNotes())->toBe('Not relevant');
});

test('requesting feedback sets decision to FeedbackRequested and dispatches event', function (): void {
    $review = seedReviewForUseCase($this->reviewRepo, 'feedback');

    $capturedEvent = null;
    $this->dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(function ($event, $name) use (&$capturedEvent) {
            $capturedEvent = $event;
            return $event instanceof ModerationFeedbackRequested
                && $name === ModerationFeedbackRequested::NAME;
        });

    $this->handler->handle(new ReviewProjectIdeaCommand(
        reviewId: $review->getId(),
        reviewerId: '10',
        approved: false,
        notes: 'Please add cost estimation',
        feedbackRequested: true,
        feedbackDeadline: '2026-07-01',
    ));

    $found = $this->reviewRepo->findById($review->getId());

    expect($found->getDecision())->toBe(ModerationDecision::FeedbackRequested)
        ->and($capturedEvent)->toBeInstanceOf(ModerationFeedbackRequested::class)
        ->and($capturedEvent->getNotes())->toBe('Please add cost estimation');
});
