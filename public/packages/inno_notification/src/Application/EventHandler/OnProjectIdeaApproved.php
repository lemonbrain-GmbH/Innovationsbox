<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnProjectIdeaApproved
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(ProjectIdeaApproved $event): void
    {
        $submitterEmail = $this->userResolver->resolveEmail($event->getSubmitterId());

        if ($submitterEmail === null) {
            $this->logger->warning('Could not resolve submitter email for approval notification', [
                'ideaId' => $event->getIdeaId(),
                'submitterId' => $event->getSubmitterId(),
            ]);
            return;
        }

        $heading = 'Deine Projektidee wurde freigegeben!';
        $bodyHtml = '<p>Gratulation! Deine Projektidee wurde freigegeben. Eine Innovation wurde für dich erstellt.</p>'
            . '<p><strong>Ideen-ID:</strong> ' . htmlspecialchars($event->getIdeaId(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/projektidee/meine', 'Meine Projektideen');

        $this->emailService->send(
            to: $submitterEmail,
            subject: $heading,
            body: EmailTemplate::wrap($heading, $bodyHtml),
        );

        $this->logger->info('Submitter notified about approval', [
            'ideaId' => $event->getIdeaId(),
            'submitterId' => $event->getSubmitterId(),
        ]);
    }
}
