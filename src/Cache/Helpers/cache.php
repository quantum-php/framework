<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Cache\Factories\CacheFactory;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Cache\Cache;

/**
 * @throws ConfigException|BaseException|DiException|ReflectionException
 */
function cache(?string $adapter = null): Cache
{
    return CacheFactory::get($adapter);
}
