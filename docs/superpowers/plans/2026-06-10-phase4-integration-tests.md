# Phase 4: Integration Tests — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add use case integration tests, event chain tests, and space status transition tests that exercise real repositories and the Symfony event dispatcher — catching bugs that unit test mocks hide.

**Architecture:** Integration tests use real MySQL via transaction-rollback isolation (no cleanup SQL). Use case handlers get real repositories but mock cross-cutting concerns (auth, logger) and event dispatcher (to capture dispatched events without triggering cascading listeners). Event chain tests use the CMS `Events` facade to verify end-to-end wiring.

**Tech Stack:** Pest, Mockery, Concrete CMS Events facade, MySQL (transaction-rollback)

**Spec:** `docs/superpowers/specs/2026-06-09-comprehensive-qa-infrastructure-design.md` (Phase 4)

---

## What Already Exists

These tests are already committed and should NOT be recreated:

| File | Tests | Status |
|------|-------|--------|
| `tests/integration/inno_project_idea/Persistence/ConcreteProjectIdeaRepositoryTest.php` | 11 | Done |
| `tests/integration/inno_moderation/Persistence/ConcreteModerationReviewRepositoryTest.php` | 12 | Done |
| `tests/integration/inno_project_space/Persistence/ConcreteProjectSpaceRepositoryTest.php` | 12 | Done |
| `tests/integration/shared/EventDispatchIntegrationTest.php` | 3 (2 skipped) | Partial |

## File Structure

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `tests/integration/inno_project_idea/UseCase/SubmitProjectIdeaIntegrationTest.php` | Submit idea → persisted + event dispatched |
| Create | `tests/integration/inno_project_idea/UseCase/EditProjectIdeaIntegrationTest.php` | Edit idea → updated in DB; resubmit on feedback |
| Create | `tests/integration/inno_project_idea/UseCase/DeleteProjectIdeaIntegrationTest.php` | Delete idea → removed from DB |
| Create | `tests/integration/inno_moderation/UseCase/ReviewProjectIdeaIntegrationTest.php` | Approve/reject/feedback → review updated + event dispatched |
| Create | `tests/integration/inno_project_space/UseCase/SpaceStatusTransitionIntegrationTest.php` | Draft→Active→Completed→Archived with real repo |
| Create | `tests/integration/inno_project_space/UseCase/JoinRequestFlowIntegrationTest.php` | Request→Accept/Reject with real repo |
| Create | `tests/integration/shared/EventChainIntegrationTest.php` | Full chains: submit→review created, approve→space created |
| Modify | `tests/integration/shared/EventDispatchIntegrationTest.php` | Unskip 2 tests |

---

### Task 1: Create SubmitProjectIdeaIntegrationTest

**Files:**
- Create: `tests/integration/inno_project_idea/UseCase/SubmitProjectIdeaIntegrationTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

declare(strict_types=1);

/**
 * Integration test: SubmitProjectIdeaHandler with real repository.
 *
 * Verifies that submitting an idea persists it to MySQL and dispatches
 * the ProjectIdeaSubmitted event with correct payload.
 *
 * Run: composer test:integration
 */

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectIdea\Adapter\Out\Persistence\ConcreteProjectIdeaRepository;
use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

beforeEach(function (): void {
    $this->db = app(Connection::class);
    $this->db->beginTransaction();

    $this->repo = new ConcreteProjectIdeaRepository($this->db);

    $this->dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $this->transaction = Mockery::mock(TransactionInterface::class);
    $this->transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $this->logger = Mockery::mock(LoggerInterface::class);
    $this->logger->shouldReceive('info');

    $this->handler = new SubmitProjectIdeaHandler(
        $this->repo,
        $this->dispatcher,
        $this->transaction,
        $this->logger,
    );
});

afterEach(function (): void {
    $this->db->rollBack();
    Mockery::close();
});

test('submitting an idea persists it with Submitted status', function (): void {
    $this->dispatcher->shouldReceive('dispatch')->once();

    $idea = $this->handler->handle(new SubmitProjectIdeaCommand(
        title: 'Integration Test Idea',
        description: '<p>Test description</p>',
        category: 'cat_energie',
        submitterId: '99',
    ));

    $found = $this->repo->findById($idea->getId());

    expect($found)->not->toBeNull()
        ->and($found->getTitle())->toBe('Integration Test Idea')
        ->and($found->getDescription())->toBe('<p>Test description</p>')
        ->and($found->getCategory())->toBe('cat_energie')
        ->and($found->getSubmitterId())->toBe('99')
        ->and($found->getStatus())->toBe(IdeaStatus::Submitted);
});

test('submitting an idea dispatches ProjectIdeaSubmitted event', function (): void {
    $capturedEvent = null;
    $this->dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(function ($event, $name) use (&$capturedEvent) {
            $capturedEvent = $event;
            return $event instanceof ProjectIdeaSubmitted
                && $name === ProjectIdeaSubmitted::NAME;
        });

    $idea = $this->handler->handle(new SubmitProjectIdeaCommand(
        title: 'Event Test',
        description: '<p>Test</p>',
        category: 'cat_energie',
        submitterId: '42',
    ));

    expect($capturedEvent)->toBeInstanceOf(ProjectIdeaSubmitted::class)
        ->and($capturedEvent->getIdeaId())->toBe($idea->getId())
        ->and($capturedEvent->getSubmitterId())->toBe('42')
        ->and($capturedEvent->getTitle())->toBe('Event Test');
});
```

