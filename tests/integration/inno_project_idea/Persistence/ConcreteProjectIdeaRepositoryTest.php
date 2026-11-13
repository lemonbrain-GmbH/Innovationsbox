<?php

declare(strict_types=1);

/**
 * Integration test: ConcreteProjectIdeaRepository
 *
 * Prerequisites
 * ─────────────
 * - CMS installed (`make install`)
 * - `ib_project_ideas` and `ib_idea_attachments` tables exist (created when
 *   inno_project_idea package is installed in the back-end)
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
use Concrete\Package\InnoProjectIdea\Adapter\Out\Persistence\ConcreteProjectIdeaRepository;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;

// ── Transaction-rollback isolation ────────────────────────────────────────────

beforeEach(function (): void {
    /** @var Connection $db */
    $this->db = app(Connection::class);
    $this->db->beginTransaction();

    $this->repo = new ConcreteProjectIdeaRepository($this->db);
});

afterEach(function (): void {
    $this->db->rollBack();
});

// ── Helper ────────────────────────────────────────────────────────────────────

function testIdeaId(string $suffix): string
{
    return 'test_integ_idea_' . $suffix . '_' . substr(md5(uniqid('', true)), 0, 6);
}

// ── Tests ─────────────────────────────────────────────────────────────────────

test('findById() returns null for unknown id', function (): void {
    $result = $this->repo->findById('does_not_exist');

    expect($result)->toBeNull();
});

