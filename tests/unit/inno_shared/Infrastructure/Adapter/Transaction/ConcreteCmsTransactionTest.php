<?php

declare(strict_types=1);

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoShared\Infrastructure\Adapter\Transaction\ConcreteCmsTransaction;

afterEach(fn() => Mockery::close());

test('transactional() commits on success', function () {
    $conn = Mockery::mock(Connection::class);
    $conn->shouldReceive('beginTransaction')->once();
    $conn->shouldReceive('commit')->once();
    $conn->shouldNotReceive('rollBack');

    $tx = new ConcreteCmsTransaction($conn);
    $result = $tx->transactional(fn() => 'success');

    expect($result)->toBe('success');
});

test('transactional() rolls back and rethrows on exception', function () {
    $conn = Mockery::mock(Connection::class);
    $conn->shouldReceive('beginTransaction')->once();
    $conn->shouldReceive('rollBack')->once();
    $conn->shouldNotReceive('commit');

    $tx = new ConcreteCmsTransaction($conn);

    expect(fn() => $tx->transactional(function () {
        throw new \RuntimeException('DB error');
    }))->toThrow(\RuntimeException::class, 'DB error');
});

test('begin() and commit() work directly', function () {
    $conn = Mockery::mock(Connection::class);
    $conn->shouldReceive('beginTransaction')->once();
    $conn->shouldReceive('commit')->once();

    $tx = new ConcreteCmsTransaction($conn);
    $tx->begin();
    $tx->commit();
});

test('begin() and rollback() work directly', function () {
    $conn = Mockery::mock(Connection::class);
    $conn->shouldReceive('beginTransaction')->once();
    $conn->shouldReceive('rollBack')->once();

    $tx = new ConcreteCmsTransaction($conn);
    $tx->begin();
    $tx->rollback();
});

test('getDepth() returns transaction nesting level', function () {
    $conn = Mockery::mock(Connection::class);
    $conn->shouldReceive('getTransactionNestingLevel')->andReturn(2);

    $tx = new ConcreteCmsTransaction($conn);
    expect($tx->getDepth())->toBe(2);
});
