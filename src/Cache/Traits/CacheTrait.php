<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Traits;

use DateTimeImmutable;
use DateInterval;

/**
 * Trait CacheTrait
 * @package Quantum\Cache
 */
trait CacheTrait
{
    protected int $ttl;

    protected string $prefix;

    /**
     * Gets the hashed key
     */
    protected function keyHash(string $key): string
    {
        return sha1($this->prefix . $key);
    }

    /**
     * Normalizes the TTL
     * @param int|DateInterval|null $ttl
     * @return int
     */
    protected function normalizeTtl($ttl): int
    {
        if ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable();
            $future = $now->add($ttl);

            return max(0, $future->getTimestamp() - $now->getTimestamp());
        }

        if ($ttl === null) {
            return $this->ttl;
        }

        return (int) $ttl;
    }
}
