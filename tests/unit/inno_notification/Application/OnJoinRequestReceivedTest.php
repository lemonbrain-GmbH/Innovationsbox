<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnJoinRequestReceived;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestReceived;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to initiator with requester name and space name', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $spaceReader->shouldReceive('getInitiatorId')
        ->with('space_1')
        ->andReturn('initiator_5');
    $spaceReader->shouldReceive('getSpaceName')
        ->with('space_1')
        ->andReturn('Klimawald Projekt');

    $userResolver->shouldReceive('resolveEmail')
        ->with('initiator_5')
        ->andReturn('initiator@example.com');
    $userResolver->shouldReceive('resolveName')
        ->with('requester_3')
        ->andReturn('Max Muster');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'initiator@example.com',
            'Neue Beitrittsanfrage für «Klimawald Projekt»',
            Mockery::on(fn($body) => str_contains($body, 'Max Muster') && str_contains($body, 'Klimawald Projekt')),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnJoinRequestReceived($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new JoinRequestReceived('space_1', 'req_99', 'requester_3'));
});

test('handle() sends HTML email body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $spaceReader->shouldReceive('getInitiatorId')->andReturn('initiator_1');
    $spaceReader->shouldReceive('getSpaceName')->andReturn('Test Space');
    $userResolver->shouldReceive('resolveEmail')->andReturn('initiator@example.com');
    $userResolver->shouldReceive('resolveName')->andReturn('Some User');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, '<!DOCTYPE html')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnJoinRequestReceived($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new JoinRequestReceived('space_1', 'req_1', 'user_1'));
});

test('handle() logs info after sending email', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $spaceReader->shouldReceive('getInitiatorId')->with('space_A')->andReturn('init_B');
    $spaceReader->shouldReceive('getSpaceName')->andReturn('My Space');
    $userResolver->shouldReceive('resolveEmail')->andReturn('init@example.com');
    $userResolver->shouldReceive('resolveName')->andReturn('Requester Name');
    $emailService->shouldReceive('send')->once();

    $logger->shouldReceive('info')
        ->once()
        ->with('Initiator notified about join request', Mockery::on(function ($context) {
            return $context['spaceId'] === 'space_A'
                && $context['initiatorId'] === 'init_B'
                && $context['requesterId'] === 'user_C';
        }));

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnJoinRequestReceived($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new JoinRequestReceived('space_A', 'req_1', 'user_C'));
});

test('handle() does not send email when resolveEmail returns null', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $spaceReader->shouldReceive('getInitiatorId')->andReturn('ghost_initiator');
    $userResolver->shouldReceive('resolveEmail')->andReturn(null);
    $emailService->shouldReceive('send')->never();
    $logger->shouldReceive('warning')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnJoinRequestReceived($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new JoinRequestReceived('space_1', 'req_1', 'user_1'));
});
