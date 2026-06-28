<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Config\Config;
use Quantum\Di\Di;
use Quantum\Di\Exceptions\DiException;

/**
 * Config facade
 * @throws DiException|ReflectionException
 */
function config(): Config
{
    if (!Di::isRegistered(Config::class)) {
        Di::register(Config::class);
    }

    return Di::get(Config::class);
}
