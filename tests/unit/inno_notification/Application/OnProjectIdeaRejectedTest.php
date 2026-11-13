<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaRejected;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to resolved submitter address with rejection subject', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')
        ->with('user_7')
        ->andReturn('user7@example.com');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'user7@example.com',
            'Projektidee konnte nicht freigegeben werden',
            Mockery::any(),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaRejected($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new ProjectIdeaRejected('idea_5', 'user_7', 'Nicht relevant'));
});

test('handle() includes rejection reason in email body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $userResolver->shouldReceive('resolveEmail')->andReturn('user@example.com');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, 'Zu unspezifisch')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaRejected($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new ProjectIdeaRejected('idea_10', 'user_2', 'Zu unspezifisch'));
});

test('handle() includes idea id in email body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $userResolver->shouldReceive('resolveEmail')->andReturn('user@example.com');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, 'idea_77')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaRejected($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new ProjectIdeaRejected('idea_77', 'user_3', 'Some reason'));
});

test('handle() sends HTML email body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $userResolver->shouldReceive('resolveEmail')->andReturn('user@example.com');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, '<!DOCTYPE html')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaRejected($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new ProjectIdeaRejected('idea_1', 'user_1', 'reason'));
});

test('handle() logs info with ideaId and submitterId', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')->andReturn('user@example.com');
    $emailService->shouldReceive('send')->once();

    $logger->shouldReceive('info')
        ->once()
        ->with('Submitter notified about rejection', Mockery::on(function ($context) {
            return $context['ideaId'] === 'idea_X' && $context['submitterId'] === 'user_Y';
        }));

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaRejected($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new ProjectIdeaRejected('idea_X', 'user_Y', 'reason'));
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
    $handler = new OnProjectIdeaRejected($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new ProjectIdeaRejected('idea_1', 'ghost_user', 'reason'));
});
