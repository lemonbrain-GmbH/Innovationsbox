<?php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Domain\Model\Category;

test('create() builds a Category with generated slug', function () {
    $cat = Category::create('cat_1', 'Landwirtschaft');

    expect($cat->getId())->toBe('cat_1')
        ->and($cat->getName())->toBe('Landwirtschaft')
        ->and($cat->getSlug())->toBe('landwirtschaft')
        ->and($cat->getSortOrder())->toBe(0);
});

test('create() with explicit slug uses that slug', function () {
    $cat = Category::create('cat_2', 'Bauen', 'bauen-und-renovieren');

    expect($cat->getSlug())->toBe('bauen-und-renovieren');
});

test('create() with sort order', function () {
    $cat = Category::create('cat_3', 'Mobilität', null, 5);

    expect($cat->getSortOrder())->toBe(5);
});

test('rename() updates name and slug', function () {
    $cat = Category::create('cat_1', 'Old Name');
    $cat->rename('New Name');

    expect($cat->getName())->toBe('New Name')
        ->and($cat->getSlug())->toBe('new-name');
});

test('rename() with explicit slug', function () {
    $cat = Category::create('cat_1', 'Old');
    $cat->rename('New Name', 'custom-slug');

    expect($cat->getSlug())->toBe('custom-slug');
});

test('reorder() updates sort order', function () {
    $cat = Category::create('cat_1', 'Test');
    $cat->reorder(3);

    expect($cat->getSortOrder())->toBe(3);
});

test('fromPersistence() reconstructs entity', function () {
    $created = new DateTimeImmutable('2026-01-15 10:00:00');
    $updated = new DateTimeImmutable('2026-02-20 14:30:00');
    $cat = Category::fromPersistence('cat_1', 'Tourismus', 'tourismus', 2, $created, $updated);

    expect($cat->getId())->toBe('cat_1')
        ->and($cat->getName())->toBe('Tourismus')
        ->and($cat->getSlug())->toBe('tourismus')
        ->and($cat->getSortOrder())->toBe(2)
        ->and($cat->getCreatedAt())->toBe($created)
        ->and($cat->getUpdatedAt())->toBe($updated);
});
