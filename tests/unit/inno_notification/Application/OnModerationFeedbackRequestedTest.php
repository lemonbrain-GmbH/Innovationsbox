<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnModerationFeedbackRequested;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationFeedbackRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectIdeaReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to submitter with notes and deadline', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $ideaReader = Mockery::mock(ProjectIdeaReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $ideaReader->shouldReceive('getSubmitterId')
        ->with('idea_10')
        ->andReturn('user_5');

    $userResolver->shouldReceive('resolveEmail')
        ->with('user_5')
        ->andReturn('user5@example.com');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'user5@example.com',
            'Rückmeldung zu deiner Projektidee',
            Mockery::on(fn($body) => str_contains($body, 'Bitte überarbeiten') && str_contains($body, '2026-07-01')),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnModerationFeedbackRequested($emailService, $userResolver, $ideaReader, $logger, $siteUrl);
    $handler->handle(new ModerationFeedbackRequested('idea_10', 'reviewer_1', 'Bitte überarbeiten', '2026-07-01'));
});

test('handle() uses "keine Frist" when deadline is null', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $ideaReader = Mockery::mock(ProjectIdeaReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $ideaReader->shouldReceive('getSubmitterId')->andReturn('user_5');
    $userResolver->shouldReceive('resolveEmail')->andReturn('user5@example.com');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, 'keine Frist')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnModerationFeedbackRequested($emailService, $userResolver, $ideaReader, $logger, $siteUrl);
    $handler->handle(new ModerationFeedbackRequested('idea_10', 'reviewer_1', 'Bitte überarbeiten'));
});

test('handle() sends HTML email body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $ideaReader = Mockery::mock(ProjectIdeaReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $ideaReader->shouldReceive('getSubmitterId')->andReturn('user_1');
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
    $handler = new OnModerationFeedbackRequested($emailService, $userResolver, $ideaReader, $logger, $siteUrl);
    $handler->handle(new ModerationFeedbackRequested('idea_1', 'reviewer_1', 'Notes'));
});

test('handle() logs info after sending email', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $ideaReader = Mockery::mock(ProjectIdeaReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $ideaReader->shouldReceive('getSubmitterId')->with('idea_X')->andReturn('user_Y');
    $userResolver->shouldReceive('resolveEmail')->andReturn('user@example.com');
    $emailService->shouldReceive('send')->once();

    $logger->shouldReceive('info')
        ->once()
        ->with('Submitter notified about feedback request', Mockery::on(function ($context) {
            return $context['ideaId'] === 'idea_X' && $context['submitterId'] === 'user_Y';
        }));

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnModerationFeedbackRequested($emailService, $userResolver, $ideaReader, $logger, $siteUrl);
    $handler->handle(new ModerationFeedbackRequested('idea_X', 'reviewer_1', 'Notes'));
});

test('handle() does not send email when resolveEmail returns null', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $ideaReader = Mockery::mock(ProjectIdeaReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $ideaReader->shouldReceive('getSubmitterId')->andReturn('ghost_user');
    $userResolver->shouldReceive('resolveEmail')->andReturn(null);
    $emailService->shouldReceive('send')->never();
    $logger->shouldReceive('warning')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnModerationFeedbackRequested($emailService, $userResolver, $ideaReader, $logger, $siteUrl);
    $handler->handle(new ModerationFeedbackRequested('idea_1', 'reviewer_1', 'Notes'));
});
