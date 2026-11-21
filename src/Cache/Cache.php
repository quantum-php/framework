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
 * @method mixed get(string $key, mixed $default = null)
 * @method iterable<string, mixed> getMultiple(iterable<int|string> $keys, mixed $default = null)
 * @method bool has(string $key)
 * @method bool set(string $key, mixed $value, null|int|\DateInterval $ttl = null)
 * @method bool setMultiple(iterable<int|string, mixed> $values, null|int|\DateInterval $ttl = null)
 * @method bool delete(string $key)
 * @method bool deleteMultiple(iterable<int|string> $keys)
 * @method bool clear()
 */
class Cache
{
    private readonly CacheInterface $adapter;

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
