<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationFeedbackRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectIdeaReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnModerationFeedbackRequested
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly ProjectIdeaReaderPort $ideaReader,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(ModerationFeedbackRequested $event): void
    {
        $submitterId = $this->ideaReader->getSubmitterId($event->getIdeaId());
        $submitterEmail = $this->userResolver->resolveEmail($submitterId);

        if ($submitterEmail === null) {
            $this->logger->warning('Could not resolve submitter email for feedback notification', [
                'ideaId' => $event->getIdeaId(),
                'submitterId' => $submitterId,
            ]);
            return;
        }

        $heading = 'Rückmeldung zu deiner Projektidee';
        $deadline = $event->getDeadline() !== null
            ? htmlspecialchars($event->getDeadline(), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : 'keine Frist';

        $bodyHtml = '<p>Ein Moderator hat eine Rückmeldung zu deiner Projektidee hinterlassen.</p>'
            . '<p><strong>Anmerkungen:</strong> ' . htmlspecialchars($event->getNotes(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>'
            . '<p><strong>Frist:</strong> ' . $deadline . '</p>'
            . '<p><strong>Ideen-ID:</strong> ' . htmlspecialchars($event->getIdeaId(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/projektidee/meine', 'Idee bearbeiten');

        $this->emailService->send(
            to: $submitterEmail,
            subject: $heading,
            body: EmailTemplate::wrap($heading, $bodyHtml),
        );

        $this->logger->info('Submitter notified about feedback request', [
            'ideaId' => $event->getIdeaId(),
            'submitterId' => $submitterId,
        ]);
    }
}
