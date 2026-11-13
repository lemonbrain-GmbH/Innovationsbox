<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Adapter\Logger\SensitiveDataProcessor;

test('scrubs sensitive keys from context', function () {
    $processor = new SensitiveDataProcessor();
    $record = [
        'context' => [
            'userId' => 42,
            'password' => 'secret123',
            'token' => 'abc-token',
            'authorization' => 'Bearer xyz',
            'cookie' => 'session=abc',
            'credit_card' => '4111-1111',
            'secret' => 'my-secret',
        ],
    ];

    $result = $processor($record);

    expect($result['context']['userId'])->toBe(42)
        ->and($result['context']['password'])->toBe('***REDACTED***')
        ->and($result['context']['token'])->toBe('***REDACTED***')
        ->and($result['context']['authorization'])->toBe('***REDACTED***')
        ->and($result['context']['cookie'])->toBe('***REDACTED***')
        ->and($result['context']['credit_card'])->toBe('***REDACTED***')
        ->and($result['context']['secret'])->toBe('***REDACTED***');
});

test('handles empty context', function () {
    $processor = new SensitiveDataProcessor();
    $record = ['context' => []];
    $result = $processor($record);
    expect($result['context'])->toBe([]);
});

test('handles nested arrays', function () {
    $processor = new SensitiveDataProcessor();
    $record = [
        'context' => [
            'user' => ['name' => 'John', 'password' => 'secret'],
        ],
    ];
    $result = $processor($record);
    expect($result['context']['user']['password'])->toBe('***REDACTED***')
        ->and($result['context']['user']['name'])->toBe('John');
});
