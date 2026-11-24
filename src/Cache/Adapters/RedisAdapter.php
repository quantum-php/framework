<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Adapters;

use Quantum\Cache\Exceptions\InvalidCacheKeyException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Cache\Traits\CacheTrait;
use Psr\SimpleCache\CacheInterface;
use Quantum\Config\ConfigSection;
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
     * @throws ConfigException
     */
    public function __construct(ConfigSection $config)
    {
        if (!$config->has('ttl')) {
            throw ConfigException::missingKey('ttl');
        }

        $this->ttl = $config->getInt('ttl');
        $this->prefix = $config->get('prefix');

        $this->redis = new Redis();
        $this->redis->connect($config->get('host'), $config->getInt('port'));
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
     * @param iterable<int|string> $keys
     * @return iterable<string, mixed>
     * @throws InvalidCacheKeyException
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $key = $this->normalizeKey($key);
            $result[$key] = $this->get($key, $default);
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
     * @param iterable<int|string, mixed> $values
     * @throws RedisException|InvalidCacheKeyException
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
     * @param iterable<int|string> $keys
     * @throws InvalidCacheKeyException
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
