<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Concrete\Package\InnoShared\Infrastructure\Http\CsrfValidationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * A concrete class that uses the trait so we can test it without a real controller.
 */
class CsrfValidationTestSubject
{
    use CsrfValidationTrait;

    /**
     * Public wrapper so tests can call the protected method.
     */
    public function validate(Request $request, ?object $tokenService = null): void
    {
        $this->validateCsrfToken($request, $tokenService);
    }
}

function makeTokenService(bool $validates): object
{
    $service = Mockery::mock();
    $service->shouldReceive('validate')
        ->andReturn($validates);

    return $service;
}

// --- Safe methods are skipped ---

test('GET requests are not validated', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'GET');

    // Should not throw — no token service needed
    $subject->validate($request);
    expect(true)->toBeTrue();
});

test('HEAD requests are not validated', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'HEAD');

    $subject->validate($request);
    expect(true)->toBeTrue();
});

test('OPTIONS requests are not validated', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'OPTIONS');

    $subject->validate($request);
    expect(true)->toBeTrue();
});

// --- Missing token ---

test('POST without token throws AuthException', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'POST');

    $subject->validate($request, makeTokenService(true));
})->throws(AuthException::class, 'CSRF token is missing.');

test('PUT without token throws AuthException', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'PUT');

    $subject->validate($request, makeTokenService(true));
})->throws(AuthException::class, 'CSRF token is missing.');

test('DELETE without token throws AuthException', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'DELETE');

    $subject->validate($request, makeTokenService(true));
})->throws(AuthException::class, 'CSRF token is missing.');

test('PATCH without token throws AuthException', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'PATCH');

    $subject->validate($request, makeTokenService(true));
})->throws(AuthException::class, 'CSRF token is missing.');

// --- Invalid token ---

test('POST with invalid token throws AuthException', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'POST');
    $request->headers->set('X-CSRF-Token', 'bad-token');

    $subject->validate($request, makeTokenService(false));
})->throws(AuthException::class, 'CSRF token is invalid or expired.');

// --- Valid token ---

test('POST with valid token passes', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'POST');
    $request->headers->set('X-CSRF-Token', 'valid-token-123');

    $tokenService = Mockery::mock();
    $tokenService->shouldReceive('validate')
        ->with('api', 'valid-token-123')
        ->once()
        ->andReturn(true);

    $subject->validate($request, $tokenService);
    expect(true)->toBeTrue();
});

test('PUT with valid token passes', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'PUT');
    $request->headers->set('X-CSRF-Token', 'valid-token-123');

    $subject->validate($request, makeTokenService(true));
    expect(true)->toBeTrue();
});

test('DELETE with valid token passes', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'DELETE');
    $request->headers->set('X-CSRF-Token', 'valid-token-123');

    $subject->validate($request, makeTokenService(true));
    expect(true)->toBeTrue();
});

// --- Context validation ---

test('missing token exception has correct context', function () {
    $subject = new CsrfValidationTestSubject();
    $request = Request::create('/api/test', 'POST');

    try {
        $subject->validate($request, makeTokenService(true));
    } catch (AuthException $e) {
        expect($e->getContext())->toBe(['header' => 'X-CSRF-Token'])
            ->and($e->getHttpStatusCode())->toBe(403);

        return;
    }

    $this->fail('Expected AuthException was not thrown.');
});
