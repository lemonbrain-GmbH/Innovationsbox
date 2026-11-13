<?php

declare(strict_types=1);

use Concrete\Core\User\User;
use Concrete\Core\User\UserInfo;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Auth\ConcreteCmsAuth;

afterEach(fn() => Mockery::close());

test('isAuthenticated() returns true when CMS user is registered', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->isAuthenticated())->toBeTrue();
});

test('isAuthenticated() returns false when CMS user is not registered', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(false);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->isAuthenticated())->toBeFalse();
});

test('getUserId() returns user ID when authenticated', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserID')->andReturn(42);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->getUserId())->toBe(42);
});

test('getUserId() returns null when not authenticated', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(false);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->getUserId())->toBeNull();
});

test('getCurrentUser() returns UserContext when authenticated', function () {
    $userInfo = Mockery::mock(UserInfo::class);
    $userInfo->shouldReceive('getUserEmail')->andReturn('john@example.com');
    $userInfo->shouldReceive('getUserName')->andReturn('John');

    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserID')->andReturn(42);
    $user->shouldReceive('getUserInfoObject')->andReturn($userInfo);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Initiator', 2 => 'Mitglied']);

    $auth = new ConcreteCmsAuth($user);
    $ctx = $auth->getCurrentUser();

    expect($ctx)->not->toBeNull()
        ->and($ctx->id)->toBe(42)
        ->and($ctx->email)->toBe('john@example.com')
        ->and($ctx->name)->toBe('John')
        ->and($ctx->roles)->toBe(['Initiator', 'Mitglied']);
});

test('getCurrentUser() returns null when not authenticated', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(false);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->getCurrentUser())->toBeNull();
});

test('hasRole() checks CMS user groups', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Moderator', 2 => 'Mitglied']);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->hasRole('moderator'))->toBeTrue()
        ->and($auth->hasRole('administrator'))->toBeFalse();
});

test('hasPermission() maps permissions to roles', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Initiator']);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->hasPermission('idea.submit'))->toBeTrue()
        ->and($auth->hasPermission('moderation.review'))->toBeFalse();
});

test('hasRole() recognises Experte group as expert role', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Experte']);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->hasRole('expert'))->toBeTrue();
});

test('hasPermission() grants expert.manage to moderator', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Moderator']);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->hasPermission('expert.manage'))->toBeTrue();
});

test('hasPermission() denies expert.manage to regular member', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Mitglied']);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->hasPermission('expert.manage'))->toBeFalse();
});
