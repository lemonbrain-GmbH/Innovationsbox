<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberLeft;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnMemberLeft
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly ProjectSpaceReaderPort $spaceReader,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(MemberLeft $event): void
    {
        $initiatorId = $this->spaceReader->getInitiatorId($event->getSpaceId());
        $initiatorEmail = $this->userResolver->resolveEmail($initiatorId);

        if ($initiatorEmail === null) {
            $this->logger->warning('Could not resolve initiator email for member left notification', [
                'spaceId' => $event->getSpaceId(),
                'userId' => $event->getUserId(),
                'initiatorId' => $initiatorId,
            ]);
            return;
        }

        $spaceName = str_replace(["\r", "\n"], '', $this->spaceReader->getSpaceName($event->getSpaceId()));
        $memberName = str_replace(["\r", "\n"], '', $this->userResolver->resolveName($event->getUserId()));

        $subject = $memberName . ' hat die Innovation verlassen';
        $bodyHtml = '<p>' . htmlspecialchars($memberName, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . ' hat die Innovation «' . htmlspecialchars($spaceName, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . '» verlassen.</p>';

        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/projektraum/' . $event->getSpaceId(), 'Zur Innovation');

        $this->emailService->send(
            to: $initiatorEmail,
            subject: $subject,
            body: EmailTemplate::wrap($subject, $bodyHtml),
        );

        $this->logger->info('Initiator notified about member leaving space', [
            'spaceId' => $event->getSpaceId(),
            'userId' => $event->getUserId(),
            'initiatorId' => $initiatorId,
        ]);
    }
}
