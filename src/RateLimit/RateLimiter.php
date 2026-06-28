<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\RateLimit;

use Quantum\RateLimit\Contracts\RateLimitAdapterInterface;

/**
 * Class RateLimiter
 * @package Quantum\RateLimit
 */
class RateLimiter
{
    private RateLimitAdapterInterface $adapter;

    public function __construct(RateLimitAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): RateLimitAdapterInterface
    {
        return $this->adapter;
    }

    public function hit(
        string $method,
        string $routePattern,
        ?string $ip,
        int $limit,
        int $interval
    ): bool {
        return $this->adapter->hit(
            $this->buildKey($method, $routePattern, $ip),
            $limit,
            $interval
        );
    }

    public function reset(string $method, string $routePattern, ?string $ip, int $count = 0): void
    {
        $this->adapter->reset($this->buildKey($method, $routePattern, $ip), $count);
    }

    public function retryAfter(string $method, string $routePattern, ?string $ip): int
    {
        return $this->adapter->retryAfter(
            $this->buildKey($method, $routePattern, $ip)
        );
    }

    public function buildKey(string $method, string $routePattern, ?string $ip): string
    {
        $normalizedMethod = strtoupper(trim($method));
        $normalizedRoute = trim($routePattern);
        $normalizedIp = trim((string) $ip);

        if ($normalizedRoute === '') {
            $normalizedRoute = '/';
        }

        if ($normalizedIp === '') {
            $normalizedIp = '0.0.0.0';
        }

        return $normalizedMethod . ':' . $normalizedRoute . ':' . $normalizedIp;
    }
}
