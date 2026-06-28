<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Session\Factories\SessionFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Session\Session;

/**
 * @throws ConfigException|BaseException|DiException|ReflectionException
 */
function session(?string $adapter = null): Session
{
    return SessionFactory::get($adapter);
}
