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
