<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Middleware;

use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class Middleware
 * @package Quantum\Middleware
 */
abstract class Middleware
{
    /**
     * Applies the middleware
     */
    abstract public function apply(Request $request, Closure $next): Response;

}