test('save() inserts a new submitted idea and findById() retrieves it', function (): void {
    $id = testIdeaId('save');
    $idea = ProjectIdea::submit(
        id: $id,
        title: 'Integration Test Idea',
        description: '<p>Test description</p>',
        category: 'cat_bauen',
        submitterId: '1',
    );

    $this->repo->save($idea);

    $found = $this->repo->findById($id);

    expect($found)->not->toBeNull()
        ->and($found->getId())->toBe($id)
        ->and($found->getTitle())->toBe('Integration Test Idea')
        ->and($found->getDescription())->toBe('<p>Test description</p>')
        ->and($found->getCategory())->toBe('cat_bauen')
        ->and($found->getSubmitterId())->toBe('1')
        ->and($found->getStatus())->toBe(IdeaStatus::Submitted)
        ->and($found->getRejectionReason())->toBeNull()
        ->and($found->getProjectSpaceId())->toBeNull()
        ->and($found->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($found->getUpdatedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('save() updates an existing idea after approve()', function (): void {
    $id = testIdeaId('approve');
    $idea = ProjectIdea::submit(
        id: $id,
        title: 'To Approve',
        description: '<p>Will be approved</p>',
        category: 'cat_bauen',
        submitterId: '1',
    );
    $this->repo->save($idea);

    $idea->approve();
    $this->repo->save($idea);

    $found = $this->repo->findById($id);

    expect($found)->not->toBeNull()
        ->and($found->getStatus())->toBe(IdeaStatus::Approved);
});

test('save() updates an existing idea after reject()', function (): void {
    $id = testIdeaId('reject');
    $idea = ProjectIdea::submit(
        id: $id,
        title: 'To Reject',
        description: '<p>Will be rejected</p>',
        category: 'cat_bauen',
        submitterId: '2',
    );
    $this->repo->save($idea);

    $idea->reject('Out of scope');
    $this->repo->save($idea);

    $found = $this->repo->findById($id);

    expect($found)->not->toBeNull()
        ->and($found->getStatus())->toBe(IdeaStatus::Rejected)
        ->and($found->getRejectionReason())->toBe('Out of scope');
});

test('save() persists markUnderReview() state', function (): void {
    $id = testIdeaId('review');
    $idea = ProjectIdea::submit(
        id: $id,
        title: 'Under Review',
        description: '<p>Being reviewed</p>',
        category: 'cat_bauen',
        submitterId: '1',
    );
    $this->repo->save($idea);

    $idea->markUnderReview();
    $this->repo->save($idea);

    $found = $this->repo->findById($id);

    expect($found->getStatus())->toBe(IdeaStatus::UnderReview);
});

test('save() persists requestFeedback() state including notes and deadline', function (): void {
    $id = testIdeaId('feedback');
    $idea = ProjectIdea::submit(
        id: $id,
        title: 'Needs Feedback',
        description: '<p>Clarification needed</p>',
        category: 'cat_bauen',
        submitterId: '1',
    );
    $this->repo->save($idea);

    $idea->requestFeedback('Please clarify scope', '2026-09-01');
    $this->repo->save($idea);

    $found = $this->repo->findById($id);

    expect($found->getStatus())->toBe(IdeaStatus::FeedbackRequested)
        ->and($found->getFeedbackNotes())->toBe('Please clarify scope')
        ->and($found->getFeedbackDeadline())->toBe('2026-09-01');
});

test('save() persists resubmit() clearing feedback state', function (): void {
    $id = testIdeaId('resubmit');
    $idea = ProjectIdea::submit(
        id: $id,
        title: 'Resubmitted',
        description: '<p>Updated description</p>',
        category: 'cat_bauen',
        submitterId: '1',
    );
    $this->repo->save($idea);
    $idea->requestFeedback('Please clarify', '2026-09-01');
    $this->repo->save($idea);

    $idea->resubmit();
    $this->repo->save($idea);

    $found = $this->repo->findById($id);

    expect($found->getStatus())->toBe(IdeaStatus::Submitted)
        ->and($found->getFeedbackNotes())->toBeNull()
        ->and($found->getFeedbackDeadline())->toBeNull();
});

test('findByStatus() returns only ideas matching the given status', function (): void {
    $idSubmitted1 = testIdeaId('stat_s1');
    $idSubmitted2 = testIdeaId('stat_s2');
    $idApproved   = testIdeaId('stat_a1');

    $submitted1 = ProjectIdea::submit(id: $idSubmitted1, title: 'S1', description: '<p>S1</p>', category: 'cat_bauen', submitterId: '1');
    $submitted2 = ProjectIdea::submit(id: $idSubmitted2, title: 'S2', description: '<p>S2</p>', category: 'cat_bauen', submitterId: '1');
    $approved   = ProjectIdea::submit(id: $idApproved, title: 'A1', description: '<p>A1</p>', category: 'cat_bauen', submitterId: '1');
    $approved->approve();

    $this->repo->save($submitted1);
    $this->repo->save($submitted2);
    $this->repo->save($approved);

    $submittedResults = $this->repo->findByStatus(IdeaStatus::Submitted);
    $approvedResults  = $this->repo->findByStatus(IdeaStatus::Approved);

    $submittedIds = array_map(fn(ProjectIdea $i) => $i->getId(), $submittedResults);
    $approvedIds  = array_map(fn(ProjectIdea $i) => $i->getId(), $approvedResults);

    expect($submittedIds)->toContain($idSubmitted1)
        ->toContain($idSubmitted2)
        ->not->toContain($idApproved);

    expect($approvedIds)->toContain($idApproved)
        ->not->toContain($idSubmitted1)
        ->not->toContain($idSubmitted2);
});

test('findBySubmitterId() returns submitted/in-progress ideas for that submitter', function (): void {
    $submitterId = '999';
    $id1 = testIdeaId('sub_a');
    $id2 = testIdeaId('sub_b');

    $idea1 = ProjectIdea::submit(id: $id1, title: 'Submitter Idea 1', description: '<p>1</p>', category: 'cat_bauen', submitterId: $submitterId);
    $idea2 = ProjectIdea::submit(id: $id2, title: 'Submitter Idea 2', description: '<p>2</p>', category: 'cat_bauen', submitterId: $submitterId);

    $this->repo->save($idea1);
    $this->repo->save($idea2);

    $found = $this->repo->findBySubmitterId($submitterId);
    $ids   = array_map(fn(ProjectIdea $i) => $i->getId(), $found);

    expect($ids)->toContain($id1)
        ->toContain($id2);
});

test('delete() removes the idea so findById() returns null', function (): void {
    $id   = testIdeaId('delete');
    $idea = ProjectIdea::submit(
        id: $id,
        title: 'To Delete',
        description: '<p>Will be deleted</p>',
        category: 'cat_bauen',
        submitterId: '1',
    );
    $this->repo->save($idea);

    expect($this->repo->findById($id))->not->toBeNull();

    $this->repo->delete($id);

    expect($this->repo->findById($id))->toBeNull();
});

test('findAll() includes newly saved ideas', function (): void {
    $id   = testIdeaId('all');
    $idea = ProjectIdea::submit(
        id: $id,
        title: 'FindAll Test Idea',
        description: '<p>Test</p>',
        category: 'cat_bauen',
        submitterId: '1',
    );
    $this->repo->save($idea);

    $all = $this->repo->findAll();
    $ids = array_map(fn(ProjectIdea $i) => $i->getId(), $all);

    expect($ids)->toContain($id);
});
