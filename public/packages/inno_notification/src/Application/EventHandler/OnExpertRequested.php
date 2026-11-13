<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnExpertRequested
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly ProjectSpaceReaderPort $spaceReader,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(ExpertRequested $event): void
    {
        $expertEmail = $this->userResolver->resolveEmail($event->getExpertUserId());

        if ($expertEmail === null) {
            $this->logger->warning('Could not resolve expert email for expert requested notification', [
                'spaceId' => $event->getSpaceId(),
                'expertUserId' => $event->getExpertUserId(),
            ]);
            return;
        }

        $spaceName = $this->spaceReader->getSpaceName($event->getSpaceId());
        $requesterName = $this->userResolver->resolveName($event->getRequestedByUserId());

        $heading = 'Deine Expertise wird angefragt';
        $bodyHtml = '<p>' . htmlspecialchars($requesterName, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . ' bittet um deine Unterstützung in der Innovation «'
            . htmlspecialchars($spaceName, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '».</p>';

        if ($event->getMessage() !== null) {
            $bodyHtml .= '<p><strong>Nachricht:</strong> '
                . htmlspecialchars($event->getMessage(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
        }

        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/expertanfragen', 'Anfrage ansehen');

        $this->emailService->send(
            to: $expertEmail,
            subject: $heading,
            body: EmailTemplate::wrap($heading, $bodyHtml),
        );

        $this->logger->info('Expert notified about expertise request', [
            'spaceId' => $event->getSpaceId(),
            'expertUserId' => $event->getExpertUserId(),
            'requestedByUserId' => $event->getRequestedByUserId(),
        ]);
    }
}
