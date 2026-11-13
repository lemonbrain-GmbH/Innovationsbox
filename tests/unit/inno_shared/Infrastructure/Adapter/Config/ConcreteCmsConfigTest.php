<?php

declare(strict_types=1);

use Concrete\Core\Config\Repository\Repository;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Config\ConcreteCmsConfig;
use Concrete\Package\InnoShared\Infrastructure\Exception\InfrastructureException;

afterEach(fn() => Mockery::close());

test('get() delegates to CMS config with prefix', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('get')
        ->with('innovationsbox.notification.admin_email', null)
        ->andReturn('admin@example.com');

    $config = new ConcreteCmsConfig($repo);
    expect($config->get('notification.admin_email'))->toBe('admin@example.com');
});

test('get() returns default when key missing', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('get')
        ->with('innovationsbox.missing.key', 'fallback')
        ->andReturn('fallback');

    $config = new ConcreteCmsConfig($repo);
    expect($config->get('missing.key', 'fallback'))->toBe('fallback');
});

test('has() checks prefixed key', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('has')
        ->with('innovationsbox.logging.level')
        ->andReturn(true);

    $config = new ConcreteCmsConfig($repo);
    expect($config->has('logging.level'))->toBeTrue();
});

test('getRequired() throws InfrastructureException when key missing', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('has')
        ->with('innovationsbox.notification.admin_email')
        ->andReturn(false);

    $config = new ConcreteCmsConfig($repo);
    $config->getRequired('notification.admin_email');
})->throws(InfrastructureException::class, "Required config key 'notification.admin_email' is missing.");

test('getRequired() returns value when key exists', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('has')
        ->with('innovationsbox.cache.default_ttl')
        ->andReturn(true);
    $repo->shouldReceive('get')
        ->with('innovationsbox.cache.default_ttl', null)
        ->andReturn(3600);

    $config = new ConcreteCmsConfig($repo);
    expect($config->getRequired('cache.default_ttl'))->toBe(3600);
});

test('set() delegates to CMS config with prefix', function () {
    $repo = Mockery::mock(Repository::class);
    $repo->shouldReceive('set')
        ->once()
        ->with('innovationsbox.logging.level', 'debug');

    $config = new ConcreteCmsConfig($repo);
    $config->set('logging.level', 'debug');
});
