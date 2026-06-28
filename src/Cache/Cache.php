<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache;

use Quantum\Cache\Exceptions\CacheException;
use Quantum\App\Exceptions\BaseException;
use Psr\SimpleCache\CacheInterface;

/**
 * Class Cache
 * @package Quantum\Cache
 * @method mixed get($key, $default = null)
 * @method iterable<string, mixed> getMultiple($keys, $default = null)
 * @method has($key): bool
 * @method bool set($key, $value, $ttl = null)
 * @method bool setMultiple($values, $ttl = null)
 * @method bool delete($key)
 * @method bool deleteMultiple($keys)
 * @method bool clear()
 */
class Cache
{
    private CacheInterface $adapter;

    /**
     * Cache constructor
     */
    public function __construct(CacheInterface $cacheAdapter)
    {
        $this->adapter = $cacheAdapter;
    }

    /**
     * Gets the current adapter
     */
    public function getAdapter(): CacheInterface
    {
        return $this->adapter;
    }

    /**
     * @param array<mixed>|null $arguments
     * @return mixed
     * @throws BaseException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists($this->adapter, $method)) {
            throw CacheException::methodNotSupported($method, $this->adapter::class);
        }

        return $this->adapter->$method(...$arguments);
    }
}
