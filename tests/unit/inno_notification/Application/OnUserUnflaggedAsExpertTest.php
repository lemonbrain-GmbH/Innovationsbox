<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnUserUnflaggedAsExpert;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\UserUnflaggedAsExpert;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to unflagged user with correct subject', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')
        ->with('user_7')
        ->andReturn('former_expert@example.com');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'former_expert@example.com',
            'Dein Expert·in-Status wurde entfernt',
            Mockery::on(fn($body) => str_contains($body, '<!DOCTYPE html') && str_contains($body, 'entfernt')),
        );

    $logger->shouldReceive('info')->once();

    $handler = new OnUserUnflaggedAsExpert($emailService, $userResolver, $logger);
    $handler->handle(new UserUnflaggedAsExpert('user_7', 'mod_1'));
});

test('handle() does not send email when resolveEmail returns null', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')->andReturn(null);
    $emailService->shouldReceive('send')->never();
    $logger->shouldReceive('warning')->once();

    $handler = new OnUserUnflaggedAsExpert($emailService, $userResolver, $logger);
    $handler->handle(new UserUnflaggedAsExpert('ghost_user', 'mod_1'));
});
