<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Debugger\Debugger;
use Quantum\Di\Di;

/**
 * Gets the Debugger instance from DI
 */
function debugbar(): Debugger
{
    if (!Di::isRegistered(Debugger::class)) {
        Di::register(Debugger::class);
    }

    return Di::get(Debugger::class);
}
