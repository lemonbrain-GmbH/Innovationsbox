<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Adapter\ErrorHandling\ConcreteCmsExceptionHandler;
use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\NotFoundException;
use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

afterEach(fn() => Mockery::close());

test('toJsonResponse() maps BaseException to correct HTTP status and user message', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('warning')->once();
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('get')->with('app.debug', false)->andReturn(false);

    $handler = new ConcreteCmsExceptionHandler($logger, $config);
    $e = new NotFoundException('Idea xyz not found', ['ideaId' => 'xyz']);

    $response = $handler->toJsonResponse($e);
    $body = json_decode($response->getContent(), true);

    expect($response->getStatusCode())->toBe(404)
        ->and($body['error']['message'])->toBe('The requested resource was not found.')
        ->and($body['error']['code'])->toBe(404)
        ->and($body)->not->toHaveKey('error.debug');
});

test('toJsonResponse() includes debug info when app.debug is true', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('warning')->once();
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('get')->with('app.debug', false)->andReturn(true);

    $handler = new ConcreteCmsExceptionHandler($logger, $config);
    $e = new ValidationException('Title is empty', ['field' => 'title']);

    $response = $handler->toJsonResponse($e);
    $body = json_decode($response->getContent(), true);

    expect($response->getStatusCode())->toBe(400)
        ->and($body['error']['debug']['exception'])->toBe('ValidationException')
        ->and($body['error']['debug']['detail'])->toBe('Title is empty');
});

test('toJsonResponse() handles unexpected exceptions as 500', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('critical')->once();
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('get')->with('app.debug', false)->andReturn(false);

    $handler = new ConcreteCmsExceptionHandler($logger, $config);
    $e = new \RuntimeException('Something broke');

    $response = $handler->toJsonResponse($e);
    $body = json_decode($response->getContent(), true);

    expect($response->getStatusCode())->toBe(500)
        ->and($body['error']['message'])->toBe('An internal error occurred. Please try again later.')
        ->and($body['error']['code'])->toBe(500);
});

test('logs BaseException at warning level', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('warning')
        ->once()
        ->with('Idea xyz not found', Mockery::hasKey('ideaId'));
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('get')->andReturn(false);

    $handler = new ConcreteCmsExceptionHandler($logger, $config);
    $handler->toJsonResponse(new NotFoundException('Idea xyz not found', ['ideaId' => 'xyz']));
});

test('logs unexpected exception at critical level', function () {
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('critical')
        ->once()
        ->with('Something broke', Mockery::hasKey('exception'));
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('get')->andReturn(false);

    $handler = new ConcreteCmsExceptionHandler($logger, $config);
    $handler->toJsonResponse(new \RuntimeException('Something broke'));
});
