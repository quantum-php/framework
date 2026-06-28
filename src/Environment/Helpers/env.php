<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Environment\Exceptions\EnvException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Environment\Environment;
use Quantum\Di\Di;

/**
 * Gets the Environment instance from DI
 * @throws DiException|\ReflectionException
 */
function environment(): Environment
{
    if (!Di::isRegistered(Environment::class)) {
        Di::register(Environment::class);
    }

    return Di::get(Environment::class);
}

/**
 * Gets the value of an environment variable
 * @param string $var
 * @param mixed|null $default
 * @return mixed
 * @throws EnvException|DiException|\ReflectionException
 */
function env(string $var, $default = null)
{
    return environment()->getValue($var, $default);
}
