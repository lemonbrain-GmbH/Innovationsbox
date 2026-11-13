<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnExpertRequested;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to expert with requester name and space name', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')
        ->with('expert_10')
        ->andReturn('expert@example.com');
    $userResolver->shouldReceive('resolveName')
        ->with('requester_3')
        ->andReturn('Hans Muster');

    $spaceReader->shouldReceive('getSpaceName')
        ->with('space_9')
        ->andReturn('Windkraft Alpin');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'expert@example.com',
            'Deine Expertise wird angefragt',
            Mockery::on(fn($body) => str_contains($body, 'Hans Muster') && str_contains($body, 'Windkraft Alpin')),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnExpertRequested($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new ExpertRequested('space_9', 'expert_10', 'requester_3', null));
});

test('handle() includes optional message in email body when present', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $userResolver->shouldReceive('resolveEmail')->andReturn('expert@example.com');
    $userResolver->shouldReceive('resolveName')->andReturn('Requester');
    $spaceReader->shouldReceive('getSpaceName')->andReturn('Test Space');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, 'Bitte hilf uns mit deinem Wissen')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnExpertRequested($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new ExpertRequested('space_1', 'expert_1', 'requester_1', 'Bitte hilf uns mit deinem Wissen'));
});

test('handle() does not include message section when message is null', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $userResolver->shouldReceive('resolveEmail')->andReturn('expert@example.com');
    $userResolver->shouldReceive('resolveName')->andReturn('Requester');
    $spaceReader->shouldReceive('getSpaceName')->andReturn('Test Space');

    $capturedBody = null;
    $emailService->shouldReceive('send')
        ->once()
        ->andReturnUsing(function (string $to, string $subject, string $body) use (&$capturedBody) {
            $capturedBody = $body;
        });

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnExpertRequested($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new ExpertRequested('space_1', 'expert_1', 'requester_1', null));

    expect($capturedBody)->not->toContain('<strong>Nachricht</strong>');
});

test('handle() sends HTML email body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $userResolver->shouldReceive('resolveEmail')->andReturn('expert@example.com');
    $userResolver->shouldReceive('resolveName')->andReturn('Requester');
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
    $handler = new OnExpertRequested($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new ExpertRequested('space_1', 'expert_1', 'requester_1', null));
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
    $handler = new OnExpertRequested($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new ExpertRequested('space_1', 'ghost_expert', 'requester_1', null));
});
