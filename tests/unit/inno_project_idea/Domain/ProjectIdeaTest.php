<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;

test('submit() creates idea with Submitted status and correct fields', function () {
    $idea = ProjectIdea::submit('id_1', 'My Idea', 'Great description', 'tech', 'user_1');
    expect($idea->getId())->toBe('id_1')
        ->and($idea->getTitle())->toBe('My Idea')
        ->and($idea->getDescription())->toBe('Great description')
        ->and($idea->getCategory())->toBe('tech')
        ->and($idea->getSubmitterId())->toBe('user_1')
        ->and($idea->getStatus())->toBe(IdeaStatus::Submitted);
});

test('approve() changes status to Approved', function () {
    $idea = ProjectIdea::submit('id_1', 'My Idea', 'Desc', 'tech', 'user_1');
    $idea->approve();
    expect($idea->getStatus())->toBe(IdeaStatus::Approved)
        ->and($idea->getUpdatedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('reject() changes status to Rejected', function () {
    $idea = ProjectIdea::submit('id_1', 'My Idea', 'Desc', 'tech', 'user_1');
    $idea->reject();
    expect($idea->getStatus())->toBe(IdeaStatus::Rejected);
});

use Concrete\Package\InnoProjectIdea\Domain\Exception\IdeaNotEditableException;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaAttachment;

test('edit() updates title, description, category when status is Submitted', function () {
    $idea = ProjectIdea::submit('id_1', 'Old Title', 'Old Desc', 'cat_1', 'user_1');
    $idea->edit('New Title', 'New Desc', 'cat_2');
    expect($idea->getTitle())->toBe('New Title')
        ->and($idea->getDescription())->toBe('New Desc')
        ->and($idea->getCategory())->toBe('cat_2');
});

test('edit() throws IdeaNotEditableException when status is Approved', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->approve();
    $idea->edit('X', 'Y', 'cat_2');
})->throws(IdeaNotEditableException::class);

test('edit() throws IdeaNotEditableException when status is Rejected', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->reject('Not relevant');
    $idea->edit('X', 'Y', 'cat_2');
})->throws(IdeaNotEditableException::class);

test('addAttachment() adds to collection', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $att = IdeaAttachment::create('att_1', 'photo.jpg', 42, 'image/jpeg', 1_000_000);
    $idea->addAttachment($att);
    expect($idea->getAttachments())->toHaveCount(1)
        ->and($idea->getAttachments()[0]->getId())->toBe('att_1');
});

test('addAttachment() throws when 5 attachments reached', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    for ($i = 0; $i < 5; $i++) {
        $idea->addAttachment(IdeaAttachment::create("att_{$i}", "f{$i}.jpg", $i + 1, 'image/jpeg', 100));
    }
    $idea->addAttachment(IdeaAttachment::create('att_6', 'extra.jpg', 99, 'image/jpeg', 100));
})->throws(\Concrete\Package\InnoProjectIdea\Domain\Exception\InvalidAttachmentException::class);

test('addAttachment() throws when idea is not editable', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->approve();
    $att = IdeaAttachment::create('att_1', 'photo.jpg', 42, 'image/jpeg', 100);
    $idea->addAttachment($att);
})->throws(IdeaNotEditableException::class);

test('removeAttachment() removes by id', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->addAttachment(IdeaAttachment::create('att_1', 'a.jpg', 1, 'image/jpeg', 100));
    $idea->addAttachment(IdeaAttachment::create('att_2', 'b.jpg', 2, 'image/jpeg', 100));
    $idea->removeAttachment('att_1');
    expect($idea->getAttachments())->toHaveCount(1)
        ->and($idea->getAttachments()[0]->getId())->toBe('att_2');
});

test('reject() stores rejection reason', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->reject('Does not meet criteria');
    expect($idea->getStatus())->toBe(IdeaStatus::Rejected)
        ->and($idea->getRejectionReason())->toBe('Does not meet criteria');
});

test('setProjectSpaceId() stores the space reference', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->approve();
    $idea->setProjectSpaceId('space_123');
    expect($idea->getProjectSpaceId())->toBe('space_123');
});

test('guardEditable() allows FeedbackRequested status', function () {
    $idea = ProjectIdea::reconstitute(
        id: 'id_1',
        title: 'Title',
        description: 'Desc',
        category: 'cat_1',
        status: IdeaStatus::FeedbackRequested,
        submitterId: 1,
        createdAt: new DateTimeImmutable(),
        updatedAt: new DateTimeImmutable(),
        feedbackNotes: 'Please fix this',
        feedbackDeadline: '2026-06-15',
    );
    $idea->edit('New Title', 'New Desc', 'cat_2');
    expect($idea->getTitle())->toBe('New Title')
        ->and($idea->getStatus())->toBe(IdeaStatus::Submitted)
        ->and($idea->getFeedbackNotes())->toBeNull()
        ->and($idea->getFeedbackDeadline())->toBeNull();
});

test('requestFeedback() sets status and feedback fields', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->requestFeedback('Please elaborate', '2026-07-01');
    expect($idea->getStatus())->toBe(IdeaStatus::FeedbackRequested)
        ->and($idea->getFeedbackNotes())->toBe('Please elaborate')
        ->and($idea->getFeedbackDeadline())->toBe('2026-07-01');
});

test('edit() while FeedbackRequested resubmits to Submitted and clears feedback', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->requestFeedback('Fix it', '2026-08-01');
    expect($idea->getStatus())->toBe(IdeaStatus::FeedbackRequested);
    $idea->edit('Fixed Title', 'Fixed Desc', 'cat_1');
    expect($idea->getStatus())->toBe(IdeaStatus::Submitted)
        ->and($idea->getTitle())->toBe('Fixed Title')
        ->and($idea->getFeedbackNotes())->toBeNull()
        ->and($idea->getFeedbackDeadline())->toBeNull();
});

test('edit() throws IdeaNotEditableException when status is UnderReview', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->markUnderReview();
    $idea->edit('X', 'Y', 'cat_2');
})->throws(IdeaNotEditableException::class);

test('markUnderReview() sets status to UnderReview', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->markUnderReview();
    expect($idea->getStatus())->toBe(IdeaStatus::UnderReview);
});

test('resubmit() sets status back to Submitted and clears feedback', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->requestFeedback('Notes', '2026-06-01');
    $idea->resubmit();
    expect($idea->getStatus())->toBe(IdeaStatus::Submitted)
        ->and($idea->getFeedbackNotes())->toBeNull()
        ->and($idea->getFeedbackDeadline())->toBeNull();
});

test('reconstitute() restores all fields including new ones', function () {
    $att = IdeaAttachment::fromPersistence('att_1', 'f.jpg', 1, 'image/jpeg', 100, new DateTimeImmutable());
    $idea = ProjectIdea::reconstitute(
        id: 'id_1',
        title: 'T',
        description: 'D',
        category: 'c',
        status: IdeaStatus::Rejected,
        submitterId: 1,
        createdAt: new DateTimeImmutable(),
        updatedAt: new DateTimeImmutable(),
        rejectionReason: 'Bad idea',
        projectSpaceId: null,
        attachments: [$att],
        feedbackNotes: 'Some notes',
        feedbackDeadline: '2026-06-30',
    );
    expect($idea->getRejectionReason())->toBe('Bad idea')
        ->and($idea->getProjectSpaceId())->toBeNull()
        ->and($idea->getAttachments())->toHaveCount(1)
        ->and($idea->getFeedbackNotes())->toBe('Some notes')
        ->and($idea->getFeedbackDeadline())->toBe('2026-06-30');
});
