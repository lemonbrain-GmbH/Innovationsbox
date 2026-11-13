<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Port\Out;

interface EmailServiceInterface
{
    public function send(string $to, string $subject, string $body): void;
}
