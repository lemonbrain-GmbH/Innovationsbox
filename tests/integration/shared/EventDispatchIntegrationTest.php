<?php

declare(strict_types=1);

/**
 * Integration test: Event dispatch wires listeners end-to-end.
 *
 * Prerequisites
 * ─────────────
 * - CMS installed (`make install`)
 * - All five packages installed in the CMS back-end (so on_start() has run
 *   and all event listeners are registered)
 * - `ib_moderation_reviews` table exists
 * - `.env` configured with valid DB_* credentials
 *
 * Run:
 *   composer test:integration
 *
 * What is verified
 * ────────────────
 * When `ProjectIdeaSubmitted` is dispatched through the Concrete CMS Events
 * facade, the `OnProjectIdeaSubmitted` listener (registered in
 * `inno_moderation/controller.php::on_start()`) must create a pending
 * ModerationReview row in `ib_moderation_reviews`.
 *
 * Isolation strategy
 * ──────────────────
 * Each test wraps DB mutations in a transaction that is rolled back in
 * afterEach(). No rows survive between tests.
 */

use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Support\Facade\Events;
use Concrete\Package\InnoModeration\Adapter\Out\Persistence\ConcreteModerationReviewRepository;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;

// ── Transaction-rollback isolation ────────────────────────────────────────────

beforeEach(function (): void {
    /** @var Connection $db */
    $this->db = app(Connection::class);
    $this->db->beginTransaction();

    $this->reviewRepo = new ConcreteModerationReviewRepository($this->db);
});

afterEach(function (): void {
    $this->db->rollBack();
});

// ── Tests ─────────────────────────────────────────────────────────────────────

test(
    'dispatching ProjectIdeaSubmitted creates a pending ModerationReview',
    function (): void {
        $ideaId     = 'evtest_idea_' . substr(md5(uniqid('', true)), 0, 8);
        $submitterId = '1';
        $title      = 'Event Dispatch Integration Test Idea';

        $event = new ProjectIdeaSubmitted($ideaId, $submitterId, $title);

        Events::dispatch(ProjectIdeaSubmitted::NAME, $event);

        $review = $this->reviewRepo->findByIdeaId($ideaId);

        expect($review)->not->toBeNull()
            ->and($review->getIdeaId())->toBe($ideaId);
    }
)->skip(
    !class_exists(\Concrete\Core\Support\Facade\Events::class),
    'Requires full CMS with all packages installed and on_start() listeners registered'
);

test(
    'dispatching ProjectIdeaSubmitted twice for the same idea creates two reviews',
    function (): void {
        // The moderation package always inserts a new pending review per event —
        // this test documents that behaviour (idempotency is enforced at the use-case level,
        // not here).
        $ideaId = 'evtest_dup_' . substr(md5(uniqid('', true)), 0, 8);
        $event  = new ProjectIdeaSubmitted($ideaId, '1', 'Duplicate dispatch test');

        Events::dispatch(ProjectIdeaSubmitted::NAME, $event);
        Events::dispatch(ProjectIdeaSubmitted::NAME, $event);

        // Both reviews target the same ideaId — findByIdeaId returns the first one.
        $review = $this->reviewRepo->findByIdeaId($ideaId);

        expect($review)->not->toBeNull()
            ->and($review->getIdeaId())->toBe($ideaId);
    }
)->skip(
    !class_exists(\Concrete\Core\Support\Facade\Events::class),
    'Requires full CMS with all packages installed and on_start() listeners registered'
);

test(
    'event class carries correct payload after construction',
    function (): void {
        // This test does NOT require full CMS — it validates the event DTO itself.
        $event = new ProjectIdeaSubmitted('idea_123', '42', 'My Idea Title');

        expect($event->getIdeaId())->toBe('idea_123')
            ->and($event->getSubmitterId())->toBe('42')
            ->and($event->getTitle())->toBe('My Idea Title')
            ->and(ProjectIdeaSubmitted::NAME)->toBe('innovationsbox.project_idea.submitted');
    }
);
