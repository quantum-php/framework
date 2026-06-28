<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Adapters;

use Quantum\Model\Exceptions\ModelException;
use Quantum\Cache\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Cache\Traits\CacheTrait;
use Psr\SimpleCache\CacheInterface;
use InvalidArgumentException;
use Quantum\Model\DbModel;
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
    public function get($key, $default = null)
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
     * @param iterable<string> $keys
     * @return iterable<string, mixed>
     * @throws InvalidArgumentException|BaseException
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
    public function set($key, $value, $ttl = null): bool
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
     * @param iterable<string> $keys
     * @throws ModelException|BaseException|InvalidArgumentException
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
     * @throws ModelException|BaseException
     */
    public function delete($key): bool
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
