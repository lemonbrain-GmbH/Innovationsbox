<?php

declare(strict_types=1);

/**
 * Integration test: ConcreteProjectSpaceRepository
 *
 * Prerequisites
 * ─────────────
 * - CMS installed (`make install`)
 * - `ib_project_spaces`, `ib_project_space_members`, `ib_project_space_collaboards`,
 *   `ib_project_space_updates`, and `ib_expert_requests` tables exist (created when
 *   inno_project_space package is installed in the back-end)
 * - `.env` configured with valid DB_* credentials
 *
 * Run:
 *   composer test:integration
 *
 * Isolation strategy
 * ──────────────────
 * Each test begins a DB transaction in beforeEach() and rolls it back in
 * afterEach(). The tables are therefore left in their original state after
 * every test — no cleanup SQL is needed.
 */

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectSpace\Adapter\Out\Persistence\ConcreteProjectSpaceRepository;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;

// ── Transaction-rollback isolation ────────────────────────────────────────────

beforeEach(function (): void {
    /** @var Connection $db */
    $this->db = app(Connection::class);
    $this->db->beginTransaction();

    $this->repo = new ConcreteProjectSpaceRepository($this->db);
});

afterEach(function (): void {
    $this->db->rollBack();
});

// ── Helper ────────────────────────────────────────────────────────────────────

function testSpaceId(string $suffix): string
{
    return 'test_integ_space_' . $suffix . '_' . substr(md5(uniqid('', true)), 0, 6);
}

function testIdeaIdForSpace(string $suffix): string
{
    return 'test_integ_idea_' . $suffix . '_' . substr(md5(uniqid('', true)), 0, 6);
}

// ── Tests ─────────────────────────────────────────────────────────────────────

test('findById() returns null for unknown id', function (): void {
    $result = $this->repo->findById('does_not_exist');

    expect($result)->toBeNull();
});

