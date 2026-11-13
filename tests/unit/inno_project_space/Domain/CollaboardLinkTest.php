<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidCollaboardUrlException;
use Concrete\Package\InnoProjectSpace\Domain\Model\CollaboardLink;

test('create() builds a CollaboardLink with correct properties', function () {
    $link = CollaboardLink::create('link_1', 'Brainstorming', 'https://web.collaboard.app/board/abc123');
    expect($link->getId())->toBe('link_1')
        ->and($link->getTitle())->toBe('Brainstorming')
        ->and($link->getUrl())->toBe('https://web.collaboard.app/board/abc123')
        ->and($link->getAddedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('create() rejects non-collaboard URLs', function () {
    CollaboardLink::create('link_1', 'Bad', 'https://miro.com/board/abc');
})->throws(InvalidCollaboardUrlException::class);

test('create() accepts web.collaboard.app URLs', function () {
    $link = CollaboardLink::create('link_1', 'Board', 'https://web.collaboard.app/board/xyz');
    expect($link->getUrl())->toBe('https://web.collaboard.app/board/xyz');
});

test('create() rejects URLs with web.collaboard.app as substring', function () {
    CollaboardLink::create('link_1', 'Fake', 'https://notcollaboard.app/board/abc');
})->throws(InvalidCollaboardUrlException::class);
