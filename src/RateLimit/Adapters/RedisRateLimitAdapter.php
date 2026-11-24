<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\RateLimit\Adapters;

use Quantum\RateLimit\Contracts\RateLimitAdapterInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Config\ConfigSection;
use RedisException;
use Redis;

/**
 * Class RedisRateLimitAdapter
 * @package Quantum\RateLimit
 */
class RedisRateLimitAdapter implements RateLimitAdapterInterface
{
    private readonly Redis $redis;

    private readonly int $resetInterval;

    private readonly string $prefix;

    /**
     * @throws RedisException
     * @throws ConfigException
     */
    public function __construct(ConfigSection $config)
    {
        if (!$config->has('ttl')) {
            throw ConfigException::missingKey('ttl');
        }

        $this->resetInterval = $config->getInt('ttl');
        $this->prefix = (string) $config->get('prefix', '');

        $this->redis = new Redis();
        $this->redis->connect($config->get('host'), $config->getInt('port'));
    }

    public function hit(string $key, int $limit, int $interval): bool
    {
        $namespacedKey = $this->prefix . $key;
        $count = (int) $this->redis->incr($namespacedKey);

        if ($count === 1) {
            $this->redis->expire($namespacedKey, $interval);
        }

        return $count <= $limit;
    }

    public function reset(string $key, int $count = 0): void
    {
        $namespacedKey = $this->prefix . $key;

        if ($count <= 0) {
            $this->redis->del($namespacedKey);
            return;
        }

        $this->redis->setex($namespacedKey, $this->resetInterval, (string) $count);
    }

    public function retryAfter(string $key): int
    {
        $ttl = (int) $this->redis->ttl($this->prefix . $key);
        return $ttl > 0 ? $ttl : 0;
    }
}