- [ ] **Step 2: Run the test**

Run: `composer test:integration`
Expected: 2 new tests pass (if CMS is installed), or graceful skip (if not)

- [ ] **Step 3: Commit**

```bash
git add tests/integration/inno_project_idea/UseCase/SubmitProjectIdeaIntegrationTest.php
git commit -m "test: add SubmitProjectIdea integration test (real repo + event capture)"
```

---

### Task 2: Create EditProjectIdeaIntegrationTest

**Files:**
- Create: `tests/integration/inno_project_idea/UseCase/EditProjectIdeaIntegrationTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

declare(strict_types=1);

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectIdea\Adapter\Out\Persistence\ConcreteProjectIdeaRepository;
use Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea\EditProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea\EditProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaResubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

beforeEach(function (): void {
    $this->db = app(Connection::class);
    $this->db->beginTransaction();

    $this->repo = new ConcreteProjectIdeaRepository($this->db);

    $this->auth = Mockery::mock(AuthInterface::class);
    $this->auth->shouldReceive('getUserId')->andReturn(99);
    $this->auth->shouldReceive('isAuthenticated')->andReturn(true);
    $this->auth->shouldReceive('hasPermission')->andReturn(false);

    $this->dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $this->transaction = Mockery::mock(TransactionInterface::class);
    $this->transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $this->logger = Mockery::mock(LoggerInterface::class);
    $this->logger->shouldReceive('info');

    $this->handler = new EditProjectIdeaHandler(
        $this->repo,
        $this->auth,
        $this->transaction,
        $this->logger,
        $this->dispatcher,
    );
});

afterEach(function (): void {
    $this->db->rollBack();
    Mockery::close();
});

function seedIdea(ConcreteProjectIdeaRepository $repo, string $suffix, string $submitterId = '99'): ProjectIdea
{
    $idea = ProjectIdea::submit(
        id: 'edit_test_' . $suffix . '_' . substr(md5(uniqid('', true)), 0, 6),
        title: 'Original Title',
        description: '<p>Original</p>',
        category: 'cat_energie',
        submitterId: $submitterId,
    );
    $repo->save($idea);
    return $idea;
}

test('editing an idea updates title and description in the database', function (): void {
    $this->dispatcher->shouldReceive('dispatch')->never();

    $idea = seedIdea($this->repo, 'edit');

    $this->handler->handle(new EditProjectIdeaCommand(
        ideaId: $idea->getId(),
        title: 'Updated Title',
        description: '<p>Updated</p>',
        category: 'cat_mobilitaet',
    ));

    $found = $this->repo->findById($idea->getId());

    expect($found->getTitle())->toBe('Updated Title')
        ->and($found->getDescription())->toBe('<p>Updated</p>')
        ->and($found->getCategory())->toBe('cat_mobilitaet');
});

test('editing a feedback_requested idea dispatches ProjectIdeaResubmitted', function (): void {
    $idea = seedIdea($this->repo, 'resubmit');

    // Simulate moderation feedback request
    $persisted = $this->repo->findById($idea->getId());
    $persisted->markUnderReview();
    $persisted->requestFeedback('Please add details', null);
    $this->repo->save($persisted);

    $capturedEvent = null;
    $this->dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(function ($event, $name) use (&$capturedEvent) {
            $capturedEvent = $event;
            return $event instanceof ProjectIdeaResubmitted
                && $name === ProjectIdeaResubmitted::NAME;
        });

    $this->handler->handle(new EditProjectIdeaCommand(
        ideaId: $idea->getId(),
        title: 'Resubmitted Title',
        description: '<p>With more details</p>',
        category: 'cat_energie',
    ));

    $found = $this->repo->findById($idea->getId());

    expect($found->getStatus())->toBe(IdeaStatus::Submitted)
        ->and($capturedEvent)->toBeInstanceOf(ProjectIdeaResubmitted::class);
});
```

