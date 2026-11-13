<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveCollaboardLink\RemoveCollaboardLinkCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveCollaboardLink\RemoveCollaboardLinkHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;

afterEach(fn() => Mockery::close());

test('handle() removes a collaboard link and saves', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', '1');
    $space->addCollaboardLink('link_1', 'Board', 'https://web.collaboard.app/board/abc');

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once()->with($space);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(false);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new RemoveCollaboardLinkHandler($repo, $auth, $tx, $logger);
    $handler->handle(new RemoveCollaboardLinkCommand('space_1', 'link_1'));

    expect($space->getCollaboardLinks())->toHaveCount(0);
});
