<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Asset\Exceptions\AssetException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Asset\AssetManager;
use Quantum\Di\Di;

/**
 * Gets the AssetManager instance
 * @throws DiException|ReflectionException
 */
function asset(): AssetManager
{
    if (!Di::isRegistered(AssetManager::class)) {
        Di::register(AssetManager::class);
    }

    return Di::get(AssetManager::class);
}

/**
 * Dumps the assets
 * @throws AssetException|DiException|ReflectionException
 */
function assets(string $type): void
{
    asset()->dump(AssetManager::STORES[$type]);
}
