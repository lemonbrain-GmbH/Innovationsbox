<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnMemberInvited;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberInvited;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to invited user with initiator name and space name', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')
        ->with('user_42')
        ->andReturn('user42@example.com');
    $userResolver->shouldReceive('resolveName')
        ->with('initiator_7')
        ->andReturn('Anna Beispiel');

    $spaceReader->shouldReceive('getSpaceName')
        ->with('space_5')
        ->andReturn('Solarenergie Initiative');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'user42@example.com',
            'Du wurdest in eine Innovation eingeladen',
            Mockery::on(fn($body) => str_contains($body, 'Anna Beispiel') && str_contains($body, 'Solarenergie Initiative')),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnMemberInvited($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new MemberInvited('space_5', 'user_42', 'initiator_7'));
});

test('handle() sends HTML email body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $userResolver->shouldReceive('resolveEmail')->andReturn('user@example.com');
    $userResolver->shouldReceive('resolveName')->andReturn('Initiator Name');
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
    $handler = new OnMemberInvited($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new MemberInvited('space_1', 'user_1', 'initiator_1'));
});

test('handle() logs info after sending email', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')->andReturn('user@example.com');
    $userResolver->shouldReceive('resolveName')->andReturn('Initiator');
    $spaceReader->shouldReceive('getSpaceName')->andReturn('My Space');
    $emailService->shouldReceive('send')->once();

    $logger->shouldReceive('info')
        ->once()
        ->with('Invited user notified about space invitation', Mockery::on(function ($context) {
            return $context['spaceId'] === 'space_X'
                && $context['userId'] === 'user_Y'
                && $context['initiatorId'] === 'init_Z';
        }));

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnMemberInvited($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new MemberInvited('space_X', 'user_Y', 'init_Z'));
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
    $handler = new OnMemberInvited($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new MemberInvited('space_1', 'ghost_user', 'initiator_1'));
});
