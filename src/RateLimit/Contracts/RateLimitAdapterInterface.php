<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\RateLimit\Contracts;

/**
 * Interface RateLimitAdapterInterface
 * @package Quantum\RateLimit
 */
interface RateLimitAdapterInterface
{
    public function hit(string $key, int $limit, int $interval): bool;

    public function reset(string $key, int $count = 0): void;

    public function retryAfter(string $key): int;
}
