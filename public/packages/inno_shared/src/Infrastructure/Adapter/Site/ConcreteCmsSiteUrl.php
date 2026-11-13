<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Site;

use Concrete\Core\Application\Application;
use Concrete\Package\InnoShared\Port\Out\SiteUrlPort;

class ConcreteCmsSiteUrl implements SiteUrlPort
{
    public function __construct(private readonly Application $app) {}

    public function getBaseUrl(): string
    {
        $site = $this->app->make('site')->getSite();
        $canonicalUrl = $site ? (string) $site->getSiteCanonicalURL() : '';

        if ($canonicalUrl !== '') {
            return rtrim($canonicalUrl, '/');
        }

        // Fallback: derive from current request
        $request = $this->app->make('request');

        return rtrim($request->getSchemeAndHttpHost(), '/');
    }
}
