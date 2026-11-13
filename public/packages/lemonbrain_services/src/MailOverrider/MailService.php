<?php

declare(strict_types=1);

namespace MailOverrider;

use Concrete\Core\Mail\Service as CoreMailService;
use Concrete\Core\Support\Facade\Config;

class MailService extends CoreMailService
{
    public function load($template, $pkgHandle = null)
    {
        if (null !== Config::get('mail_overrider.template') && array_key_exists($template, Config::get('mail_overrider.template'))) {
            $pkgHandle = Config::get('mail_overrider.template')[$template];
        }

        parent::load($template, $pkgHandle);
    }
}
