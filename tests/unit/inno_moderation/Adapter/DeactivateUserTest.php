<?php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Adapter\In\Http\UserApiController;
use Concrete\Package\InnoShared\Domain\Event\UserAccountDeactivated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

/**
 * Build a UserApiController with mocked dependencies and a fake CMS app container
 * that validates any CSRF token as valid (bypassing real token service in unit tests).
 */
function makeController(
    AuthInterface $auth,
    ExceptionHandlerInterface $handler,
    EventDispatcherInterface $dispatcher,
): UserApiController {
    $tokenService = Mockery::mock();
    $tokenService->shouldReceive('validate')->andReturn(true);

    $app = Mockery::mock(\Concrete\Core\Application\Application::class);
    $app->shouldReceive('make')->with('token')->andReturn($tokenService);

    $controller = new UserApiController($auth, $handler, $dispatcher);
    $controller->setApplication($app);

    return $controller;
}

/**
 * Build a DELETE request with a JSON body and a valid CSRF token header.
 */
function makeDeleteRequest(string $uri, array $body): Request
{
    $request = Request::create($uri, 'DELETE', [], [], [], [], json_encode($body));
    $request->headers->set('X-CSRF-Token', 'valid-token');

    return $request;
}

// ─── deactivateUser ──────────────────────────────────────────────────────────

test('deactivateUser returns 400 when reason is missing', function () {
    $auth = Mockery::mock(AuthInterface::class);
    $handler = Mockery::mock(ExceptionHandlerInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $auth->shouldReceive('hasPermission')->with('moderation.review')->andReturn(true);

    $controller = makeController($auth, $handler, $dispatcher);
    $response = $controller->deactivateUser(makeDeleteRequest('/api/moderation/user/5', []), '5');

    expect($response->getStatusCode())->toBe(400)
        ->and(json_decode($response->getContent(), true))->toBe(['error' => 'Reason is required']);
});

test('deactivateUser returns 400 when reason is whitespace only', function () {
    $auth = Mockery::mock(AuthInterface::class);
    $handler = Mockery::mock(ExceptionHandlerInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $auth->shouldReceive('hasPermission')->with('moderation.review')->andReturn(true);

    $controller = makeController($auth, $handler, $dispatcher);
    $response = $controller->deactivateUser(makeDeleteRequest('/api/moderation/user/5', ['reason' => '   ']), '5');

    expect($response->getStatusCode())->toBe(400);
});

test('deactivateUser returns 403 when moderator lacks permission', function () {
    $auth = Mockery::mock(AuthInterface::class);
    $handler = Mockery::mock(ExceptionHandlerInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $auth->shouldReceive('hasPermission')->with('moderation.review')->andReturn(false);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);

    $handler->shouldReceive('toJsonResponse')
        ->once()
        ->with(Mockery::type(AuthException::class))
        ->andReturn(new JsonResponse(['error' => 'Forbidden'], 403));

    $controller = makeController($auth, $handler, $dispatcher);
    $response = $controller->deactivateUser(makeDeleteRequest('/api/moderation/user/5', ['reason' => 'Spam']), '5');

    expect($response->getStatusCode())->toBe(403);
});

// ─── deactivateOwnAccount ────────────────────────────────────────────────────

test('deactivateOwnAccount returns 400 when password is missing', function () {
    $auth = Mockery::mock(AuthInterface::class);
    $handler = Mockery::mock(ExceptionHandlerInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $auth->shouldReceive('isAuthenticated')->andReturn(true);

    $controller = makeController($auth, $handler, $dispatcher);
    $response = $controller->deactivateOwnAccount(makeDeleteRequest('/api/user/account', []));

    expect($response->getStatusCode())->toBe(400)
        ->and(json_decode($response->getContent(), true))->toBe(['error' => 'Password is required']);
});

test('deactivateOwnAccount returns 400 when password is empty string', function () {
    $auth = Mockery::mock(AuthInterface::class);
    $handler = Mockery::mock(ExceptionHandlerInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $auth->shouldReceive('isAuthenticated')->andReturn(true);

    $controller = makeController($auth, $handler, $dispatcher);
    $response = $controller->deactivateOwnAccount(makeDeleteRequest('/api/user/account', ['password' => '']));

    expect($response->getStatusCode())->toBe(400);
});

test('deactivateOwnAccount returns 401 when not authenticated', function () {
    $auth = Mockery::mock(AuthInterface::class);
    $handler = Mockery::mock(ExceptionHandlerInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $auth->shouldReceive('isAuthenticated')->andReturn(false);

    $handler->shouldReceive('toJsonResponse')
        ->once()
        ->with(Mockery::type(AuthException::class))
        ->andReturn(new JsonResponse(['error' => 'Not authenticated'], 401));

    $controller = makeController($auth, $handler, $dispatcher);
    $response = $controller->deactivateOwnAccount(makeDeleteRequest('/api/user/account', ['password' => 'secret']));

    expect($response->getStatusCode())->toBe(401);
});

// ─── UserAccountDeactivated event ────────────────────────────────────────────

test('UserAccountDeactivated isSelfDeletion returns true when userId equals deactivatedByUserId', function () {
    $event = new UserAccountDeactivated('42', 'user@example.com', 'User Name', '', '42');

    expect($event->isSelfDeletion())->toBeTrue();
});

test('UserAccountDeactivated isSelfDeletion returns false when deactivated by moderator', function () {
    $event = new UserAccountDeactivated('42', 'user@example.com', 'User Name', 'Policy violation', '99');

    expect($event->isSelfDeletion())->toBeFalse()
        ->and($event->getReason())->toBe('Policy violation')
        ->and($event->getDeactivatedByUserId())->toBe('99');
});

test('UserAccountDeactivated exposes all fields correctly', function () {
    $event = new UserAccountDeactivated('7', 'test@test.com', 'Test User', 'Abuse', '3');

    expect($event->getUserId())->toBe('7')
        ->and($event->getUserEmail())->toBe('test@test.com')
        ->and($event->getUserName())->toBe('Test User')
        ->and($event->getReason())->toBe('Abuse')
        ->and($event->getDeactivatedByUserId())->toBe('3');
});
