<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Cache;

use Concrete\Package\InnoShared\Infrastructure\Contract\Cache\CacheInterface;

class NullCache implements CacheInterface
{
    public function get($key, $default = null)
    {
        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        return true;
    }

    public function delete($key)
    {
        return true;
    }

    public function clear()
    {
        return true;
    }

    /**
     * @param iterable<string> $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $default;
        }
        return $result;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple($values, $ttl = null)
    {
        return true;
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple($keys)
    {
        return true;
    }

    public function has($key)
    {
        return false;
    }

    public function getOrSet(string $key, int $ttl, callable $callback): mixed
    {
        return $callback();
    }

    /**
     * @param list<string> $tags
     */
    public function setTagged(string $key, mixed $value, int $ttl, array $tags): void
    {
        // No-op
    }

    public function flushTag(string $tag): void
    {
        // No-op
    }
}
