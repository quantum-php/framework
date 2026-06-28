<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Enums;

/**
 * Class CacheType
 * @package Quantum\Cache
 * @codeCoverageIgnore
 */
final class CacheType
{
    public const FILE = 'file';

    public const DATABASE = 'database';

    public const MEMCACHED = 'memcached';

    public const REDIS = 'redis';

    private function __construct()
    {
    }
}
