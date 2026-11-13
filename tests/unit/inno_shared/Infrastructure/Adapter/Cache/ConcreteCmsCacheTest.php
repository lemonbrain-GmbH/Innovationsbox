<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Adapter\Cache\ConcreteCmsCache;
use Stash\Interfaces\ItemInterface;
use Stash\Interfaces\PoolInterface;

afterEach(fn() => Mockery::close());

test('get() returns cached value', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('isMiss')->andReturn(false);
    $item->shouldReceive('get')->andReturn('cached_value');

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/test/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    expect($cache->get('test.key'))->toBe('cached_value');
});

test('get() returns default on cache miss', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('isMiss')->andReturn(true);

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/missing/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    expect($cache->get('missing.key', 'fallback'))->toBe('fallback');
});

test('set() stores value in pool', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('set')->once()->with('my_value');
    $item->shouldReceive('setTTL')->once()->with(3600);
    $item->shouldReceive('save')->once();

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/my/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    expect($cache->set('my.key', 'my_value', 3600))->toBeTrue();
});

test('getOrSet() returns cached value on hit', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('isMiss')->andReturn(false);
    $item->shouldReceive('get')->andReturn('cached');

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/my/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    $called = false;
    $result = $cache->getOrSet('my.key', 3600, function () use (&$called) {
        $called = true;
        return 'computed';
    });
    expect($result)->toBe('cached')
        ->and($called)->toBeFalse();
});

test('getOrSet() calls callback and caches on miss', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('isMiss')->andReturn(true);
    $item->shouldReceive('set')->once()->with('computed');
    $item->shouldReceive('setTTL')->once()->with(600);
    $item->shouldReceive('save')->once();

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/my/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    $result = $cache->getOrSet('my.key', 600, fn() => 'computed');
    expect($result)->toBe('computed');
});

test('has() checks pool', function () {
    $item = Mockery::mock(ItemInterface::class);
    $item->shouldReceive('isMiss')->andReturn(false);

    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('getItem')->with('innovationsbox/my/key')->andReturn($item);

    $cache = new ConcreteCmsCache($pool);
    expect($cache->has('my.key'))->toBeTrue();
});

test('delete() clears item from pool', function () {
    $pool = Mockery::mock(PoolInterface::class);
    $pool->shouldReceive('deleteItem')->once()->with('innovationsbox/my/key')->andReturn(true);

    $cache = new ConcreteCmsCache($pool);
    expect($cache->delete('my.key'))->toBeTrue();
});
