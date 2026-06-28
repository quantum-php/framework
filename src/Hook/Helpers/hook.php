<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Di\Exceptions\DiException;
use Quantum\Hook\HookManager;
use Quantum\Di\Di;

/**
 * Gets the HookManager instance
 * @throws DiException|ReflectionException
 */
function hook(): HookManager
{
    if (!Di::isRegistered(HookManager::class)) {
        Di::register(HookManager::class);
    }

    return Di::get(HookManager::class);
}
