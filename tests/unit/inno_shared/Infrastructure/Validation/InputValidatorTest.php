<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;
use Concrete\Package\InnoShared\Infrastructure\Validation\InputValidator;

test('requireString returns trimmed value', function () {
    $v = new InputValidator(['title' => '  Hello  ']);
    expect($v->requireString('title', 1, 255))->toBe('Hello');
});

test('requireString throws on empty', function () {
    $v = new InputValidator(['title' => '  ']);
    $v->requireString('title', 1, 255);
})->throws(ValidationException::class);

test('requireString throws on too long', function () {
    $v = new InputValidator(['title' => str_repeat('a', 256)]);
    $v->requireString('title', 1, 255);
})->throws(ValidationException::class);

test('requireString throws on missing key', function () {
    $v = new InputValidator([]);
    $v->requireString('title', 1, 255);
})->throws(ValidationException::class);

test('optionalString returns null when missing', function () {
    $v = new InputValidator([]);
    expect($v->optionalString('notes', 1000))->toBeNull();
});

test('optionalString returns trimmed value', function () {
    $v = new InputValidator(['notes' => ' hello ']);
    expect($v->optionalString('notes', 1000))->toBe('hello');
});

test('requireEnum throws on invalid', function () {
    $v = new InputValidator(['status' => 'invalid']);
    $v->requireEnum('status', ['active', 'archived']);
})->throws(ValidationException::class);

test('requireEnum returns valid value', function () {
    $v = new InputValidator(['status' => 'active']);
    expect($v->requireEnum('status', ['active', 'archived']))->toBe('active');
});
