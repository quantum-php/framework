<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Di\Exceptions\DiException;
use Quantum\Environment\Server;
use Quantum\Di\Di;

/**
 * @throws DiException|ReflectionException
 */
function server(): Server
{
    if (!Di::isRegistered(Server::class)) {
        Di::register(Server::class);
    }

    return Di::get(Server::class);
}

function get_user_ip(): ?string
{
    return server()->ip();
}

if (!function_exists('getallheaders')) {

    /**
     * @return array<string, mixed>
     * @throws DiException|ReflectionException
     */
    function getallheaders(): array
    {
        return server()->getAllHeaders();
    }
}
