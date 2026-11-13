<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnJoinRequestAccepted;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestAccepted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to requesting user with space name', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')
        ->with('user_42')
        ->andReturn('user42@example.com');

    $spaceReader->shouldReceive('getSpaceName')
        ->with('space_7')
        ->andReturn('Solarenergie Initiative');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'user42@example.com',
            'Deine Beitrittsanfrage wurde angenommen',
            Mockery::on(fn($body) => str_contains($body, 'Solarenergie Initiative')),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnJoinRequestAccepted($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new JoinRequestAccepted('space_7', 'req_1', 'user_42'));
});

test('handle() sends HTML email body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $userResolver->shouldReceive('resolveEmail')->andReturn('user@example.com');
    $spaceReader->shouldReceive('getSpaceName')->andReturn('Test Space');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, '<!DOCTYPE html')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnJoinRequestAccepted($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new JoinRequestAccepted('space_1', 'req_1', 'user_1'));
});

test('handle() logs info after sending email', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')->andReturn('user@example.com');
    $spaceReader->shouldReceive('getSpaceName')->andReturn('My Space');
    $emailService->shouldReceive('send')->once();

    $logger->shouldReceive('info')
        ->once()
        ->with('Requester notified about accepted join request', Mockery::on(function ($context) {
            return $context['spaceId'] === 'space_X' && $context['userId'] === 'user_Y';
        }));

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnJoinRequestAccepted($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new JoinRequestAccepted('space_X', 'req_1', 'user_Y'));
});

test('handle() does not send email when resolveEmail returns null', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')->andReturn(null);
    $emailService->shouldReceive('send')->never();
    $logger->shouldReceive('warning')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnJoinRequestAccepted($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new JoinRequestAccepted('space_1', 'req_1', 'ghost_user'));
});
