<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Contracts;

/**
 * Interface AppInterface
 * @package Quantum\App
 */
interface AppInterface
{
    /**
     * Starts the app
     */
    public function start(): ?int;
}
