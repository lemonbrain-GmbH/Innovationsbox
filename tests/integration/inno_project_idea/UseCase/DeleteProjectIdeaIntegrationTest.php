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