- [ ] **Step 2: Run the test**

Run: `composer test:integration`
Expected: 2 new tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/integration/inno_project_idea/UseCase/EditProjectIdeaIntegrationTest.php
git commit -m "test: add EditProjectIdea integration test (edit + resubmit after feedback)"
```

---

### Task 3: Create DeleteProjectIdeaIntegrationTest

**Files:**
- Create: `tests/integration/inno_project_idea/UseCase/DeleteProjectIdeaIntegrationTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

declare(strict_types=1);

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectIdea\Adapter\Out\Persistence\ConcreteProjectIdeaRepository;
use Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea\DeleteProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea\DeleteProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Domain\Exception\IdeaNotEditableException;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;

beforeEach(function (): void {
    $this->db = app(Connection::class);
    $this->db->beginTransaction();

    $this->repo = new ConcreteProjectIdeaRepository($this->db);

    $this->auth = Mockery::mock(AuthInterface::class);
    $this->auth->shouldReceive('getUserId')->andReturn(99);
    $this->auth->shouldReceive('isAuthenticated')->andReturn(true);
    $this->auth->shouldReceive('hasPermission')->andReturn(false);

    $this->transaction = Mockery::mock(TransactionInterface::class);
    $this->transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $this->logger = Mockery::mock(LoggerInterface::class);
    $this->logger->shouldReceive('info');

    $this->handler = new DeleteProjectIdeaHandler(
        $this->repo,
        $this->auth,
        $this->transaction,
        $this->logger,
    );
});

afterEach(function (): void {
    $this->db->rollBack();
    Mockery::close();
});

test('deleting a submitted idea removes it from the database', function (): void {
    $idea = ProjectIdea::submit(
        id: 'del_test_' . substr(md5(uniqid('', true)), 0, 6),
        title: 'To Delete',
        description: '<p>Delete me</p>',
        category: 'cat_energie',
        submitterId: '99',
    );
    $this->repo->save($idea);

    $this->handler->handle(new DeleteProjectIdeaCommand(ideaId: $idea->getId()));

    expect($this->repo->findById($idea->getId()))->toBeNull();
});

test('deleting an approved idea throws IdeaNotEditableException', function (): void {
    $idea = ProjectIdea::submit(
        id: 'del_approved_' . substr(md5(uniqid('', true)), 0, 6),
        title: 'Approved Idea',
        description: '<p>Cannot delete</p>',
        category: 'cat_energie',
        submitterId: '99',
    );
    $idea->approve();
    $this->repo->save($idea);

    $this->handler->handle(new DeleteProjectIdeaCommand(ideaId: $idea->getId()));
})->throws(IdeaNotEditableException::class);
```

- [ ] **Step 2: Run the test**

Run: `composer test:integration`
Expected: 2 new tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/integration/inno_project_idea/UseCase/DeleteProjectIdeaIntegrationTest.php
git commit -m "test: add DeleteProjectIdea integration test (success + blocked on approved)"
```

---

### Task 4: Create ReviewProjectIdeaIntegrationTest

**Files:**
- Create: `tests/integration/inno_moderation/UseCase/ReviewProjectIdeaIntegrationTest.php`

- [ ] **Step 1: Create the test file**

```php
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

function seedReview(ConcreteModerationReviewRepository $repo, string $suffix): ModerationReview
{
    $review = ModerationReview::createPending(
        id: 'rev_test_' . $suffix . '_' . substr(md5(uniqid('', true)), 0, 6),
        ideaId: 'idea_' . $suffix,
    );
    $repo->save($review);
    return $review;
}

test('approving a review sets decision to Approved and dispatches ModerationDecisionMade', function (): void {
    $review = seedReview($this->reviewRepo, 'approve');

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
    $review = seedReview($this->reviewRepo, 'reject');

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
    $review = seedReview($this->reviewRepo, 'feedback');

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
```

- [ ] **Step 2: Run the test**

