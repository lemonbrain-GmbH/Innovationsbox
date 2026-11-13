<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnStatusUpdatePublished;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\StatusUpdatePublished;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to all members except author', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $spaceReader->shouldReceive('getMemberUserIds')
        ->with('space_1')
        ->andReturn(['author_1', 'member_2', 'member_3']);
    $spaceReader->shouldReceive('getSpaceName')
        ->with('space_1')
        ->andReturn('Grüne Stadt');

    $userResolver->shouldReceive('resolveName')
        ->with('author_1')
        ->andReturn('Max Autor');

    $userResolver->shouldReceive('resolveEmail')
        ->with('member_2')
        ->andReturn('member2@example.com');
    $userResolver->shouldReceive('resolveEmail')
        ->with('member_3')
        ->andReturn('member3@example.com');

    $emailService->shouldReceive('send')
        ->twice()
        ->with(
            Mockery::on(fn($to) => in_array($to, ['member2@example.com', 'member3@example.com'], true)),
            'Neues Update in «Grüne Stadt»',
            Mockery::on(fn($body) => str_contains($body, 'Max Autor') && str_contains($body, 'Grüne Stadt')),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnStatusUpdatePublished($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new StatusUpdatePublished('space_1', 'update_99', 'author_1'));
});

test('handle() does not send email to the author', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $spaceReader->shouldReceive('getMemberUserIds')->andReturn(['author_1', 'member_2']);
    $spaceReader->shouldReceive('getSpaceName')->andReturn('Test Space');
    $userResolver->shouldReceive('resolveName')->andReturn('Author');
    $userResolver->shouldReceive('resolveEmail')
        ->with('member_2')
        ->andReturn('member2@example.com');

    // Only one email sent (not to author_1)
    $emailService->shouldReceive('send')->once()->with('member2@example.com', Mockery::any(), Mockery::any());

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnStatusUpdatePublished($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new StatusUpdatePublished('space_1', 'update_1', 'author_1'));
});

test('handle() skips members with unresolvable email', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $spaceReader->shouldReceive('getMemberUserIds')->andReturn(['author_1', 'member_2', 'ghost_3']);
    $spaceReader->shouldReceive('getSpaceName')->andReturn('Test Space');
    $userResolver->shouldReceive('resolveName')->andReturn('Author');
    $userResolver->shouldReceive('resolveEmail')->with('member_2')->andReturn('member2@example.com');
    $userResolver->shouldReceive('resolveEmail')->with('ghost_3')->andReturn(null);

    $emailService->shouldReceive('send')->once()->with('member2@example.com', Mockery::any(), Mockery::any());
    $logger->shouldReceive('warning')->once();
    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnStatusUpdatePublished($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new StatusUpdatePublished('space_1', 'update_1', 'author_1'));
});

test('handle() logs correct sent count', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $spaceReader->shouldReceive('getMemberUserIds')->andReturn(['author_1', 'member_2', 'member_3', 'ghost_4']);
    $spaceReader->shouldReceive('getSpaceName')->andReturn('Test Space');
    $userResolver->shouldReceive('resolveName')->andReturn('Author');
    $userResolver->shouldReceive('resolveEmail')->with('member_2')->andReturn('member2@example.com');
    $userResolver->shouldReceive('resolveEmail')->with('member_3')->andReturn('member3@example.com');
    $userResolver->shouldReceive('resolveEmail')->with('ghost_4')->andReturn(null);

    $emailService->shouldReceive('send')->twice();
    $logger->shouldReceive('warning')->once();

    $logger->shouldReceive('info')
        ->once()
        ->with('Members notified about status update', Mockery::on(function ($context) {
            return $context['spaceId'] === 'space_1'
                && $context['updateId'] === 'update_42'
                && $context['authorId'] === 'author_1'
                && $context['sentCount'] === 2;
        }));

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnStatusUpdatePublished($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new StatusUpdatePublished('space_1', 'update_42', 'author_1'));
});

test('handle() sends HTML email body', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $spaceReader->shouldReceive('getMemberUserIds')->andReturn(['author_1', 'member_2']);
    $spaceReader->shouldReceive('getSpaceName')->andReturn('Test Space');
    $userResolver->shouldReceive('resolveName')->andReturn('Author');
    $userResolver->shouldReceive('resolveEmail')->with('member_2')->andReturn('member@example.com');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(fn($body) => str_contains($body, '<!DOCTYPE html')),
        );

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnStatusUpdatePublished($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new StatusUpdatePublished('space_1', 'update_1', 'author_1'));
});
