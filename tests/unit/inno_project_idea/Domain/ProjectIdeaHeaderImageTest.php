<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;

test('header image file id is null by default', function () {
    $idea = ProjectIdea::submit('idea_1', 'Title', 'Desc', 'cat_1', '42');
    expect($idea->getHeaderImageFileId())->toBeNull();
});

test('can set and get header image file id', function () {
    $idea = ProjectIdea::submit('idea_1', 'Title', 'Desc', 'cat_1', '42');
    $idea->setHeaderImageFileId(123);
    expect($idea->getHeaderImageFileId())->toBe(123);
});

test('can clear header image file id', function () {
    $idea = ProjectIdea::submit('idea_1', 'Title', 'Desc', 'cat_1', '42');
    $idea->setHeaderImageFileId(123);
    $idea->setHeaderImageFileId(null);
    expect($idea->getHeaderImageFileId())->toBeNull();
});

test('reconstitute preserves header image file id', function () {
    $idea = ProjectIdea::reconstitute(
        id: 'idea_1',
        title: 'Title',
        description: 'Desc',
        category: 'cat_1',
        status: IdeaStatus::Submitted,
        submitterId: 42,
        createdAt: new DateTimeImmutable(),
        updatedAt: new DateTimeImmutable(),
        headerImageFileId: 456,
    );
    expect($idea->getHeaderImageFileId())->toBe(456);
});
