<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;

test('getPriority() returns 0 by default', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'i1', 'Title', 'Desc', '1');

    expect($space->getPriority())->toBe(0);
});

test('setPriority() sets and getPriority() returns the value', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'i1', 'Title', 'Desc', '1');

    $space->setPriority(1);

    expect($space->getPriority())->toBe(1);
});

test('setPriority(0) clears priority', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'i1', 'Title', 'Desc', '1');
    $space->setPriority(2);

    $space->setPriority(0);

    expect($space->getPriority())->toBe(0);
});

test('setPriority() rejects values above 3', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'i1', 'Title', 'Desc', '1');

    $space->setPriority(4);
})->throws(\DomainException::class);

test('setPriority() rejects negative values', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'i1', 'Title', 'Desc', '1');

    $space->setPriority(-1);
})->throws(\DomainException::class);

test('setPriority() updates updatedAt', function () {
    $space = ProjectSpace::fromPersistence(
        's1',
        'i1',
        'Title',
        'Desc',
        SpaceStatus::Active,
        '1',
        new \DateTimeImmutable('2024-01-01'),
        new \DateTimeImmutable('2024-01-01'),
        ['1'],
        [],
        [],
        [],
    );
    $before = $space->getUpdatedAt();

    $space->setPriority(1);

    expect($space->getUpdatedAt())->toBeGreaterThan($before);
});

test('fromPersistence() restores priority', function () {
    $space = ProjectSpace::fromPersistence(
        's1',
        'i1',
        'Title',
        'Desc',
        SpaceStatus::Active,
        '1',
        new \DateTimeImmutable(),
        new \DateTimeImmutable(),
        ['1'],
        [],
        [],
        [],
        '',
        [],
        null,
        2,
    );

    expect($space->getPriority())->toBe(2);
});
