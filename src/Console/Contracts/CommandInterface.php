<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Contracts;

/**
 * Interface CommandInterface
 * @package Quantum\Console
 */
interface CommandInterface
{
    /**
     * Executes the current command.
     */
    public function exec(): void;

}
