<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnProjectIdeaRejected
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(ProjectIdeaRejected $event): void
    {
        $submitterEmail = $this->userResolver->resolveEmail($event->getSubmitterId());

        if ($submitterEmail === null) {
            $this->logger->warning('Could not resolve submitter email for rejection notification', [
                'ideaId' => $event->getIdeaId(),
                'submitterId' => $event->getSubmitterId(),
            ]);
            return;
        }

        $heading = 'Projektidee konnte nicht freigegeben werden';
        $bodyHtml = '<p>Leider wurde deine Projektidee nicht freigegeben.</p>'
            . '<p><strong>Grund:</strong> ' . htmlspecialchars($event->getReason(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>'
            . '<p><strong>Ideen-ID:</strong> ' . htmlspecialchars($event->getIdeaId(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/projektidee/meine', 'Meine Projektideen');

        $this->emailService->send(
            to: $submitterEmail,
            subject: $heading,
            body: EmailTemplate::wrap($heading, $bodyHtml),
        );

        $this->logger->info('Submitter notified about rejection', [
            'ideaId' => $event->getIdeaId(),
            'submitterId' => $event->getSubmitterId(),
        ]);
    }
}
