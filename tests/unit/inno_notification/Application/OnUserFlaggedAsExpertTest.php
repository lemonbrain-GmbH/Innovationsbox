<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnUserFlaggedAsExpert;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\UserFlaggedAsExpert;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to flagged user with correct subject', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')
        ->with('user_42')
        ->andReturn('expert@example.com');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'expert@example.com',
            'Du wurdest als Expert·in markiert',
            Mockery::on(fn($body) => str_contains($body, '<!DOCTYPE html') && str_contains($body, 'Expert·in')),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnUserFlaggedAsExpert($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new UserFlaggedAsExpert('user_42', 'mod_1'));
});

test('handle() does not send email when resolveEmail returns null', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')->andReturn(null);
    $emailService->shouldReceive('send')->never();
    $logger->shouldReceive('warning')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnUserFlaggedAsExpert($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new UserFlaggedAsExpert('ghost_user', 'mod_1'));
});
