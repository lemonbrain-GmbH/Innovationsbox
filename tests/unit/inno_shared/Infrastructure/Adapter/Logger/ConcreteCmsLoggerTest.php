<?php

declare(strict_types=1);

use Concrete\Core\Logging\Logger;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Logger\ConcreteCmsLogger;

afterEach(fn() => Mockery::close());

test('info() delegates to CMS logger', function () {
    $cmsLogger = Mockery::mock(Logger::class);
    $cmsLogger->shouldReceive('info')
        ->once()
        ->with('Idea submitted', ['ideaId' => '123']);

    $logger = new ConcreteCmsLogger($cmsLogger);
    $logger->info('Idea submitted', ['ideaId' => '123']);
});

test('error() delegates to CMS logger', function () {
    $cmsLogger = Mockery::mock(Logger::class);
    $cmsLogger->shouldReceive('error')
        ->once()
        ->with('DB failed', ['table' => 'ideas']);

    $logger = new ConcreteCmsLogger($cmsLogger);
    $logger->error('DB failed', ['table' => 'ideas']);
});

test('withContext() merges default context into all subsequent calls', function () {
    $cmsLogger = Mockery::mock(Logger::class);
    $cmsLogger->shouldReceive('info')
        ->once()
        ->with('Test message', ['requestId' => 'req-1', 'extra' => 'data']);

    $logger = new ConcreteCmsLogger($cmsLogger);
    $contextLogger = $logger->withContext(['requestId' => 'req-1']);
    $contextLogger->info('Test message', ['extra' => 'data']);
});

test('withContext() does not mutate original logger', function () {
    $cmsLogger = Mockery::mock(Logger::class);
    $cmsLogger->shouldReceive('info')
        ->once()
        ->with('Original', []);

    $logger = new ConcreteCmsLogger($cmsLogger);
    $logger->withContext(['requestId' => 'req-1']);
    $logger->info('Original');
});
