<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnMemberRemoved;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberRemoved;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle() sends email to removed user with space name', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')
        ->with('user_42')
        ->andReturn('user42@example.com');

    $spaceReader->shouldReceive('getSpaceName')
        ->with('space_5')
        ->andReturn('Klimaschutz Projekt');

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            'user42@example.com',
            Mockery::on(fn($subject) => str_contains($subject, 'entfernt')),
            Mockery::on(fn($body) => str_contains($body, 'entfernt') && str_contains($body, 'Klimaschutz Projekt')),
        );

    $logger->shouldReceive('info')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnMemberRemoved($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new MemberRemoved('space_5', 'user_42', 'admin_1'));
});

test('handle() does not send email when resolveEmail returns null', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $spaceReader = Mockery::mock(ProjectSpaceReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $userResolver->shouldReceive('resolveEmail')
        ->with('ghost_user')
        ->andReturn(null);

    $emailService->shouldReceive('send')->never();
    $logger->shouldReceive('warning')->once();

    $siteUrl = Mockery::mock(SiteUrlPort::class);
    $siteUrl->shouldReceive('getBaseUrl')->andReturn('https://example.com');
    $handler = new OnMemberRemoved($emailService, $userResolver, $spaceReader, $logger, $siteUrl);
    $handler->handle(new MemberRemoved('space_1', 'ghost_user', 'admin_1'));
});
