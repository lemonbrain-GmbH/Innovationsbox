<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestReceived;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnJoinRequestReceived
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly ProjectSpaceReaderPort $spaceReader,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(JoinRequestReceived $event): void
    {
        $initiatorId = $this->spaceReader->getInitiatorId($event->getSpaceId());
        $initiatorEmail = $this->userResolver->resolveEmail($initiatorId);

        if ($initiatorEmail === null) {
            $this->logger->warning('Could not resolve initiator email for join request notification', [
                'spaceId' => $event->getSpaceId(),
                'initiatorId' => $initiatorId,
            ]);
            return;
        }

        $spaceName = str_replace(["\r", "\n"], '', $this->spaceReader->getSpaceName($event->getSpaceId()));
        $requesterName = $this->userResolver->resolveName($event->getUserId());

        $heading = 'Neue Beitrittsanfrage für «' . $spaceName . '»';
        $bodyHtml = '<p>Es gibt eine neue Beitrittsanfrage für deine Innovation.</p>'
            . '<p><strong>Innovation:</strong> ' . htmlspecialchars($spaceName, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>'
            . '<p><strong>Anfragender:</strong> ' . htmlspecialchars($requesterName, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';

        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/projektraum/' . $event->getSpaceId(), 'Beitrittsanfrage ansehen');

        $this->emailService->send(
            to: $initiatorEmail,
            subject: $heading,
            body: EmailTemplate::wrap($heading, $bodyHtml),
        );

        $this->logger->info('Initiator notified about join request', [
            'spaceId' => $event->getSpaceId(),
            'initiatorId' => $initiatorId,
            'requesterId' => $event->getUserId(),
        ]);
    }
}
