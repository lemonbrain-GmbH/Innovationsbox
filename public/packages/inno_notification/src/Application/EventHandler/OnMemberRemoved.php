<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberRemoved;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnMemberRemoved
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly ProjectSpaceReaderPort $spaceReader,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(MemberRemoved $event): void
    {
        $removedEmail = $this->userResolver->resolveEmail($event->getUserId());

        if ($removedEmail === null) {
            $this->logger->warning('Could not resolve removed user email for member removed notification', [
                'spaceId' => $event->getSpaceId(),
                'userId' => $event->getUserId(),
                'removedByUserId' => $event->getRemovedByUserId(),
            ]);
            return;
        }

        $spaceName = $this->spaceReader->getSpaceName($event->getSpaceId());

        $subject = 'Du wurdest aus einer Innovation entfernt';
        $bodyHtml = '<p>Du wurdest aus der Innovation «' . htmlspecialchars($spaceName, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . '» entfernt.</p>';

        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/innovationen', 'Innovationen ansehen');

        $this->emailService->send(
            to: $removedEmail,
            subject: $subject,
            body: EmailTemplate::wrap($subject, $bodyHtml),
        );

        $this->logger->info('Removed user notified about space removal', [
            'spaceId' => $event->getSpaceId(),
            'userId' => $event->getUserId(),
            'removedByUserId' => $event->getRemovedByUserId(),
        ]);
    }
}
