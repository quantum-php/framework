<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Adapters;

use Quantum\Cache\Exceptions\InvalidCacheKeyException;
use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Cache\Traits\CacheTrait;
use Psr\SimpleCache\CacheInterface;
use Quantum\Model\DbModel;
use DateInterval;
use Exception;

/**
 * Class DatabaseAdapter
 * @package Quantum\Cache
 */
class DatabaseAdapter implements CacheInterface
{
    use CacheTrait;

    private DbModel $cacheModel;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        $this->ttl = $params['ttl'];
        $this->prefix = $params['prefix'];
        $this->cacheModel = ModelFactory::createDynamicModel($params['table']);
    }

    /**
     * @inheritDoc
     * @throws BaseException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            $cacheItem = $this->cacheModel->findOneBy('key', $this->keyHash($key));

            if ($cacheItem === null) {
                return $default;
            }

            try {
                return unserialize($cacheItem->prop('value'));
            } catch (Exception) {
                $this->delete($key);
                return $default;
            }
        }

        return $default;
    }

    /**
     * @inheritDoc
     * @param iterable<int|string> $keys
     * @return iterable<int|string, mixed>
     * @throws BaseException
     * @throws InvalidCacheKeyException
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($this->normalizeKey($key), $default);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $cacheItem = $this->cacheModel->findOneBy('key', $this->keyHash($key));

        if ($cacheItem === null || empty($cacheItem->asArray())) {
            return false;
        }

        if (time() >= $cacheItem->prop('ttl')) {
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
        $cacheItem = $this->cacheModel->findOneBy('key', $this->keyHash($key));

        if ($cacheItem === null || empty($cacheItem->asArray())) {
            $cacheItem = $this->cacheModel->create();
        }

        $cacheItem->prop('key', $this->keyHash($key));
        $cacheItem->prop('value', serialize($value));
        $cacheItem->prop('ttl', time() + $this->normalizeTtl($ttl));

        return $cacheItem->save();
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
     * @param iterable<int|string> $keys
     * @throws ModelException|BaseException
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
     * @throws ModelException|BaseException
     */
    public function delete(string $key): bool
    {
        $cacheItem = $this->cacheModel->findOneBy('key', $this->keyHash($key));

        if ($cacheItem !== null && !empty($cacheItem->asArray())) {
            return $cacheItem->delete();
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->cacheModel->deleteMany();
    }
}
