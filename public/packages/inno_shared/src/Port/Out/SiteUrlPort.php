<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Port\Out;

interface SiteUrlPort
{
    public function getBaseUrl(): string;
}
