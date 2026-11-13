<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Cache;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

interface CacheInterface extends PsrCacheInterface
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function getOrSet(string $key, int $ttl, callable $callback): mixed;

    /**
     * @param list<string> $tags
     */
    public function setTagged(string $key, mixed $value, int $ttl, array $tags): void;

    public function flushTag(string $tag): void;
}
