<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Adapter\Monitoring\LogBasedMetrics;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

afterEach(fn() => Mockery::close());

test('increment() logs counter metric', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')
        ->once()
        ->with('metric.counter', ['name' => 'idea.submitted', 'value' => 1, 'status' => 'draft']);

    $metrics = new LogBasedMetrics($logger);
    $metrics->increment('idea.submitted', 1, ['status' => 'draft']);
});

test('gauge() logs gauge metric', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')
        ->once()
        ->with('metric.gauge', ['name' => 'queue.size', 'value' => 42.0]);

    $metrics = new LogBasedMetrics($logger);
    $metrics->gauge('queue.size', 42.0);
});

test('timing() logs timing metric', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')
        ->once()
        ->with('metric.timing', ['name' => 'handler.duration', 'ms' => 142.5]);

    $metrics = new LogBasedMetrics($logger);
    $metrics->timing('handler.duration', 142.5);
});

test('measure() times the callable and logs it', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')
        ->once()
        ->with('metric.timing', Mockery::on(function ($ctx) {
            return $ctx['name'] === 'test.operation' && is_float($ctx['ms']);
        }));

    $metrics = new LogBasedMetrics($logger);
    $result = $metrics->measure('test.operation', fn() => 'done');
    expect($result)->toBe('done');
});

test('event() logs an event', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')
        ->once()
        ->with('metric.event', ['name' => 'deploy', 'version' => '1.0']);

    $metrics = new LogBasedMetrics($logger);
    $metrics->event('deploy', ['version' => '1.0']);
});
