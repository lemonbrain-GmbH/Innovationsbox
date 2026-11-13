<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnExpertRequestAccepted;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequestAccepted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to initiator with expert name and space name', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $spaceReader->shouldReceive('getInitiatorId')
        ->with('space_3')
        ->andReturn('initiator_5');
    $spaceReader->shouldReceive('getSpaceName')
        ->with('space_3')
        ->andReturn('Klimamobilität');

    $userResolver->shouldReceive('resolveEmail')
        ->with('initiator_5')
        ->andReturn('initiator@example.com');
    $userResolver->shouldReceive('resolveName')
        ->with('expert_20')
        ->andReturn('Dr. Müller');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'initiator@example.com',
            'Expert·in hat die Anfrage angenommen',
            Mockery::on(fn($body) => str_contains($body, 'Dr. Müller') && str_contains($body, 'Klimamobilität')),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnExpertRequestAccepted($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new ExpertRequestAccepted('space_3', 'expert_20', 'req_1'));
});

test('handle() sends HTML email body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $spaceReader->shouldReceive('getInitiatorId')->andReturn('initiator_1');
    $spaceReader->shouldReceive('getSpaceName')->andReturn('Test Space');
    $userResolver->shouldReceive('resolveEmail')->andReturn('initiator@example.com');
    $userResolver->shouldReceive('resolveName')->andReturn('Expert Name');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, '<!DOCTYPE html')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnExpertRequestAccepted($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new ExpertRequestAccepted('space_1', 'expert_1', 'req_1'));
});

test('handle() logs info after sending email', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $spaceReader->shouldReceive('getInitiatorId')->with('space_A')->andReturn('init_B');
    $spaceReader->shouldReceive('getSpaceName')->andReturn('My Space');
    $userResolver->shouldReceive('resolveEmail')->andReturn('init@example.com');
    $userResolver->shouldReceive('resolveName')->andReturn('Expert Name');
    $emailService->shouldReceive('send')->once();

    $logger->shouldReceive('info')
        ->once()
        ->with('Initiator notified about accepted expert request', Mockery::on(function ($context) {
            return $context['spaceId'] === 'space_A'
                && $context['initiatorId'] === 'init_B'
                && $context['expertUserId'] === 'expert_C';
        }));

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnExpertRequestAccepted($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new ExpertRequestAccepted('space_A', 'expert_C', 'req_1'));
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
    $handler = new OnExpertRequestAccepted($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new ExpertRequestAccepted('space_1', 'expert_1', 'req_1'));
});
