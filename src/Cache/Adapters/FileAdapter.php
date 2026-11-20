<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Adapters;

use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Psr\SimpleCache\CacheInterface;
use Quantum\Cache\Traits\CacheTrait;
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
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        $this->fs = FileSystemFactory::get();
        $this->ttl = $params['ttl'];
        $this->prefix = $params['prefix'];
        $this->cacheDir = $params['path'];
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
     * @param iterable<string> $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get(is_int($key) ? (string) $key : $key, $default);
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
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $results = [];

        foreach ($values as $key => $value) {
            $results[] = $this->set(is_int($key) ? (string) $key : $key, $value, $ttl);
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
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $results = [];

        foreach ($keys as $key) {
            $results[] = $this->delete(is_int($key) ? (string) $key : $key);
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
