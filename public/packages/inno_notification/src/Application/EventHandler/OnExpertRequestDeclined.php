<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequestDeclined;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnExpertRequestDeclined
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly ProjectSpaceReaderPort $spaceReader,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(ExpertRequestDeclined $event): void
    {
        $initiatorId = $this->spaceReader->getInitiatorId($event->getSpaceId());
        $initiatorEmail = $this->userResolver->resolveEmail($initiatorId);

        if ($initiatorEmail === null) {
            $this->logger->warning('Could not resolve initiator email for expert request declined notification', [
                'spaceId' => $event->getSpaceId(),
                'initiatorId' => $initiatorId,
            ]);
            return;
        }

        $spaceName = $this->spaceReader->getSpaceName($event->getSpaceId());
        $expertName = $this->userResolver->resolveName($event->getExpertUserId());

        $heading = 'Expert·in hat die Anfrage abgelehnt';
        $bodyHtml = '<p>' . htmlspecialchars($expertName, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . ' hat die Expertanfrage für die Innovation «'
            . htmlspecialchars($spaceName, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . '» leider abgelehnt.</p>';

        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/projektraum/' . $event->getSpaceId(), 'Zur Innovation');

        $this->emailService->send(
            to: $initiatorEmail,
            subject: $heading,
            body: EmailTemplate::wrap($heading, $bodyHtml),
        );

        $this->logger->info('Initiator notified about declined expert request', [
            'spaceId' => $event->getSpaceId(),
            'initiatorId' => $initiatorId,
            'expertUserId' => $event->getExpertUserId(),
        ]);
    }
}
