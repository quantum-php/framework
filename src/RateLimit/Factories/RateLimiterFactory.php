<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\RateLimit\Factories;

use Quantum\RateLimit\Contracts\RateLimitAdapterInterface;
use Quantum\RateLimit\Adapters\RedisRateLimitAdapter;
use Quantum\RateLimit\Adapters\FileRateLimitAdapter;
use Quantum\RateLimit\Exceptions\RateLimitException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\RateLimit\Enums\RateLimitType;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\RateLimit\RateLimiter;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class RateLimiterFactory
 * @package Quantum\RateLimit
 */
class RateLimiterFactory
{
    public const ADAPTERS = [
        RateLimitType::FILE => FileRateLimitAdapter::class,
        RateLimitType::REDIS => RedisRateLimitAdapter::class,
    ];

    /**
     * @var array<string, RateLimiter>
     */
    private array $instances = [];

    /**
     * @throws ConfigException|BaseException|DiException|ReflectionException
     */
    public static function get(?string $adapter = null): RateLimiter
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve($adapter);
    }

    /**
     * @throws ConfigException|BaseException|DiException|ReflectionException
     */
    public function resolve(?string $adapter = null): RateLimiter
    {
        if (!config()->has('rate_limit')) {
            config()->import(new Setup('config', 'rate_limit'));
        }

        $adapter ??= (string) config()->get('rate_limit.default', RateLimitType::FILE);
        $adapterClass = $this->getAdapterClass($adapter);

        if (!isset($this->instances[$adapter])) {
            $this->instances[$adapter] = $this->createInstance($adapterClass, $adapter);
        }

        return $this->instances[$adapter];
    }

    /**
     * @throws BaseException
     */
    private function getAdapterClass(string $adapter): string
    {
        $class = self::ADAPTERS[$adapter] ?? null;

        if ($class === null) {
            throw RateLimitException::adapterNotSupported($adapter);
        }

        return $class;
    }

    private function createInstance(string $adapterClass, string $adapter): RateLimiter
    {
        $params = (array) config()->get('rate_limit.' . $adapter, []);

        /** @var RateLimitAdapterInterface $adapterInstance */
        $adapterInstance = new $adapterClass($params);

        return new RateLimiter($adapterInstance);
    }
}
