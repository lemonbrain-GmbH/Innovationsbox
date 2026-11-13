<?php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\CreateCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\DeleteCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\ManageCategoryHandler;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\UpdateCategoryCommand;
use Concrete\Package\InnoModeration\Domain\Model\Category;
use Concrete\Package\InnoModeration\Port\Out\CategoryRepositoryPort;
use Psr\Log\LoggerInterface;

afterEach(fn() => Mockery::close());

test('create() saves a new category and returns it', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('save')->once()->with(Mockery::type(Category::class));
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $result = $handler->create(new CreateCategoryCommand('Mobilität'));

    expect($result->getName())->toBe('Mobilität')
        ->and($result->getSlug())->toBe('mobilitaet');
});

test('create() with explicit slug', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('save')->once();
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $result = $handler->create(new CreateCategoryCommand('Bauen', 'bauen-renovieren', 2));

    expect($result->getSlug())->toBe('bauen-renovieren')
        ->and($result->getSortOrder())->toBe(2);
});

test('update() renames category', function () {
    $cat = Category::create('cat_1', 'Old Name');
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findById')->with('cat_1')->andReturn($cat);
    $repo->shouldReceive('save')->once();
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $result = $handler->update(new UpdateCategoryCommand('cat_1', name: 'New Name'));

    expect($result->getName())->toBe('New Name');
});

test('update() changes sort order only', function () {
    $cat = Category::create('cat_1', 'Bauen');
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findById')->with('cat_1')->andReturn($cat);
    $repo->shouldReceive('save')->once();
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $result = $handler->update(new UpdateCategoryCommand('cat_1', sortOrder: 5));

    expect($result->getName())->toBe('Bauen')
        ->and($result->getSortOrder())->toBe(5);
});

test('update() throws when category not found', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findById')->with('missing')->andReturn(null);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $handler->update(new UpdateCategoryCommand('missing', name: 'X'));
})->throws(RuntimeException::class, 'Category not found');

test('delete() removes category', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findById')->with('cat_1')->andReturn(Category::create('cat_1', 'X'));
    $repo->shouldReceive('countSpacesByCategoryId')->with('cat_1')->andReturn(0);
    $repo->shouldReceive('delete')->once()->with('cat_1');
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $handler->delete(new DeleteCategoryCommand('cat_1'));
});

test('delete() throws when category has assigned spaces', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findById')->with('cat_1')->andReturn(Category::create('cat_1', 'X'));
    $repo->shouldReceive('countSpacesByCategoryId')->with('cat_1')->andReturn(3);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $handler->delete(new DeleteCategoryCommand('cat_1'));
})->throws(RuntimeException::class, 'Cannot delete category with assigned project spaces');

test('list() returns all categories', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findAll')->andReturn([
        Category::create('cat_1', 'Bauen'),
        Category::create('cat_2', 'Wohnen'),
    ]);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $result = $handler->list();

    expect($result)->toHaveCount(2);
});
