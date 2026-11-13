<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;

class OnUserRegistered
{
    /** @var \Closure(): list<string> */
    private \Closure $moderatorEmailsLoader;

    /**
     * @param null|\Closure(): list<string> $moderatorEmailsLoader
     */
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly LoggerInterface $logger,
        private readonly SiteUrlPort $siteUrl,
        ?\Closure $moderatorEmailsLoader = null,
    ) {
        $this->moderatorEmailsLoader = $moderatorEmailsLoader ?? static function (): array {
            $group = \Concrete\Core\User\Group\Group::getByName('Moderator');
            if ($group === null) {
                return [];
            }
            $emails = [];
            foreach ($group->getGroupMembers() as $member) {
                $ui = \Concrete\Core\User\UserInfo::getByID($member->getUserID());
                if ($ui && $ui->getUserEmail()) {
                    $emails[] = $ui->getUserEmail();
                }
            }

            return $emails;
        };
    }

    public function handle(object $event): void
    {
        $userInfo = $event->getUserInfoObject();
        $userName = $userInfo->getUserName();
        $userEmail = $userInfo->getUserEmail();

        $moderatorEmails = ($this->moderatorEmailsLoader)();
        $heading = 'Neue Registrierung auf der Innovationsbox';
        $bodyHtml = '<p><strong>' . htmlspecialchars($userName, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</strong> (' . htmlspecialchars($userEmail, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ') hat sich auf der Plattform registriert.</p>';
        $bodyHtml .= EmailTemplate::linkButton($this->siteUrl->getBaseUrl() . '/moderation/benutzer', 'Benutzerverwaltung');

        foreach ($moderatorEmails as $email) {
            $this->emailService->send(
                to: $email,
                subject: $heading,
                body: EmailTemplate::wrap($heading, $bodyHtml),
            );
        }

        $this->logger->info('Moderators notified about new registration', [
            'userName' => $userName,
            'notified' => count($moderatorEmails),
        ]);
    }
}
