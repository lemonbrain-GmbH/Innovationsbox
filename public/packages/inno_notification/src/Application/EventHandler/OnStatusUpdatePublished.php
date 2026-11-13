<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\StatusUpdatePublished;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnStatusUpdatePublished
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly ProjectSpaceReaderPort $spaceReader,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(StatusUpdatePublished $event): void
    {
        $memberIds = $this->spaceReader->getMemberUserIds($event->getSpaceId());
        $spaceName = $this->spaceReader->getSpaceName($event->getSpaceId());
        $authorName = $this->userResolver->resolveName($event->getAuthorId());

        $heading = 'Neues Update in «' . $spaceName . '»';
        $bodyHtml = '<p>' . htmlspecialchars($authorName, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . ' hat ein neues Update in der Innovation «'
            . htmlspecialchars($spaceName, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . '» veröffentlicht.</p>';

        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/projektraum/' . $event->getSpaceId(), 'Update ansehen');

        $sentCount = 0;

        foreach ($memberIds as $memberId) {
            if ($memberId === $event->getAuthorId()) {
                continue;
            }

            $memberEmail = $this->userResolver->resolveEmail($memberId);

            if ($memberEmail === null) {
                $this->logger->warning('Could not resolve member email for status update notification', [
                    'spaceId' => $event->getSpaceId(),
                    'memberId' => $memberId,
                ]);
                continue;
            }

            $this->emailService->send(
                to: $memberEmail,
                subject: $heading,
                body: EmailTemplate::wrap($heading, $bodyHtml),
            );

            $sentCount++;
        }

        $this->logger->info('Members notified about status update', [
            'spaceId' => $event->getSpaceId(),
            'updateId' => $event->getUpdateId(),
            'authorId' => $event->getAuthorId(),
            'sentCount' => $sentCount,
        ]);
    }
}
