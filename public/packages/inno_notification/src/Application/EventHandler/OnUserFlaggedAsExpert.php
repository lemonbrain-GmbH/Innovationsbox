<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\UserFlaggedAsExpert;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnUserFlaggedAsExpert
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(UserFlaggedAsExpert $event): void
    {
        $email = $this->userResolver->resolveEmail($event->getUserId());
        if ($email === null) {
            $this->logger->warning('Cannot resolve email for expert flag notification', ['userId' => $event->getUserId()]);

            return;
        }

        $heading = 'Du wurdest als Expert·in markiert';
        $bodyHtml = '<p>Du wurdest als Expert·in auf der Innovationsbox markiert.</p><p>Du kannst jetzt von Innovationen als Expert·in angefragt werden.</p>';
        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/expertanfragen', 'Meine Anfragen');
        $this->emailService->send(
            to: $email,
            subject: $heading,
            body: EmailTemplate::wrap($heading, $bodyHtml),
        );

        $this->logger->info('User notified about expert flag', ['userId' => $event->getUserId()]);
    }
}
