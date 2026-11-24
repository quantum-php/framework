<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Adapters;

use Quantum\Cache\Exceptions\InvalidCacheKeyException;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Cache\Traits\CacheTrait;
use Psr\SimpleCache\CacheInterface;
use Quantum\Config\ConfigSection;
use Quantum\Storage\FileSystem;
use ReflectionException;
use DateInterval;
use Throwable;

/**
 * Class FileAdapter
 * @package Quantum\Cache
 */
class FileAdapter implements CacheInterface
{
    use CacheTrait;

    private FileSystem $fs;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public function __construct(ConfigSection $config)
    {
        $this->fs = FileSystemFactory::get();
        $this->ttl = $config->getInt('ttl');
        $this->prefix = $config->get('prefix');
        $this->cacheDir = $config->get('path');
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            $cacheItem = $this->fs->get($this->getPath($key));

            if (!$cacheItem) {
                return $default;
            }

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
        $path = $this->getPath($key);

        if (!$this->fs->exists($path)) {
            return false;
        }

        if (time() >= $this->fs->lastModified($path)) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $path = $this->getPath($key);
        $result = $this->fs->put($path, serialize($value)) !== false;

        if ($result && !touch($path, time() + $this->normalizeTtl($ttl))) {
            $this->fs->remove($path);
            return false;
        }

        return $result;
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
        $path = $this->getPath($key);

        if ($this->fs->exists($path)) {
            return $this->fs->remove($path);
        }

        return false;
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
        if (!$this->fs->isDirectory($this->cacheDir)) {
            return false;
        }

        $files = $this->fs->glob($this->cacheDir . DS . '*');

        if (!$files) {
            return false;
        }

        foreach ($files as $file) {
            $this->fs->remove($file);
        }

        return true;
    }

    /**
     * Gets the path for given cache key
     */
    private function getPath(string $key): string
    {
        return $this->cacheDir . DS . $this->keyHash($key);
    }
}