Run: `composer test:integration`
Expected: 3 new tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/integration/inno_moderation/UseCase/ReviewProjectIdeaIntegrationTest.php
git commit -m "test: add ReviewProjectIdea integration test (approve, reject, feedback)"
```

---

### Task 5: Create SpaceStatusTransitionIntegrationTest

**Files:**
- Create: `tests/integration/inno_project_space/UseCase/SpaceStatusTransitionIntegrationTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

declare(strict_types=1);

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectSpace\Adapter\Out\Persistence\ConcreteProjectSpaceRepository;
use Concrete\Package\InnoProjectSpace\Application\UseCase\ChangeSpaceStatus\ChangeSpaceStatusCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\ChangeSpaceStatus\ChangeSpaceStatusHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceActivated;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCompleted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

beforeEach(function (): void {
    $this->db = app(Connection::class);
    $this->db->beginTransaction();

    $this->repo = new ConcreteProjectSpaceRepository($this->db);

    $this->auth = Mockery::mock(AuthInterface::class);
    $this->auth->shouldReceive('getUserId')->andReturn(1);
    $this->auth->shouldReceive('isAuthenticated')->andReturn(true);
    $this->auth->shouldReceive('hasPermission')->andReturn(true);

    $this->dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $this->transaction = Mockery::mock(TransactionInterface::class);
    $this->transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $this->logger = Mockery::mock(LoggerInterface::class);
    $this->logger->shouldReceive('info');

    $this->handler = new ChangeSpaceStatusHandler(
        $this->repo,
        $this->auth,
        $this->dispatcher,
        $this->transaction,
        $this->logger,
    );
});

afterEach(function (): void {
    $this->db->rollBack();
    Mockery::close();
});

function seedSpace(ConcreteProjectSpaceRepository $repo, string $suffix): ProjectSpace
{
    $space = ProjectSpace::createFromApprovedIdea(
        id: 'status_test_' . $suffix . '_' . substr(md5(uniqid('', true)), 0, 6),
        projectIdeaId: 'idea_status_' . $suffix,
        title: 'Status Transition Test',
        description: '<p>Test</p>',
        initiatorId: '1',
    );
    $repo->save($space);
    return $space;
}

test('draft → active persists and dispatches ProjectSpaceActivated', function (): void {
    $space = seedSpace($this->repo, 'activate');

    $capturedEvent = null;
    $this->dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(function ($event, $name) use (&$capturedEvent) {
            $capturedEvent = $event;
            return $event instanceof ProjectSpaceActivated
                && $name === ProjectSpaceActivated::NAME;
        });

    $this->handler->handle(new ChangeSpaceStatusCommand(
        spaceId: $space->getId(),
        newStatus: 'active',
    ));

    $found = $this->repo->findById($space->getId());

    expect($found->getStatus())->toBe(SpaceStatus::Active)
        ->and($capturedEvent)->toBeInstanceOf(ProjectSpaceActivated::class);
});

test('active → completed persists and dispatches ProjectSpaceCompleted', function (): void {
    $space = seedSpace($this->repo, 'complete');
    $persisted = $this->repo->findById($space->getId());
    $persisted->activate();
    $this->repo->save($persisted);

    $this->dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(fn($event, $name) => $event instanceof ProjectSpaceCompleted);

    $this->handler->handle(new ChangeSpaceStatusCommand(
        spaceId: $space->getId(),
        newStatus: 'completed',
    ));

    $found = $this->repo->findById($space->getId());

    expect($found->getStatus())->toBe(SpaceStatus::Completed);
});

test('completed → archived persists without event', function (): void {
    $space = seedSpace($this->repo, 'archive');
    $persisted = $this->repo->findById($space->getId());
    $persisted->activate();
    $persisted->complete();
    $this->repo->save($persisted);

    $this->dispatcher->shouldReceive('dispatch')->never();

    $this->handler->handle(new ChangeSpaceStatusCommand(
        spaceId: $space->getId(),
        newStatus: 'archived',
    ));

    $found = $this->repo->findById($space->getId());

    expect($found->getStatus())->toBe(SpaceStatus::Archived);
});

test('full lifecycle: draft → active → completed → archived', function (): void {
    $space = seedSpace($this->repo, 'lifecycle');
    $this->dispatcher->shouldReceive('dispatch')->times(2); // activated + completed

    $this->handler->handle(new ChangeSpaceStatusCommand($space->getId(), 'active'));
    $this->handler->handle(new ChangeSpaceStatusCommand($space->getId(), 'completed'));
    $this->handler->handle(new ChangeSpaceStatusCommand($space->getId(), 'archived'));

    $found = $this->repo->findById($space->getId());

    expect($found->getStatus())->toBe(SpaceStatus::Archived);
});

