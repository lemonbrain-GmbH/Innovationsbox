<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Application\EventHandler\OnProjectSpaceCreated;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCreated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

afterEach(fn() => Mockery::close());

test('handle() sets project space id on the idea and saves it', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $idea = ProjectIdea::submit('idea_1', 'Title', 'Desc', 'cat_1', 'user_1');

    $repo->shouldReceive('findById')->once()->with('idea_1')->andReturn($idea);
    $repo->shouldReceive('save')->once()->with(Mockery::on(function (ProjectIdea $saved) {
        return $saved->getProjectSpaceId() === 'space_99';
    }));
    $logger->shouldReceive('info')->once();

    $handler = new OnProjectSpaceCreated($repo, $logger);
    $handler->handle(new ProjectSpaceCreated('space_99', 'idea_1', 'user_1'));
});

test('handle() logs info with ideaId and spaceId after linking', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $idea = ProjectIdea::submit('idea_2', 'Title', 'Desc', 'cat_1', 'user_1');

    $repo->shouldReceive('findById')->once()->andReturn($idea);
    $repo->shouldReceive('save')->once();
    $logger->shouldReceive('info')
        ->once()
        ->with('Linked project space to idea', Mockery::on(function ($context) {
            return $context['ideaId'] === 'idea_2' && $context['spaceId'] === 'space_5';
        }));

    $handler = new OnProjectSpaceCreated($repo, $logger);
    $handler->handle(new ProjectSpaceCreated('space_5', 'idea_2', 'user_1'));
});

test('handle() does nothing when idea is not found', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->once()->with('unknown_idea')->andReturn(null);
    $repo->shouldReceive('save')->never();
    $logger->shouldReceive('info')->never();

    $handler = new OnProjectSpaceCreated($repo, $logger);
    $handler->handle(new ProjectSpaceCreated('space_1', 'unknown_idea', 'user_1'));
});
