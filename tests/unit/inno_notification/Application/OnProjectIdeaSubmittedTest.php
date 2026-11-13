<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaSubmitted;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to all moderators', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);

    $userResolver->shouldReceive('resolveGroupEmails')
        ->with('Moderator')
        ->andReturn(['mod1@example.com', 'mod2@example.com']);

    $userResolver->shouldReceive('resolveName')
        ->with('user_1')
        ->andReturn('Max Muster');

    $emailService->shouldReceive('send')
        ->twice()
        ->with(
            Mockery::on(fn($to) => in_array($to, ['mod1@example.com', 'mod2@example.com'])),
            Mockery::on(fn($s) => str_contains($s, 'Solar Panels')),
            Mockery::type('string'),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaSubmitted($emailService, $logger, $userResolver, $siteUrl);
    $handler->handle(new ProjectIdeaSubmitted('idea_1', 'user_1', 'Solar Panels'));
});

test('handle() logs warning when no moderators found', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);

    $userResolver->shouldReceive('resolveGroupEmails')
        ->with('Moderator')
        ->andReturn([]);

    $emailService->shouldNotReceive('send');
    $logger->shouldReceive('warning')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaSubmitted($emailService, $logger, $userResolver, $siteUrl);
    $handler->handle(new ProjectIdeaSubmitted('idea_1', 'user_1', 'Solar Panels'));
});

test('handle() body is HTML and contains submitter name', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
    $userResolver = Mockery::mock(UserResolverPort::class);

    $userResolver->shouldReceive('resolveGroupEmails')->andReturn(['mod@example.com']);
    $userResolver->shouldReceive('resolveName')->with('user_1')->andReturn('Anna Beispiel');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, '<!DOCTYPE html') && str_contains($body, 'Anna Beispiel')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaSubmitted($emailService, $logger, $userResolver, $siteUrl);
    $handler->handle(new ProjectIdeaSubmitted('idea_1', 'user_1', 'Solar Panels'));
});

test('handle() body contains idea id', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
    $userResolver = Mockery::mock(UserResolverPort::class);

    $userResolver->shouldReceive('resolveGroupEmails')->andReturn(['mod@example.com']);
    $userResolver->shouldReceive('resolveName')->andReturn('Someone');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, 'idea_42')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnProjectIdeaSubmitted($emailService, $logger, $userResolver, $siteUrl);
    $handler->handle(new ProjectIdeaSubmitted('idea_42', 'user_1', 'Wind Power'));
});
