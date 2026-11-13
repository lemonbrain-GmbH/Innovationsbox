<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Http;

use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Validates CSRF tokens on state-changing HTTP methods (POST, PUT, DELETE, PATCH).
 *
 * Controllers using this trait must call validateCsrfToken() at the start of
 * every state-changing action, before any business logic.
 *
 * The token is read from the X-CSRF-Token header and validated against the
 * Concrete CMS token service with action 'api'.
 */
trait CsrfValidationTrait
{
    /**
     * Validate the CSRF token for state-changing requests.
     *
     * GET, HEAD, and OPTIONS requests are safe methods and are skipped.
     *
     * @param Request $request The incoming HTTP request
     * @param object|null $tokenService Optional token service (for testing); defaults to $this->app->make('token')
     *
     * @throws AuthException if the token is missing or invalid (403)
     */
    protected function validateCsrfToken(Request $request, ?object $tokenService = null): void
    {
        $safeMethods = ['GET', 'HEAD', 'OPTIONS'];
        if (in_array($request->getMethod(), $safeMethods, true)) {
            return;
        }

        $token = $request->headers->get('X-CSRF-Token', '');

        if ($token === '' || $token === null) {
            throw new AuthException(
                'CSRF token is missing.',
                ['header' => 'X-CSRF-Token'],
            );
        }

        $service = $tokenService ?? $this->app->make('token');

        if (!$service->validate('api', $token)) {
            throw new AuthException(
                'CSRF token is invalid or expired.',
                ['header' => 'X-CSRF-Token'],
            );
        }
    }
}
