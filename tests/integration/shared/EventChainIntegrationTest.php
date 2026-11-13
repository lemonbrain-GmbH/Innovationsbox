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
        ProjectIdeaSubmitted::NAME,
        new ProjectIdeaSubmitted($ideaId, '1', 'Chain Test Idea'),
    );

    $review = $this->reviewRepo->findByIdeaId($ideaId);

    expect($review)->not->toBeNull()
        ->and($review->getIdeaId())->toBe($ideaId);
})->skip(
    !class_exists(\Concrete\Core\Support\Facade\Events::class),
    'Requires full CMS with all packages installed'
);

test('ModerationDecisionMade(approved) → idea approved + project space created', function (): void {
    $idea = ProjectIdea::submit(
        id: 'chain_approve_' . substr(md5(uniqid('', true)), 0, 8),
        title: 'Chain Approval Test',
        description: '<p>Will be approved</p>',
        category: 'cat_energie',
        submitterId: '1',
    );
    $this->ideaRepo->save($idea);

    Events::dispatch(
        ModerationDecisionMade::NAME,
        new ModerationDecisionMade(
            ideaId: $idea->getId(),
            reviewerId: '10',
            approved: true,
            reason: 'Approved for testing',
        ),
    );

    $updatedIdea = $this->ideaRepo->findById($idea->getId());
    expect($updatedIdea->getStatus()->value)->toBe('approved');

    $space = $this->spaceRepo->findByProjectIdeaId($idea->getId());
    expect($space)->not->toBeNull()
        ->and($space->getInitiatorId())->toBe('1');
})->skip(
    !class_exists(\Concrete\Core\Support\Facade\Events::class),
    'Requires full CMS with all packages installed'
);
