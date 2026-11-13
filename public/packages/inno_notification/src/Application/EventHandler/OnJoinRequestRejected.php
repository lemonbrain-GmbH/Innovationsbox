<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnJoinRequestRejected
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly ProjectSpaceReaderPort $spaceReader,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(JoinRequestRejected $event): void
    {
        $requesterEmail = $this->userResolver->resolveEmail($event->getUserId());

        if ($requesterEmail === null) {
            $this->logger->warning('Could not resolve requester email for join request rejected notification', [
                'spaceId' => $event->getSpaceId(),
                'userId' => $event->getUserId(),
            ]);
            return;
        }

        $spaceName = $this->spaceReader->getSpaceName($event->getSpaceId());

        $heading = 'Deine Beitrittsanfrage wurde abgelehnt';
        $bodyHtml = '<p>Deine Beitrittsanfrage wurde leider abgelehnt.</p>'
            . '<p><strong>Innovation:</strong> ' . htmlspecialchars($spaceName, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';

        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/innovationen', 'Innovationen ansehen');

        $this->emailService->send(
            to: $requesterEmail,
            subject: $heading,
            body: EmailTemplate::wrap($heading, $bodyHtml),
        );

        $this->logger->info('Requester notified about rejected join request', [
            'spaceId' => $event->getSpaceId(),
            'userId' => $event->getUserId(),
        ]);
    }
}
