<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Adapter\User\ConcreteCmsUserResolver;

afterEach(fn() => Mockery::close());

test('resolveEmail returns email for valid user', function () {
    $userInfo = Mockery::mock(\Concrete\Core\User\UserInfo::class);
    $userInfo->shouldReceive('getUserEmail')->andReturn('test@example.com');
    $resolver = new ConcreteCmsUserResolver(fn(int $id) => $id === 42 ? $userInfo : null);
    expect($resolver->resolveEmail('42'))->toBe('test@example.com');
});

test('resolveEmail returns null for unknown user', function () {
    $resolver = new ConcreteCmsUserResolver(fn(int $id) => null);
    expect($resolver->resolveEmail('999'))->toBeNull();
});

test('resolveName returns display_name when set', function () {
    $userInfo = Mockery::mock(\Concrete\Core\User\UserInfo::class);
    $userInfo->shouldReceive('getAttribute')->with('display_name')->andReturn('Marco Meier');
    $resolver = new ConcreteCmsUserResolver(fn(int $id) => $userInfo);
    expect($resolver->resolveName('42'))->toBe('Marco Meier');
});

test('resolveName falls back to username', function () {
    $userInfo = Mockery::mock(\Concrete\Core\User\UserInfo::class);
    $userInfo->shouldReceive('getAttribute')->with('display_name')->andReturn(null);
    $userInfo->shouldReceive('getUserName')->andReturn('marco');
    $resolver = new ConcreteCmsUserResolver(fn(int $id) => $userInfo);
    expect($resolver->resolveName('42'))->toBe('marco');
});

test('resolveName returns fallback for unknown user', function () {
    $resolver = new ConcreteCmsUserResolver(fn(int $id) => null);
    expect($resolver->resolveName('999'))->toBe('Benutzer 999');
});
