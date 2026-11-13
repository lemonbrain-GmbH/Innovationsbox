<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\EventHandler\OnProjectIdeaApproved;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('header image file id is passed from event to project space', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);

    $savedSpace = null;
    $repo->shouldReceive('save')->once()->withArgs(function (ProjectSpace $space) use (&$savedSpace) {
        $savedSpace = $space;
        return true;
    });
    $dispatcher->shouldReceive('dispatch')->once();
    $logger->shouldReceive('info')->once();
    $transaction->shouldReceive('transactional')->once()->andReturnUsing(fn(callable $fn) => $fn());

    $event = new ProjectIdeaApproved(
        ideaId: 'idea_1',
        submitterId: '42',
        categoryId: 'cat_1',
        title: 'Test',
        description: 'Desc',
        headerImageFileId: 999,
    );

    $handler = new OnProjectIdeaApproved($repo, $dispatcher, $logger, $transaction);
    $handler->handle($event);

    expect($savedSpace)->not->toBeNull();
    expect($savedSpace->getHeaderImageFileId())->toBe(999);
});
