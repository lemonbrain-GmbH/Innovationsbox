<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaApproved;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to resolved submitter address', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')
        ->with('user_42')
        ->andReturn('user42@example.com');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'user42@example.com',
            'Deine Projektidee wurde freigegeben!',
            Mockery::on(fn($body) => str_contains($body, 'idea_99')),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaApproved($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new ProjectIdeaApproved('idea_99', 'user_42'));
});

test('handle() sends HTML email body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $userResolver->shouldReceive('resolveEmail')->andReturn('someone@example.com');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, '<!DOCTYPE html')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaApproved($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new ProjectIdeaApproved('idea_1', 'sub_1'));
});

test('handle() logs info after sending email', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')->andReturn('someone@example.com');
    $emailService->shouldReceive('send')->once();

    $logger->shouldReceive('info')
        ->once()
        ->with('Submitter notified about approval', Mockery::on(function ($context) {
            return $context['ideaId'] === 'idea_1' && $context['submitterId'] === 'sub_1';
        }));

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaApproved($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new ProjectIdeaApproved('idea_1', 'sub_1'));
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
    $handler = new OnProjectIdeaApproved($emailService, $userResolver, $logger, $siteUrl);
    $handler->handle(new ProjectIdeaApproved('idea_1', 'ghost_user'));
});
