<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Storage\FileSystem;

/**
 * Gets the FileSystem handler
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function fs(?string $adapter = null): FileSystem
{
    return FileSystemFactory::get($adapter);
}
