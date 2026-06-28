<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Adapters;

use Quantum\Cache\Exceptions\CacheException;
use Quantum\Cache\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;
use Quantum\Cache\Traits\CacheTrait;
use Psr\SimpleCache\CacheInterface;
use InvalidArgumentException;
use Memcached;
use Exception;

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
        $this->memcached->addServer($params['host'], $params['port']);

        if (!$this->memcached->getStats()) {
            throw CacheException::cantConnect('Memcached server');
        }
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            $cacheItem = $this->memcached->get($this->keyHash($key));

            try {
                return unserialize($cacheItem);
            } catch (Exception) {
                $this->delete($key);
                return $default;
            }
        }

        return $default;
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     * @param iterable<string> $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple($keys, $default = null)
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException(
                _message(ExceptionMessages::ARGUMENT_NOT_ITERABLE, '$keys'),
                E_WARNING
            );
        }

        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function has($key): bool
    {
        $cacheItem = $this->memcached->get($this->keyHash($key));
        return (bool) $cacheItem;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->memcached->set($this->keyHash($key), serialize($value), $this->normalizeTtl($ttl));
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     * @param iterable<string, mixed> $values
     */
    public function setMultiple($values, $ttl = null): bool
    {
        if (!is_array($values)) {
            throw new InvalidArgumentException(
                _message(ExceptionMessages::ARGUMENT_NOT_ITERABLE, '$values'),
                E_WARNING
            );
        }

        $results = [];

        foreach ($values as $key => $value) {
            $results[] = $this->set($key, $value, $ttl);
        }

        return !in_array(false, $results, true);
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        return $this->memcached->delete($this->keyHash($key));
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     * @param iterable<string> $keys
     */
    public function deleteMultiple($keys): bool
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException(
                _message(ExceptionMessages::ARGUMENT_NOT_ITERABLE, '$keys'),
                E_WARNING
            );
        }

        $results = [];

        foreach ($keys as $key) {
            $results[] = $this->delete($key);
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
