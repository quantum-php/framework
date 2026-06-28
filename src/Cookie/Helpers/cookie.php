<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Di\Exceptions\DiException;
use Quantum\Cookie\Cookie;
use Quantum\Di\Di;

/**
 * Gets cookie handler
 * @throws DiException|ReflectionException
 */
function cookie(): Cookie
{
    if (!Di::has(Cookie::class)) {
        Di::set(Cookie::class, new Cookie($_COOKIE));
    }

    return Di::get(Cookie::class);
}
