<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnUserRegistered;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to each moderator', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $moderatorLoader = fn() => ['mod1@example.com', 'mod2@example.com'];

    $emailService->shouldReceive('send')->twice();
    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnUserRegistered($emailService, $logger, $siteUrl, $moderatorLoader);

    $userInfo = Mockery::mock(\stdClass::class);
    $userInfo->shouldReceive('getUserName')->andReturn('NewUser');
    $userInfo->shouldReceive('getUserEmail')->andReturn('newuser@example.com');

    $event = Mockery::mock(\stdClass::class);
    $event->shouldReceive('getUserInfoObject')->andReturn($userInfo);

    $handler->handle($event);
});

test('handle() does not send email when moderator list is empty', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $moderatorLoader = fn() => [];

    $emailService->shouldReceive('send')->never();
    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnUserRegistered($emailService, $logger, $siteUrl, $moderatorLoader);

    $userInfo = Mockery::mock(\stdClass::class);
    $userInfo->shouldReceive('getUserName')->andReturn('NewUser');
    $userInfo->shouldReceive('getUserEmail')->andReturn('newuser@example.com');

    $event = Mockery::mock(\stdClass::class);
    $event->shouldReceive('getUserInfoObject')->andReturn($userInfo);

    $handler->handle($event);
});

test('handle() sends email with correct subject and HTML body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $moderatorLoader = fn() => ['mod@example.com'];

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'mod@example.com',
            'Neue Registrierung auf der Innovationsbox',
            Mockery::on(fn($body) => str_contains($body, '<!DOCTYPE html') && str_contains($body, 'NewUser')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnUserRegistered($emailService, $logger, $siteUrl, $moderatorLoader);

    $userInfo = Mockery::mock(\stdClass::class);
    $userInfo->shouldReceive('getUserName')->andReturn('NewUser');
    $userInfo->shouldReceive('getUserEmail')->andReturn('newuser@example.com');

    $event = Mockery::mock(\stdClass::class);
    $event->shouldReceive('getUserInfoObject')->andReturn($userInfo);

    $handler->handle($event);
});
