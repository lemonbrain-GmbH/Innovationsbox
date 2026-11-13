<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\UserUnflaggedAsExpert;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnUserUnflaggedAsExpert
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(UserUnflaggedAsExpert $event): void
    {
        $email = $this->userResolver->resolveEmail($event->getUserId());
        if ($email === null) {
            $this->logger->warning('Cannot resolve email for expert unflag notification', ['userId' => $event->getUserId()]);

            return;
        }

        $heading = 'Dein Expert·in-Status wurde entfernt';
        $this->emailService->send(
            to: $email,
            subject: $heading,
            body: EmailTemplate::wrap($heading, '<p>Dein Expert·in-Status auf der Innovationsbox wurde entfernt.</p>'),
        );

        $this->logger->info('User notified about expert unflag', ['userId' => $event->getUserId()]);
    }
}
