<?php

declare(strict_types=1);

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Monitoring\ConcreteCmsHealthCheck;
use Concrete\Package\InnoShared\Infrastructure\Contract\Monitoring\HealthStatus;

afterEach(fn() => Mockery::close());

test('check() returns healthy when all services are up', function () {
    $db = Mockery::mock(Connection::class);
    $db->shouldReceive('fetchOne')->with('SELECT 1')->andReturn('1');

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('concrete.version')->andReturn('9.4.0');

    $check = new ConcreteCmsHealthCheck($db, $config);
    $result = $check->check();

    expect($result['database'])->toBe(HealthStatus::Healthy)
        ->and($result['cms'])->toBe(HealthStatus::Healthy);
});

test('check() returns unhealthy database when query fails', function () {
    $db = Mockery::mock(Connection::class);
    $db->shouldReceive('fetchOne')->andThrow(new \Exception('Connection refused'));

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('concrete.version')->andReturn('9.4.0');

    $check = new ConcreteCmsHealthCheck($db, $config);
    $result = $check->check();

    expect($result['database'])->toBe(HealthStatus::Unhealthy);
});

test('check() returns unhealthy CMS when config fails', function () {
    $db = Mockery::mock(Connection::class);
    $db->shouldReceive('fetchOne')->with('SELECT 1')->andReturn('1');

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('concrete.version')->andReturn(null);

    $check = new ConcreteCmsHealthCheck($db, $config);
    $result = $check->check();

    expect($result['cms'])->toBe(HealthStatus::Unhealthy);
});
