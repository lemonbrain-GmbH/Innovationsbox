<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;

test('header image file id is null by default', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1');
    expect($space->getHeaderImageFileId())->toBeNull();
});

test('can set header image', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1');
    $space->setHeaderImage(789);
    expect($space->getHeaderImageFileId())->toBe(789);
});

test('can clear header image', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1');
    $space->setHeaderImage(789);
    $space->setHeaderImage(null);
    expect($space->getHeaderImageFileId())->toBeNull();
});

test('createFromApprovedIdea accepts header image file id', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1', 555);
    expect($space->getHeaderImageFileId())->toBe(555);
});
