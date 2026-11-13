<?php

declare(strict_types=1);

use Concrete\Core\User\UserInfo;
use Concrete\Package\InnoFrontend\Service\ProfileCompletionChecker;

afterEach(fn() => Mockery::close());

test('returns false for /account path', function () {
    $ui = Mockery::mock(UserInfo::class);
    $checker = new ProfileCompletionChecker();
    expect($checker->needsCompletion($ui, '/account/edit_profile'))->toBeFalse();
});

test('returns false for /login path', function () {
    $ui = Mockery::mock(UserInfo::class);
    $checker = new ProfileCompletionChecker();
    expect($checker->needsCompletion($ui, '/login'))->toBeFalse();
});

test('returns false for /register path', function () {
    $ui = Mockery::mock(UserInfo::class);
    $checker = new ProfileCompletionChecker();
    expect($checker->needsCompletion($ui, '/register'))->toBeFalse();
});

test('returns false for /dashboard path', function () {
    $ui = Mockery::mock(UserInfo::class);
    $checker = new ProfileCompletionChecker();
    expect($checker->needsCompletion($ui, '/dashboard/sitemap'))->toBeFalse();
});
