<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Middleware\Exceptions;

use Quantum\Middleware\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class MiddlewareException
 * @package Quantum\Middleware
 */
class MiddlewareException extends BaseException
{
    public static function middlewareNotFound(string $name): self
    {
        return new self(
            _message(ExceptionMessages::MIDDLEWARE_NOT_FOUND, [$name]),
            E_ERROR
        );
    }
}
