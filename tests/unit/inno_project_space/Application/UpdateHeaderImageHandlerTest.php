<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\UpdateHeaderImageHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;

afterEach(fn() => Mockery::close());

test('sets header image on project space', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1');
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $repo->shouldReceive('findById')->with('s1')->once()->andReturn($space);
    $repo->shouldReceive('save')->once()->withArgs(fn(ProjectSpace $s) => $s->getHeaderImageFileId() === 123);

    $handler = new UpdateHeaderImageHandler($repo);
    $handler->updateHeaderImage('s1', 123);

    expect($space->getHeaderImageFileId())->toBe(123);
});

test('clears header image when file id is null', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1', 123);
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $repo->shouldReceive('findById')->with('s1')->once()->andReturn($space);
    $repo->shouldReceive('save')->once()->withArgs(fn(ProjectSpace $s) => $s->getHeaderImageFileId() === null);

    $handler = new UpdateHeaderImageHandler($repo);
    $handler->updateHeaderImage('s1', null);

    expect($space->getHeaderImageFileId())->toBeNull();
});

test('throws when space not found', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $repo->shouldReceive('findById')->with('s1')->once()->andReturn(null);

    $handler = new UpdateHeaderImageHandler($repo);
    $handler->updateHeaderImage('s1', 123);
})->throws(\RuntimeException::class);
