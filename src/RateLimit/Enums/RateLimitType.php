<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\RateLimit\Enums;

/**
 * Class RateLimitType
 * @package Quantum\RateLimit
 * @codeCoverageIgnore
 */
final class RateLimitType
{
    public const FILE = 'file';

    public const REDIS = 'redis';

    private function __construct()
    {
    }
}