test('invalid transition draft → completed throws DomainException', function (): void {
    $space = seedSpace($this->repo, 'invalid');
    $this->dispatcher->shouldReceive('dispatch')->never();

    $this->handler->handle(new ChangeSpaceStatusCommand($space->getId(), 'completed'));
})->throws(\DomainException::class);
```

- [ ] **Step 2: Run the test**

Run: `composer test:integration`
Expected: 5 new tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/integration/inno_project_space/UseCase/SpaceStatusTransitionIntegrationTest.php
git commit -m "test: add SpaceStatusTransition integration test (full lifecycle + invalid)"
```

---

### Task 6: Create JoinRequestFlowIntegrationTest

**Files:**
- Create: `tests/integration/inno_project_space/UseCase/JoinRequestFlowIntegrationTest.php`

- [ ] **Step 1: Read the handler files to understand signatures**

Run: Read these files:
- `public/packages/inno_project_space/src/Application/UseCase/RequestToJoin/RequestToJoinHandler.php`
- `public/packages/inno_project_space/src/Application/UseCase/RequestToJoin/RequestToJoinCommand.php`
- `public/packages/inno_project_space/src/Application/UseCase/HandleJoinRequest/HandleJoinRequestHandler.php`
- `public/packages/inno_project_space/src/Application/UseCase/HandleJoinRequest/HandleJoinRequestCommand.php`

Understand the constructor dependencies, command fields, and handler logic before writing tests.

- [ ] **Step 2: Create the test file**

Write the test following the same pattern as Tasks 1-5:
- `beforeEach`: real repo + mocked auth/transaction/dispatcher/logger
- Transaction-rollback isolation
- Test: user requests to join → membership request persisted on space
- Test: initiator accepts → member added to space
- Test: initiator rejects → request removed, member not added

The exact code depends on the handler signatures found in Step 1. Use `seedSpace()` (same pattern as Task 5) to create a space with an active status, then exercise the join flow.

- [ ] **Step 3: Run the test**

Run: `composer test:integration`

- [ ] **Step 4: Commit**

```bash
git add tests/integration/inno_project_space/UseCase/JoinRequestFlowIntegrationTest.php
git commit -m "test: add JoinRequestFlow integration test (request, accept, reject)"
```

---

### Task 7: Create EventChainIntegrationTest

**Files:**
- Create: `tests/integration/shared/EventChainIntegrationTest.php`

- [ ] **Step 1: Create the test file**

This test uses the CMS `Events` facade to dispatch events and verify cross-package listeners fire correctly. These tests require the full CMS with all packages installed (they exercise the listener wiring from `on_start()`).

```php
<?php

declare(strict_types=1);

/**
 * Integration test: Cross-package event chains.
 *
 * Requires: Full CMS with all 5 packages installed and on_start() listeners registered.
 * Run: composer test:integration (in Docker or with local CMS)
 */

use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Support\Facade\Events;
use Concrete\Package\InnoModeration\Adapter\Out\Persistence\ConcreteModerationReviewRepository;
use Concrete\Package\InnoProjectIdea\Adapter\Out\Persistence\ConcreteProjectIdeaRepository;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectSpace\Adapter\Out\Persistence\ConcreteProjectSpaceRepository;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;

beforeEach(function (): void {
    $this->db = app(Connection::class);
    $this->db->beginTransaction();

    $this->ideaRepo = new ConcreteProjectIdeaRepository($this->db);
    $this->reviewRepo = new ConcreteModerationReviewRepository($this->db);
    $this->spaceRepo = new ConcreteProjectSpaceRepository($this->db);
});

afterEach(function (): void {
    $this->db->rollBack();
});

test('ProjectIdeaSubmitted → moderation review auto-created', function (): void {
    $ideaId = 'chain_submit_' . substr(md5(uniqid('', true)), 0, 8);

    Events::dispatch(
        new ProjectIdeaSubmitted($ideaId, '1', 'Chain Test Idea'),
        ProjectIdeaSubmitted::NAME,
    );

    $review = $this->reviewRepo->findByIdeaId($ideaId);

    expect($review)->not->toBeNull()
        ->and($review->getIdeaId())->toBe($ideaId);
})->skip(
    !class_exists(\Concrete\Core\Support\Facade\Events::class),
    'Requires full CMS with all packages installed'
);

test('ModerationDecisionMade(approved) → idea approved + project space created', function (): void {
    // Seed an idea in Submitted status
    $idea = ProjectIdea::submit(
        id: 'chain_approve_' . substr(md5(uniqid('', true)), 0, 8),
        title: 'Chain Approval Test',
        description: '<p>Will be approved</p>',
        category: 'cat_energie',
        submitterId: '1',
    );
    $this->ideaRepo->save($idea);

    // Dispatch approval decision
    Events::dispatch(
        new ModerationDecisionMade(
            ideaId: $idea->getId(),
            reviewerId: '10',
            approved: true,
            reason: 'Approved for testing',
        ),
        ModerationDecisionMade::NAME,
    );

    // Verify idea is now Approved
    $updatedIdea = $this->ideaRepo->findById($idea->getId());
    expect($updatedIdea->getStatus()->value)->toBe('approved');

    // Verify project space was auto-created
    $space = $this->spaceRepo->findByProjectIdeaId($idea->getId());
    expect($space)->not->toBeNull()
        ->and($space->getInitiatorId())->toBe('1');
})->skip(
    !class_exists(\Concrete\Core\Support\Facade\Events::class),
    'Requires full CMS with all packages installed'
);
```

