<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Cache;

use Concrete\Package\InnoShared\Infrastructure\Contract\Cache\CacheInterface;
use Stash\Interfaces\PoolInterface;

class ConcreteCmsCache implements CacheInterface
{
    private const PREFIX = 'innovationsbox/';
    private const TAG_PREFIX = 'innovationsbox/_tags/';

    public function __construct(private readonly PoolInterface $pool) {}

    public function get($key, $default = null)
    {
        $item = $this->pool->getItem($this->normalizeKey($key));
        if ($item->isMiss()) {
            return $default;
        }

        return $item->get();
    }

    public function set($key, $value, $ttl = null)
    {
        $item = $this->pool->getItem($this->normalizeKey($key));
        $item->set($value);
        if ($ttl !== null) {
            $item->setTTL($ttl);
        }
        $item->save();

        return true;
    }

    public function delete($key)
    {
        return $this->pool->deleteItem($this->normalizeKey($key));
    }

    public function clear()
    {
        return $this->pool->clear();
    }

    /**
     * @param iterable<string> $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key)
    {
        $item = $this->pool->getItem($this->normalizeKey($key));

        return !$item->isMiss();
    }

    public function getOrSet(string $key, int $ttl, callable $callback): mixed
    {
        $item = $this->pool->getItem($this->normalizeKey($key));
        if (!$item->isMiss()) {
            return $item->get();
        }

        $value = $callback();
        $item->set($value);
        $item->setTTL($ttl);
        $item->save();

        return $value;
    }

    /**
     * @param list<string> $tags
     */
    public function setTagged(string $key, mixed $value, int $ttl, array $tags): void
    {
        $this->set($key, $value, $ttl);

        foreach ($tags as $tag) {
            $tagKey = self::TAG_PREFIX . $tag;
            $tagItem = $this->pool->getItem($tagKey);
            $keys = $tagItem->isMiss() ? [] : ($tagItem->get() ?? []);
            $keys[] = $key;
            $keys = array_unique($keys);
            $tagItem->set($keys);
            $tagItem->save();
        }
    }

    public function flushTag(string $tag): void
    {
        $tagKey = self::TAG_PREFIX . $tag;
        $tagItem = $this->pool->getItem($tagKey);
        if ($tagItem->isMiss()) {
            return;
        }

        $keys = $tagItem->get() ?? [];
        foreach ($keys as $key) {
            $this->delete($key);
        }

        $this->pool->deleteItem($tagKey);
    }

    private function normalizeKey(string $key): string
    {
        return self::PREFIX . str_replace('.', '/', $key);
    }
}
