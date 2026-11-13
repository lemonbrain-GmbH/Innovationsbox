<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\AddCollaboardLink\AddCollaboardLinkCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\AddCollaboardLink\AddCollaboardLinkHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

afterEach(fn() => Mockery::close());

function makeAddCollaboardMocks(string $initiatorId = '1'): array
{
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', $initiatorId);
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    return [$space, $repo, $auth, $tx, $logger];
}

test('handle() adds a collaboard link and saves', function () {
    [$space, $repo, $auth, $tx, $logger] = makeAddCollaboardMocks();

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once()->with($space);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(false);

    $handler = new AddCollaboardLinkHandler($repo, $auth, $tx, $logger);
    $handler->handle(new AddCollaboardLinkCommand(
        spaceId: 'space_1',
        title: 'Brainstorming Board',
        url: 'https://web.collaboard.app/share/abc123',
    ));

    expect($space->getCollaboardLinks())->toHaveCount(1);
});

test('handle() throws AuthException if user lacks space.manage permission', function () {
    [$space, $repo, $auth, $tx, $logger] = makeAddCollaboardMocks();

    $auth->shouldReceive('isAuthenticated')->andReturn(false);

    $handler = new AddCollaboardLinkHandler($repo, $auth, $tx, $logger);
    $handler->handle(new AddCollaboardLinkCommand('space_1', 'Board', 'https://web.collaboard.app/share/abc'));
})->throws(AuthException::class);
