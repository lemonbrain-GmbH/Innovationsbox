<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Adapter\Cache\NullCache;

test('get() always returns default', function () {
    $cache = new NullCache();
    expect($cache->get('key', 'default'))->toBe('default');
});

test('set() does not store anything', function () {
    $cache = new NullCache();
    $cache->set('key', 'value', 3600);
    expect($cache->get('key'))->toBeNull();
});

test('has() always returns false', function () {
    $cache = new NullCache();
    expect($cache->has('key'))->toBeFalse();
});

test('getOrSet() always calls the callback', function () {
    $cache = new NullCache();
    $called = 0;
    $result = $cache->getOrSet('key', 3600, function () use (&$called) {
        $called++;
        return 'computed';
    });
    expect($result)->toBe('computed')
        ->and($called)->toBe(1);
});

test('delete() returns true', function () {
    $cache = new NullCache();
    expect($cache->delete('key'))->toBeTrue();
});

test('clear() returns true', function () {
    $cache = new NullCache();
    expect($cache->clear())->toBeTrue();
});

test('flushTag() does nothing without error', function () {
    $cache = new NullCache();
    $cache->flushTag('some_tag');
    expect(true)->toBeTrue();
});