- [ ] **Step 2: Run the test**

Run: `composer test:integration`
Expected: 2 tests pass (if CMS installed) or skip gracefully

- [ ] **Step 3: Commit**

```bash
git add tests/integration/shared/EventChainIntegrationTest.php
git commit -m "test: add cross-package event chain integration tests (submit→review, approve→space)"
```

---

### Task 8: Unskip existing EventDispatchIntegrationTest

**Files:**
- Modify: `tests/integration/shared/EventDispatchIntegrationTest.php:69,89`

- [ ] **Step 1: Change skip to conditional skip**

The 2 skipped tests have a hard `->skip('...')`. Change them to conditionally skip only when the CMS Events facade isn't available (same pattern as Task 7):

Line 69 — change:
```php
// OLD:
)->skip('Requires full CMS with all packages installed and on_start() listeners registered');
// NEW:
)->skip(
    !class_exists(\Concrete\Core\Support\Facade\Events::class),
    'Requires full CMS with all packages installed and on_start() listeners registered'
);
```

Line 89 — same change:
```php
// OLD:
)->skip('Requires full CMS with all packages installed and on_start() listeners registered');
// NEW:
)->skip(
    !class_exists(\Concrete\Core\Support\Facade\Events::class),
    'Requires full CMS with all packages installed and on_start() listeners registered'
);
```

- [ ] **Step 2: Run the test**

Run: `composer test:integration`
Expected: Tests pass when CMS is available, skip gracefully when not

- [ ] **Step 3: Commit**

```bash
git add tests/integration/shared/EventDispatchIntegrationTest.php
git commit -m "test: make event dispatch tests conditionally skip instead of always skip"
```

---

### Task 9: Verify all integration tests pass and commit summary

**Files:** None (verification only)

- [ ] **Step 1: Run the full integration test suite**

Run: `composer test:integration`

Expected output (when CMS not installed — local dev without DB):
```
Tests: X skipped (graceful skip from bootstrap guard)
```

Expected output (when CMS installed — Docker or full local):
```
Tests: ~20 passed
```

- [ ] **Step 2: Run PHPStan to verify no type issues**

Run: `composer lint:php`
Expected: No errors

- [ ] **Step 3: Run PHP CS Fixer**

Run: `composer lint:cs:fix`
Expected: No changes or auto-fixed formatting

- [ ] **Step 4: Commit any fixes**

```bash
git add -A tests/integration/
git commit -m "fix: resolve any lint/type issues in integration tests"
```

(Skip this step if there are no issues to fix.)

---

## Summary

After completing all 9 tasks:

- **6 new test files** covering use case handlers with real repositories
- **1 new event chain test file** for cross-package event wiring
- **1 modified file** to unskip existing event dispatch tests
- **~20 new test cases** total (submit, edit, delete, review, status transitions, join requests, event chains)
- All tests use **transaction-rollback isolation** (no cleanup SQL)
- Tests that need full CMS **conditionally skip** when CMS isn't available

**Combined with existing integration tests:** 3 repository test files (35 tests) + 7 use case/event test files (~20 tests) = **~55 integration tests total**

**Next:** Create a PR for the full feature/ideas_moderation branch (Phases 1-4)
