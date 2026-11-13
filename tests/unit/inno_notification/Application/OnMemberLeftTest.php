<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnMemberLeft;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberLeft;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to initiator with member name and space name', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $spaceReader->shouldReceive('getInitiatorId')
        ->with('space_5')
        ->andReturn('initiator_7');

    $userResolver->shouldReceive('resolveEmail')
        ->with('initiator_7')
        ->andReturn('initiator@example.com');

    $userResolver->shouldReceive('resolveName')
        ->with('user_42')
        ->andReturn('Max Mustermann');

    $spaceReader->shouldReceive('getSpaceName')
        ->with('space_5')
        ->andReturn('Solarenergie Initiative');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'initiator@example.com',
            Mockery::on(fn($subject) => str_contains($subject, 'verlassen')),
            Mockery::on(fn($body) => str_contains($body, 'Max Mustermann') && str_contains($body, 'Solarenergie Initiative')),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnMemberLeft($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new MemberLeft('space_5', 'user_42'));
});

test('handle() does not send email when initiator resolveEmail returns null', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $spaceReader->shouldReceive('getInitiatorId')
        ->with('space_1')
        ->andReturn('initiator_ghost');

    $userResolver->shouldReceive('resolveEmail')
        ->with('initiator_ghost')
        ->andReturn(null);

    $emailService->shouldReceive('send')->never();
    $logger->shouldReceive('warning')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnMemberLeft($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new MemberLeft('space_1', 'user_1'));
});
