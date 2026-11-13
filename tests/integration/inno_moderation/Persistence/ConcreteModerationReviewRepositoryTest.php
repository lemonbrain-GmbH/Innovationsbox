<?php

declare(strict_types=1);

/**
 * Integration test: ConcreteModerationReviewRepository
 *
 * Prerequisites
 * ─────────────
 * - CMS installed (`make install`)
 * - `ib_moderation_reviews` table exists (created when inno_moderation package
 *   is installed in the back-end)
 * - `.env` configured with valid DB_* credentials
 *
 * Run:
 *   composer test:integration
 *
 * Isolation strategy
 * ──────────────────
 * Each test begins a DB transaction in beforeEach() and rolls it back in
 * afterEach(). The table is therefore left in its original state after every
 * test — no cleanup SQL is needed.
 */

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoModeration\Adapter\Out\Persistence\ConcreteModerationReviewRepository;
use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;

// ── Transaction-rollback isolation ────────────────────────────────────────────

beforeEach(function (): void {
    /** @var Connection $db */
    $this->db = app(Connection::class);
    $this->db->beginTransaction();

    $this->repo = new ConcreteModerationReviewRepository($this->db);
});

afterEach(function (): void {
    $this->db->rollBack();
});

// ── Helper ────────────────────────────────────────────────────────────────────

/**
 * Returns a unique ID prefix for test isolation within a test run.
 * Using a prefix avoids collisions if the table is not empty.
 */
function testReviewId(string $suffix): string
{
    return 'test_integ_' . $suffix . '_' . substr(md5(uniqid('', true)), 0, 6);
}

// ── Tests ─────────────────────────────────────────────────────────────────────

test('findById() returns null for unknown id', function (): void {
    $result = $this->repo->findById('does_not_exist');

    expect($result)->toBeNull();
});

test('save() inserts a new pending review and findById() retrieves it', function (): void {
    $id = testReviewId('save');
    $review = ModerationReview::createPending($id, 'idea_' . $id);

    $this->repo->save($review);

    $found = $this->repo->findById($id);

    expect($found)->not->toBeNull()
        ->and($found->getId())->toBe($id)
        ->and($found->getIdeaId())->toBe('idea_' . $id)
        ->and($found->getDecision())->toBe(ModerationDecision::Pending)
        ->and($found->getReviewerId())->toBeNull()
        ->and($found->getNotes())->toBe('');
});

test('save() updates an existing review after approve()', function (): void {
    $id = testReviewId('approve');
    $review = ModerationReview::createPending($id, 'idea_' . $id);
    $this->repo->save($review);

    $review->approve('reviewer_1', 'Looks good', 'Better Title', 'Better desc', 'cat_bauen');
    $this->repo->save($review);

    $found = $this->repo->findById($id);

    expect($found)->not->toBeNull()
        ->and($found->getDecision())->toBe(ModerationDecision::Approved)
        ->and($found->getReviewerId())->toBe('reviewer_1')
        ->and($found->getNotes())->toBe('Looks good')
        ->and($found->getTitleOverride())->toBe('Better Title')
        ->and($found->getDescriptionOverride())->toBe('Better desc')
        ->and($found->getCategoryOverride())->toBe('cat_bauen')
        ->and($found->getReviewedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('save() updates an existing review after reject()', function (): void {
    $id = testReviewId('reject');
    $review = ModerationReview::createPending($id, 'idea_' . $id);
    $this->repo->save($review);

    $review->reject('reviewer_2', 'Out of scope');
    $this->repo->save($review);

    $found = $this->repo->findById($id);

    expect($found->getDecision())->toBe(ModerationDecision::Rejected)
        ->and($found->getReviewerId())->toBe('reviewer_2')
        ->and($found->getNotes())->toBe('Out of scope');
});

test('findByIdeaId() returns the review for a given idea', function (): void {
    $id = testReviewId('byidea');
    $ideaId = 'idea_' . $id;
    $review = ModerationReview::createPending($id, $ideaId);
    $this->repo->save($review);

    $found = $this->repo->findByIdeaId($ideaId);

    expect($found)->not->toBeNull()
        ->and($found->getId())->toBe($id);
});

test('findByIdeaId() returns null when no review exists for the idea', function (): void {
    $result = $this->repo->findByIdeaId('idea_nonexistent_xyz');

    expect($result)->toBeNull();
});

test('findByDecision() returns only reviews matching the given decision', function (): void {
    $idPending1 = testReviewId('dec_p1');
    $idPending2 = testReviewId('dec_p2');
    $idApproved = testReviewId('dec_a1');

    $pending1 = ModerationReview::createPending($idPending1, 'idea_' . $idPending1);
    $pending2 = ModerationReview::createPending($idPending2, 'idea_' . $idPending2);
    $approved = ModerationReview::createPending($idApproved, 'idea_' . $idApproved);
    $approved->approve('reviewer_1', 'OK');

    $this->repo->save($pending1);
    $this->repo->save($pending2);
    $this->repo->save($approved);

    $pendingResults = $this->repo->findByDecision(ModerationDecision::Pending);
    $approvedResults = $this->repo->findByDecision(ModerationDecision::Approved);

    $pendingIds = array_map(fn(ModerationReview $r) => $r->getId(), $pendingResults);
    $approvedIds = array_map(fn(ModerationReview $r) => $r->getId(), $approvedResults);

    expect($pendingIds)->toContain($idPending1)
        ->toContain($idPending2)
        ->not->toContain($idApproved);

    expect($approvedIds)->toContain($idApproved)
        ->not->toContain($idPending1)
        ->not->toContain($idPending2);
});

test('save() persists requestFeedback() state including deadline', function (): void {
    $id = testReviewId('feedback');
    $review = ModerationReview::createPending($id, 'idea_' . $id);
    $this->repo->save($review);

    $review->requestFeedback('reviewer_3', 'Please clarify scope', '2026-09-01');
    $this->repo->save($review);

    $found = $this->repo->findById($id);

    expect($found->getDecision())->toBe(ModerationDecision::FeedbackRequested)
        ->and($found->getReviewerId())->toBe('reviewer_3')
        ->and($found->getNotes())->toBe('Please clarify scope')
        ->and($found->getFeedbackDeadline())->toBe('2026-09-01');
});

test('save() persists resetToPending() clearing reviewer and notes', function (): void {
    $id = testReviewId('reset');
    $review = ModerationReview::createPending($id, 'idea_' . $id);
    $review->approve('reviewer_1', 'Looked good initially');
    $this->repo->save($review);

    $review->resetToPending();
    $this->repo->save($review);

    $found = $this->repo->findById($id);

    expect($found->getDecision())->toBe(ModerationDecision::Pending)
        ->and($found->getReviewerId())->toBeNull()
        ->and($found->getNotes())->toBe('')
        ->and($found->getReviewedAt())->toBeNull()
        ->and($found->getFeedbackDeadline())->toBeNull();
});
