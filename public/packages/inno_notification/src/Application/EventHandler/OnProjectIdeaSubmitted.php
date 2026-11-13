<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnProjectIdeaSubmitted
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly LoggerInterface $logger,
        private readonly UserResolverPort $userResolver,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(ProjectIdeaSubmitted $event): void
    {
        $recipients = $this->userResolver->resolveGroupEmails('Moderator');

        if ($recipients === []) {
            $this->logger->warning('No moderators found to notify about submitted idea', [
                'ideaId' => $event->getIdeaId(),
            ]);

            return;
        }

        $submitterName = $this->userResolver->resolveName($event->getSubmitterId());

        $safeTitle = str_replace(["\r", "\n"], '', $event->getTitle());
        $heading = 'Neue Projektidee eingereicht: ' . $safeTitle;
        $bodyHtml = '<p>Eine neue Projektidee wurde eingereicht und wartet auf Moderation.</p>'
            . '<p><strong>Eingereicht von:</strong> ' . htmlspecialchars($submitterName, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>'
            . '<p><strong>ID:</strong> ' . htmlspecialchars($event->getIdeaId(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';

        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/moderation', 'Zur Moderation');

        $subject = 'Neue Projektidee eingereicht: ' . $safeTitle;
        $body = EmailTemplate::wrap($heading, $bodyHtml);

        foreach ($recipients as $email) {
            $this->emailService->send(
                to: $email,
                subject: $subject,
                body: $body,
            );
        }

        $this->logger->info('Moderators notified about new idea', [
            'ideaId' => $event->getIdeaId(),
            'recipients' => $recipients,
        ]);
    }
}
