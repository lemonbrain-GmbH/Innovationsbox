<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnUserAccountDeactivated;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\UserAccountDeactivated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

afterEach(fn() => Mockery::close());

test('handle() sends self-deletion email without reason', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'user@test.ch',
            Mockery::on(fn($s) => str_contains($s, 'deaktiviert')),
            Mockery::on(fn($b) => str_contains($b, 'deaktiviert') && !str_contains($b, 'Begründung')),
        );

    $logger->shouldReceive('info')->once();

    $handler = new OnUserAccountDeactivated($emailService, $logger);
    $handler->handle(new UserAccountDeactivated('42', 'user@test.ch', 'Max', '', '42'));
});

test('handle() sends moderator-deletion email with reason', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'user@test.ch',
            Mockery::on(fn($s) => str_contains($s, 'deaktiviert')),
            Mockery::on(fn($b) => str_contains($b, 'Spam account') && str_contains($b, 'Begründung')),
        );

    $logger->shouldReceive('info')->once();

    $handler = new OnUserAccountDeactivated($emailService, $logger);
    $handler->handle(new UserAccountDeactivated('42', 'user@test.ch', 'Max', 'Spam account', '1'));
});
