<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Adapter\Out\Email;

use Concrete\Core\Mail\Service as MailService;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;

class ConcreteEmailService implements EmailServiceInterface
{
    /** @param MailService<\Symfony\Component\Mime\Email> $mailer */
    public function __construct(private readonly MailService $mailer) {}

    public function send(string $to, string $subject, string $body): void
    {
        $this->mailer->reset();
        $this->mailer->to($to);
        $this->mailer->setSubject($subject);
        $this->mailer->setBody(false);
        $this->mailer->setBodyHTML($body);
        $this->mailer->sendMail();
    }
}
