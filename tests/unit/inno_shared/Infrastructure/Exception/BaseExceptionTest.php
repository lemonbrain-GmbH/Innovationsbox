<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Concrete\Package\InnoShared\Infrastructure\Exception\InfrastructureException;
use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;
use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

test('ValidationException has status 400 and safe user message', function () {
    $e = new ValidationException('Title is empty', ['field' => 'title']);
    expect($e->getHttpStatusCode())->toBe(400)
        ->and($e->getUserMessage())->toBe('The provided data is invalid.')
        ->and($e->getContext())->toBe(['field' => 'title'])
        ->and($e->getMessage())->toBe('Title is empty');
});

test('NotFoundException has status 404', function () {
    $e = new NotFoundException('Idea xyz not found', ['id' => 'xyz']);
    expect($e->getHttpStatusCode())->toBe(404)
        ->and($e->getUserMessage())->toBe('The requested resource was not found.')
        ->and($e->getContext())->toBe(['id' => 'xyz']);
});

test('AuthException defaults to 403 when authenticated', function () {
    $e = new AuthException('Not allowed', ['permission' => 'idea.submit']);
    expect($e->getHttpStatusCode())->toBe(403)
        ->and($e->getUserMessage())->toBe('You do not have permission to perform this action.');
});

test('AuthException returns 401 when not authenticated', function () {
    $e = new AuthException('Login required', authenticated: false);
    expect($e->getHttpStatusCode())->toBe(401);
});

test('InfrastructureException has status 500', function () {
    $e = new InfrastructureException('DB connection failed');
    expect($e->getHttpStatusCode())->toBe(500)
        ->and($e->getUserMessage())->toBe('An internal error occurred. Please try again later.');
});

test('exceptions carry previous throwable', function () {
    $prev = new \RuntimeException('root cause');
    $e = new InfrastructureException('Wrapper', previous: $prev);
    expect($e->getPrevious())->toBe($prev);
});
