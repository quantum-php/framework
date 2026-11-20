<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Adapters;

use Quantum\Cache\Exceptions\CacheException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Cache\Traits\CacheTrait;
use Psr\SimpleCache\CacheInterface;
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

    private Memcached $memcached;

    /**
     * @throws BaseException
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        $this->ttl = $params['ttl'];
        $this->prefix = $params['prefix'];

        $this->memcached = new Memcached();
        $this->memcached->addServer($params['host'], (int) $params['port']);

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
     * @param iterable<string> $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get((string) $key, $default);
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
        return $this->memcached->set($this->keyHash($key), serialize($value), $this->normalizeTtl($ttl));
    }

    /**
     * @inheritDoc
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $results = [];

        foreach ($values as $key => $value) {
            $results[] = $this->set((string) $key, $value, $ttl);
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
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $results = [];

        foreach ($keys as $key) {
            $results[] = $this->delete((string) $key);
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
