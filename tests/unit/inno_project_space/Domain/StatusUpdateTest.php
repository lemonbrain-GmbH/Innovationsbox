<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdate;

test('create() builds a StatusUpdate with correct properties', function () {
    $update = StatusUpdate::create('upd_1', 'Kickoff done', '<p>We started!</p>', 'user_1');
    expect($update->getId())->toBe('upd_1')
        ->and($update->getTitle())->toBe('Kickoff done')
        ->and($update->getBody())->toBe('<p>We started!</p>')
        ->and($update->getAuthorId())->toBe('user_1')
        ->and($update->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class);
});
