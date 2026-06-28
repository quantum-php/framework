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
use Quantum\Cache\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Psr\SimpleCache\CacheInterface;
use Quantum\Cache\Traits\CacheTrait;
use Quantum\Storage\FileSystem;
use InvalidArgumentException;
use ReflectionException;
use Exception;

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
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            $cacheItem = $this->fs->get($this->getPath($key));

            if (!$cacheItem) {
                return $default;
            }

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
            throw new InvalidArgumentException(_message(ExceptionMessages::ARGUMENT_NOT_ITERABLE, '$keys'), E_WARNING);
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
    public function set($key, $value, $ttl = null): bool
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
     * @throws InvalidArgumentException
     * @param iterable<string, mixed> $values
     */
    public function setMultiple($values, $ttl = null): bool
    {
        if (!is_array($values)) {
            throw new InvalidArgumentException(_message(ExceptionMessages::ARGUMENT_NOT_ITERABLE, '$values'), E_WARNING);
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
        $path = $this->getPath($key);

        if ($this->fs->exists($path)) {
            return $this->fs->remove($path);
        }

        return false;
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     * @param iterable<string> $keys
     */
    public function deleteMultiple($keys): bool
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException(_message(ExceptionMessages::ARGUMENT_NOT_ITERABLE, '$keys'), E_WARNING);
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
