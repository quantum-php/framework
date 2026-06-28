<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Factories;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Cache\Exceptions\CacheException;
use Quantum\Cache\Adapters\MemcachedAdapter;
use Quantum\Cache\Adapters\DatabaseAdapter;
use Quantum\App\Exceptions\BaseException;
use Quantum\Cache\Adapters\RedisAdapter;
use Quantum\Cache\Adapters\FileAdapter;
use Quantum\Di\Exceptions\DiException;
use Psr\SimpleCache\CacheInterface;
use Quantum\Cache\Enums\CacheType;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\Cache\Cache;
use Quantum\Di\Di;

/**
 * Class CacheFactory
 * @package Quantum\Cache
 */
class CacheFactory
{
    public const ADAPTERS = [
        CacheType::FILE => FileAdapter::class,
        CacheType::DATABASE => DatabaseAdapter::class,
        CacheType::MEMCACHED => MemcachedAdapter::class,
        CacheType::REDIS => RedisAdapter::class,
    ];

    /**
     * @var array<string, Cache>
     */
    private array $instances = [];

    /**
     * @throws ConfigException|BaseException|DiException|ReflectionException
     */
    public static function get(?string $adapter = null): Cache
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve($adapter);
    }

    /**
     * @throws ConfigException|BaseException|DiException|ReflectionException
     */
    public function resolve(?string $adapter = null): Cache
    {
        if (!config()->has('cache')) {
            config()->import(new Setup('config', 'cache'));
        }

        $adapter ??= config()->get('cache.default');

        $adapterClass = $this->getAdapterClass($adapter);

        if (!isset($this->instances[$adapter])) {
            $this->instances[$adapter] = $this->createInstance($adapterClass, $adapter);
        }

        return $this->instances[$adapter];
    }

    /**
     * @throws CacheException|BaseException
     */
    private function createInstance(string $adapterClass, string $adapter): Cache
    {
        $cacheAdapter = new $adapterClass(config()->get('cache.' . $adapter));

        if (!$cacheAdapter instanceof CacheInterface) {
            throw CacheException::adapterNotSupported($adapter);
        }

        return new Cache($cacheAdapter);
    }

    /**
     * @throws BaseException
     */
    private function getAdapterClass(string $adapter): string
    {
        if (!array_key_exists($adapter, self::ADAPTERS)) {
            throw CacheException::adapterNotSupported($adapter);
        }

        return self::ADAPTERS[$adapter];
    }
}
