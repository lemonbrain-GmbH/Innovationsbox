<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Http;

class SecurityHeadersMiddleware
{
    public function applyHeaders(\Symfony\Component\HttpFoundation\Response $response, bool $isSecure = false): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($isSecure) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}
