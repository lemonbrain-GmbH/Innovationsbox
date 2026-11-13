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

function seedStatusSpace(ConcreteProjectSpaceRepository $repo, string $suffix): ProjectSpace
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
    $space = seedStatusSpace($this->repo, 'activate');

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
    $space = seedStatusSpace($this->repo, 'complete');
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
    $space = seedStatusSpace($this->repo, 'archive');
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
    $space = seedStatusSpace($this->repo, 'lifecycle');
    $this->dispatcher->shouldReceive('dispatch')->times(2);

    $this->handler->handle(new ChangeSpaceStatusCommand($space->getId(), 'active'));
    $this->handler->handle(new ChangeSpaceStatusCommand($space->getId(), 'completed'));
    $this->handler->handle(new ChangeSpaceStatusCommand($space->getId(), 'archived'));

    $found = $this->repo->findById($space->getId());

    expect($found->getStatus())->toBe(SpaceStatus::Archived);
});

test('invalid transition draft → completed throws DomainException', function (): void {
    $space = seedStatusSpace($this->repo, 'invalid');
    $this->dispatcher->shouldReceive('dispatch')->never();

    $this->handler->handle(new ChangeSpaceStatusCommand($space->getId(), 'completed'));
})->throws(\Exception::class);
