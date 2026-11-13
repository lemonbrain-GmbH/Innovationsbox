<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberInvited;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnMemberInvited
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly ProjectSpaceReaderPort $spaceReader,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
    ) {}

    public function handle(MemberInvited $event): void
    {
        $invitedEmail = $this->userResolver->resolveEmail($event->getUserId());

        if ($invitedEmail === null) {
            $this->logger->warning('Could not resolve invited user email for member invited notification', [
                'spaceId' => $event->getSpaceId(),
                'userId' => $event->getUserId(),
            ]);
            return;
        }

        $spaceName = $this->spaceReader->getSpaceName($event->getSpaceId());
        $initiatorName = $this->userResolver->resolveName($event->getInitiatorId());

        $heading = 'Du wurdest in eine Innovation eingeladen';
        $bodyHtml = '<p>' . htmlspecialchars($initiatorName, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . ' hat dich in die Innovation «' . htmlspecialchars($spaceName, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . '» eingeladen. Schau dir die Innovation an und beginne mitzuarbeiten.</p>';

        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/projektraum/' . $event->getSpaceId(), 'Zur Innovation');

        $this->emailService->send(
            to: $invitedEmail,
            subject: $heading,
            body: EmailTemplate::wrap($heading, $bodyHtml),
        );

        $this->logger->info('Invited user notified about space invitation', [
            'spaceId' => $event->getSpaceId(),
            'userId' => $event->getUserId(),
            'initiatorId' => $event->getInitiatorId(),
        ]);
    }
}
