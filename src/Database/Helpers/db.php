<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Database\Database;
use Quantum\Di\Di;

/**
 * Gets the Database instance from DI
 */
function db(): Database
{
    if (!Di::isRegistered(Database::class)) {
        Di::register(Database::class);
    }

    return Di::get(Database::class);
}
