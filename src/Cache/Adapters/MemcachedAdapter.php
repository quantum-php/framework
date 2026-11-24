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
use Quantum\Cache\Exceptions\CacheException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Cache\Traits\CacheTrait;
use Psr\SimpleCache\CacheInterface;
use Quantum\Config\ConfigSection;
use DateInterval;
use Memcached;
use Throwable;

/**
 * Class MemcachedAdapter
 * @package Quantum\Cache
 */
class MemcachedAdapter implements CacheInterface
{
    use CacheTrait;

    /**
     * Longest expiration Memcached reads as seconds from now; a larger value is read as a Unix timestamp
     */
    private const MAX_RELATIVE_TTL = 2592000;

    private Memcached $memcached;

    /**
     * @throws BaseException
     * @throws ConfigException
     */
    public function __construct(ConfigSection $config)
    {
        $this->ttl = $config->getInt('ttl');
        $this->prefix = $config->get('prefix');

        $this->memcached = new Memcached();
        $this->memcached->addServer($config->get('host'), $config->getInt('port'));

        if (!$this->memcached->getStats()) {
            throw CacheException::cantConnect('Memcached server');
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            $cacheItem = $this->memcached->get($this->keyHash($key));

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
        $cacheItem = $this->memcached->get($this->keyHash($key));
        return (bool) $cacheItem;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $ttl = $this->normalizeTtl($ttl);
        $key = $this->keyHash($key);

        if ($ttl <= 0) {
            return $this->memcached->delete($key)
                || $this->memcached->getResultCode() === Memcached::RES_NOTFOUND;
        }

        if ($ttl > self::MAX_RELATIVE_TTL) {
            $ttl = time() + $ttl;
        }

        return $this->memcached->set($key, serialize($value), $ttl);
    }

    /**
     * @inheritDoc
     * @param iterable<int|string, mixed> $values
     * @throws InvalidCacheKeyException
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
        return $this->memcached->delete($this->keyHash($key));
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
     */
    public function clear(): bool
    {
        return $this->memcached->flush();
    }

}
