<?php

declare(strict_types=1);

namespace MailOverrider;

use Concrete\Core\Foundation\Service\Provider;

class ServiceProvider extends Provider
{
    public function register()
    {
        $this->app->bind('mail', MailService::class);
        $this->app->bind('helper/mail', MailService::class);
    }
}
