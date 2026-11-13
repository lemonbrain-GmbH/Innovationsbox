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

function seedEditIdea(ConcreteProjectIdeaRepository $repo, string $suffix, string $submitterId = '99'): ProjectIdea
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

    $idea = seedEditIdea($this->repo, 'edit');

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
    $idea = seedEditIdea($this->repo, 'resubmit');

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