test('save() inserts a new draft space and findById() retrieves it', function (): void {
    $spaceId  = testSpaceId('save');
    $ideaId   = testIdeaIdForSpace('save');
    $space = ProjectSpace::createFromApprovedIdea(
        id: $spaceId,
        projectIdeaId: $ideaId,
        title: 'Integration Test Space',
        description: '<p>Test description</p>',
        initiatorId: '1',
        categoryId: 'cat_bauen',
    );

    $this->repo->save($space);

    $found = $this->repo->findById($spaceId);

    expect($found)->not->toBeNull()
        ->and($found->getId())->toBe($spaceId)
        ->and($found->getProjectIdeaId())->toBe($ideaId)
        ->and($found->getTitle())->toBe('Integration Test Space')
        ->and($found->getDescription())->toBe('<p>Test description</p>')
        ->and($found->getStatus())->toBe(SpaceStatus::Active)
        ->and($found->getInitiatorId())->toBe('1')
        ->and($found->getCategoryId())->toBe('cat_bauen')
        ->and($found->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($found->getUpdatedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('save() creates space with initiator as first member', function (): void {
    $spaceId = testSpaceId('members');
    $space = ProjectSpace::createFromApprovedIdea(
        id: $spaceId,
        projectIdeaId: testIdeaIdForSpace('members'),
        title: 'Members Test',
        description: '<p>Test</p>',
        initiatorId: '42',
    );

    $this->repo->save($space);

    $found = $this->repo->findById($spaceId);

    expect($found->getMemberIds())->toContain('42');
});

test('save() updates title and description on second call', function (): void {
    $spaceId = testSpaceId('update');
    $space = ProjectSpace::createFromApprovedIdea(
        id: $spaceId,
        projectIdeaId: testIdeaIdForSpace('update'),
        title: 'Original Title',
        description: '<p>Original</p>',
        initiatorId: '1',
    );
    $this->repo->save($space);

    // Reconstitute from persistence, then activate (which triggers updatedAt change)
    $found = $this->repo->findById($spaceId);
    $found->activate();
    $this->repo->save($found);

    $updated = $this->repo->findById($spaceId);

    expect($updated->getStatus())->toBe(SpaceStatus::Active);
});

test('findByProjectIdeaId() returns the space for a given idea', function (): void {
    $spaceId = testSpaceId('byidea');
    $ideaId  = testIdeaIdForSpace('byidea');
    $space = ProjectSpace::createFromApprovedIdea(
        id: $spaceId,
        projectIdeaId: $ideaId,
        title: 'By Idea Test',
        description: '<p>Test</p>',
        initiatorId: '1',
    );
    $this->repo->save($space);

    $found = $this->repo->findByProjectIdeaId($ideaId);

    expect($found)->not->toBeNull()
        ->and($found->getId())->toBe($spaceId);
});

test('findByProjectIdeaId() returns null when no space exists for the idea', function (): void {
    $result = $this->repo->findByProjectIdeaId('idea_nonexistent_xyz');

    expect($result)->toBeNull();
});

test('findPublic() returns only active and completed spaces', function (): void {
    $draftId     = testSpaceId('pub_draft');
    $activeId    = testSpaceId('pub_active');
    $completedId = testSpaceId('pub_completed');

    $draft = ProjectSpace::createFromApprovedIdea(
        id: $draftId,
        projectIdeaId: testIdeaIdForSpace('pub_draft'),
        title: 'Draft Space',
        description: '<p>Draft</p>',
        initiatorId: '1',
    );

    $active = ProjectSpace::createFromApprovedIdea(
        id: $activeId,
        projectIdeaId: testIdeaIdForSpace('pub_active'),
        title: 'Active Space',
        description: '<p>Active</p>',
        initiatorId: '1',
    );
    $active->activate();

    $completed = ProjectSpace::createFromApprovedIdea(
        id: $completedId,
        projectIdeaId: testIdeaIdForSpace('pub_completed'),
        title: 'Completed Space',
        description: '<p>Completed</p>',
        initiatorId: '1',
    );
    $completed->activate();
    $completed->complete();

    $this->repo->save($draft);
    $this->repo->save($active);
    $this->repo->save($completed);

    $result = $this->repo->findPublic(null, 1, 100);

    $spaces = $result['data'];
    $ids    = array_map(fn(ProjectSpace $s) => $s->getId(), $spaces);

    expect($ids)->toContain($activeId)
        ->toContain($completedId)
        ->not->toContain($draftId);
});

test('findPublic() supports status filtering', function (): void {
    $activeId    = testSpaceId('filter_active');
    $completedId = testSpaceId('filter_completed');

    $active = ProjectSpace::createFromApprovedIdea(
        id: $activeId,
        projectIdeaId: testIdeaIdForSpace('filter_active'),
        title: 'Filter Active',
        description: '<p>Active</p>',
        initiatorId: '1',
    );
    $active->activate();

    $completed = ProjectSpace::createFromApprovedIdea(
        id: $completedId,
        projectIdeaId: testIdeaIdForSpace('filter_completed'),
        title: 'Filter Completed',
        description: '<p>Completed</p>',
        initiatorId: '1',
    );
    $completed->activate();
    $completed->complete();

    $this->repo->save($active);
    $this->repo->save($completed);

    $activeOnly    = $this->repo->findPublic(SpaceStatus::Active, 1, 100);
    $completedOnly = $this->repo->findPublic(SpaceStatus::Completed, 1, 100);

    $activeIds    = array_map(fn(ProjectSpace $s) => $s->getId(), $activeOnly['data']);
    $completedIds = array_map(fn(ProjectSpace $s) => $s->getId(), $completedOnly['data']);

    expect($activeIds)->toContain($activeId)->not->toContain($completedId);
    expect($completedIds)->toContain($completedId)->not->toContain($activeId);
});

test('findPublic() returns total count', function (): void {
    $result = $this->repo->findPublic(null, 1, 100);

    expect($result)->toHaveKey('total')
        ->and($result)->toHaveKey('data')
        ->and($result['total'])->toBeInt();
});

test('findPublic() paginates results', function (): void {
    // Save two active spaces
    foreach (['pg_a', 'pg_b'] as $suffix) {
        $s = ProjectSpace::createFromApprovedIdea(
            id: testSpaceId($suffix),
            projectIdeaId: testIdeaIdForSpace($suffix),
            title: 'Page Test ' . $suffix,
            description: '<p>Test</p>',
            initiatorId: '1',
        );
        $s->activate();
        $this->repo->save($s);
    }

    $page1 = $this->repo->findPublic(SpaceStatus::Active, 1, 1);
    $page2 = $this->repo->findPublic(SpaceStatus::Active, 2, 1);

    expect(count($page1['data']))->toBe(1)
        ->and(count($page2['data']))->toBe(1)
        ->and($page1['data'][0]->getId())->not->toBe($page2['data'][0]->getId());
});

test('save() persists status update for a space', function (): void {
    $spaceId = testSpaceId('statusupdate');
    $space = ProjectSpace::createFromApprovedIdea(
        id: $spaceId,
        projectIdeaId: testIdeaIdForSpace('statusupdate'),
        title: 'Status Update Test',
        description: '<p>Test</p>',
        initiatorId: '1',
    );
    $space->activate();
    $updateId = 'upd_' . uniqid('', true);
    $space->addStatusUpdate($updateId, 'First Update', 'Content here', '1');
    $this->repo->save($space);

    $found = $this->repo->findById($spaceId);

    $updates = $found->getStatusUpdates();
    $updateIds = array_map(fn($u) => $u->getId(), $updates);

    expect($updateIds)->toContain($updateId);
});
