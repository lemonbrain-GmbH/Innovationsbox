<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Domain\Exception\InvalidAttachmentException;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaAttachment;

test('create() builds attachment with valid image', function () {
    $att = IdeaAttachment::create('att_1', 'photo.jpg', 42, 'image/jpeg', 2_000_000);
    expect($att->getId())->toBe('att_1')
        ->and($att->getFilename())->toBe('photo.jpg')
        ->and($att->getFileManagerId())->toBe(42)
        ->and($att->getMimeType())->toBe('image/jpeg')
        ->and($att->getSize())->toBe(2_000_000)
        ->and($att->getAddedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('create() accepts PDF', function () {
    $att = IdeaAttachment::create('att_2', 'doc.pdf', 43, 'application/pdf', 1_000_000);
    expect($att->getMimeType())->toBe('application/pdf');
});

test('create() accepts docx', function () {
    $att = IdeaAttachment::create('att_3', 'file.docx', 44, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 500_000);
    expect($att->getMimeType())->toBe('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
});

test('create() rejects invalid MIME type', function () {
    IdeaAttachment::create('att_4', 'virus.exe', 45, 'application/x-msdownload', 100);
})->throws(InvalidAttachmentException::class);

test('create() rejects file exceeding 10MB', function () {
    IdeaAttachment::create('att_5', 'huge.jpg', 46, 'image/jpeg', 11_000_000);
})->throws(InvalidAttachmentException::class);

test('fromPersistence() reconstructs without validation', function () {
    $att = IdeaAttachment::fromPersistence('att_1', 'photo.jpg', 42, 'image/jpeg', 2_000_000, new DateTimeImmutable('2026-01-01'));
    expect($att->getId())->toBe('att_1')
        ->and($att->getAddedAt()->format('Y-m-d'))->toBe('2026-01-01');
});
