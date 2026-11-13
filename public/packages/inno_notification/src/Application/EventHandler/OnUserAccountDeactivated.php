<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\UserAccountDeactivated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class OnUserAccountDeactivated
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(UserAccountDeactivated $event): void
    {
        $heading = 'Dein Konto wurde deaktiviert';

        if ($event->isSelfDeletion()) {
            $body = '<p>Dein Konto bei der Innovationsbox wurde auf deinen Wunsch deaktiviert.</p>'
                . '<p>Du kannst dich nicht mehr anmelden. Deine bisherigen Beiträge bleiben erhalten.</p>';
        } else {
            $body = '<p>Dein Konto bei der Innovationsbox wurde von einem Moderator deaktiviert.</p>'
                . '<p><strong>Begründung:</strong> ' . htmlspecialchars($event->getReason(), ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p>Du kannst dich nicht mehr anmelden. Deine bisherigen Beiträge bleiben erhalten.</p>';
        }

        $this->emailService->send(
            to: $event->getUserEmail(),
            subject: $heading,
            body: EmailTemplate::wrap($heading, $body),
        );

        $this->logger->info('User deactivation notification sent', [
            'userId' => $event->getUserId(),
            'selfDeletion' => $event->isSelfDeletion(),
        ]);
    }
}
