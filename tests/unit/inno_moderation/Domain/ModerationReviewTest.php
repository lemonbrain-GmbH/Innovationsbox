<?php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;

test('createPending() creates review with Pending decision and no reviewer', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    expect($review->getId())->toBe('review_1')
        ->and($review->getIdeaId())->toBe('idea_1')
        ->and($review->getDecision())->toBe(ModerationDecision::Pending)
        ->and($review->getReviewerId())->toBeNull()
        ->and($review->getNotes())->toBe('');
});

test('approve() sets decision to Approved, records reviewerId and notes', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $review->approve('reviewer_1', 'Looks great');
    expect($review->getDecision())->toBe(ModerationDecision::Approved)
        ->and($review->getReviewerId())->toBe('reviewer_1')
        ->and($review->getNotes())->toBe('Looks great')
        ->and($review->getReviewedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('reject() sets decision to Rejected with a reason', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $review->reject('reviewer_1', 'Not relevant');
    expect($review->getDecision())->toBe(ModerationDecision::Rejected)
        ->and($review->getNotes())->toBe('Not relevant');
});

test('approve() with overrides stores them', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $review->approve('reviewer_1', 'Looks great', 'Better Title', 'Better description', 'cat_2');
    expect($review->getDecision())->toBe(ModerationDecision::Approved)
        ->and($review->getTitleOverride())->toBe('Better Title')
        ->and($review->getDescriptionOverride())->toBe('Better description')
        ->and($review->getCategoryOverride())->toBe('cat_2');
});

test('approve() without overrides leaves them null', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $review->approve('reviewer_1', 'OK');
    expect($review->getTitleOverride())->toBeNull()
        ->and($review->getDescriptionOverride())->toBeNull()
        ->and($review->getCategoryOverride())->toBeNull();
});

test('requestFeedback() sets decision to FeedbackRequested with notes and deadline', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $review->requestFeedback('reviewer_1', 'Please add more details', '2026-07-15');
    expect($review->getDecision())->toBe(ModerationDecision::FeedbackRequested)
        ->and($review->getReviewerId())->toBe('reviewer_1')
        ->and($review->getNotes())->toBe('Please add more details')
        ->and($review->getFeedbackDeadline())->toBe('2026-07-15')
        ->and($review->getReviewedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('requestFeedback() without deadline leaves it null', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $review->requestFeedback('reviewer_1', 'Fix it', null);
    expect($review->getDecision())->toBe(ModerationDecision::FeedbackRequested)
        ->and($review->getFeedbackDeadline())->toBeNull();
});

test('reconstitute() restores all fields', function () {
    $review = ModerationReview::reconstitute(
        id: 'review_1',
        ideaId: 'idea_1',
        decision: ModerationDecision::Approved,
        reviewerId: 'mod_1',
        notes: 'Good',
        createdAt: new DateTimeImmutable('2026-01-01'),
        reviewedAt: new DateTimeImmutable('2026-01-02'),
        titleOverride: 'New Title',
        descriptionOverride: 'New Desc',
        categoryOverride: 'cat_2',
        feedbackDeadline: '2026-03-01',
    );
    expect($review->getId())->toBe('review_1')
        ->and($review->getTitleOverride())->toBe('New Title')
        ->and($review->getDescriptionOverride())->toBe('New Desc')
        ->and($review->getCategoryOverride())->toBe('cat_2')
        ->and($review->getFeedbackDeadline())->toBe('2026-03-01');
});
