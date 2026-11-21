<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Adapters;

use Quantum\Cache\Traits\CacheTrait;
use Psr\SimpleCache\CacheInterface;
use RedisException;
use DateInterval;
use Throwable;
use Redis;

/**
 * Class RedisAdapter
 * @package Quantum\Cache
 */
class RedisAdapter implements CacheInterface
{
    use CacheTrait;

    private Redis $redis;

    /**
     * @throws RedisException
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        $this->ttl = $params['ttl'];
        $this->prefix = $params['prefix'];

        $this->redis = new Redis();
        $this->redis->connect($params['host'], (int) $params['port']);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            $cacheItem = $this->redis->get($this->keyHash($key));

            try {
                return unserialize($cacheItem);
            } catch (Throwable) {
                $this->delete($key);
                return $default;
            }
        }

        return $default;
    }

    /**
     * @inheritDoc
     * @param iterable<string> $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($this->normalizeKey($key), $default);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $cacheItem = $this->redis->get($this->keyHash($key));
        return (bool) $cacheItem;
    }

    /**
     * @inheritDoc
     * @throws RedisException
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $ttl = $this->normalizeTtl($ttl);
        $key = $this->keyHash($key);

        if ($ttl <= 0) {
            $this->redis->del($key);

            return true;
        }

        return (bool) $this->redis->setex(
            $key,
            $ttl,
            serialize($value)
        );
    }

    /**
     * @inheritDoc
     * @throws RedisException
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $results = [];

        foreach ($values as $key => $value) {
            $results[] = $this->set($this->normalizeKey($key), $value, $ttl);
        }

        return !in_array(false, $results, true);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return (bool) $this->redis->del($this->keyHash($key));
    }

    /**
     * @inheritDoc
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $results = [];

        foreach ($keys as $key) {
            $results[] = $this->delete($this->normalizeKey($key));
        }

        return !in_array(false, $results, true);
    }

    /**
     * @inheritDoc
     * @throws RedisException
     */
    public function clear(): bool
    {
        return (bool) $this->redis->flushdb();
    }
}
